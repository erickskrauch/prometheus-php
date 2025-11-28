<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage\Redis;

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\Metric\HistogramSamplesBuilder;
use ErickSkrauch\Prometheus\Metric\MetricFamilySamples;
use ErickSkrauch\Prometheus\Metric\Sample;
use ErickSkrauch\Prometheus\Storage\Adapter;
use ErickSkrauch\Prometheus\Utils;

final class Redis implements Adapter {

    private const HISTOGRAM_COUNT = 'count';
    private const HISTOGRAM_SUM = 'sum';

    /**
     * Use a dot as a delimiter because its cannot be part of a metric name
     */
    private const LABELS_VALUES_DELIMITER = '.';

    private readonly string $metricsHashKey;

    private readonly string $metaHashKey;

    /**
     * @param non-empty-string $keysPrefix
     */
    public function __construct(
        private readonly RedisClient $redis,
        private readonly string $keysPrefix = 'prometheus_',
    ) {
        $this->metricsHashKey = $this->keysPrefix . 'metrics';
        $this->metaHashKey = $this->keysPrefix . 'meta';
    }

    public function updateCounter(
        string $name,
        float $delta,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        $this->redis->hIncrByFloat($this->metricsHashKey, self::toMetricMember($name, $labelsValues), $delta);
        $this->storeMeta($name, [
            'type' => Counter::TYPE,
            'help' => $help,
            'labelsNames' => $labelsNames,
        ]);
    }

    public function updateGauge(
        string $name,
        float $value,
        bool $valueIsDelta,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        $member = self::toMetricMember($name, $labelsValues);
        if ($valueIsDelta) {
            $this->redis->hIncrByFloat($this->metricsHashKey, $member, $value);
        } else {
            $this->redis->hSet($this->metricsHashKey, $member, $value);
        }

        $this->storeMeta($name, [
            'type' => Gauge::TYPE,
            'help' => $help,
            'labelsNames' => $labelsNames,
        ]);
    }

    public function updateHistogram(
        string $name,
        float $value,
        array $buckets,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        // Update only the first matched bucket since HistogramSamplesBuilder will accumulate all values itself
        foreach ($buckets as $bucket) {
            if ($value <= $bucket) {
                $member = self::toMetricMember($name, [...$labelsValues, (string)$bucket]);
                $this->redis->hIncrByFloat($this->metricsHashKey, $member, 1);
                break;
            }
        }

        $member = self::toMetricMember($name, [...$labelsValues, self::HISTOGRAM_COUNT]);
        $this->redis->hIncrByFloat($this->metricsHashKey, $member, 1);

        $member = self::toMetricMember($name, [...$labelsValues, self::HISTOGRAM_SUM]);
        $this->redis->hIncrByFloat($this->metricsHashKey, $member, $value);

        $this->storeMeta($name, [
            'type' => Histogram::TYPE,
            'buckets' => $buckets,
            'help' => $help,
            'labelsNames' => $labelsNames,
        ]);
    }

    public function collect(bool $sortMetrics = true): array {
        $metas = [];
        $metasIterator = self::iterateRedisKeyValuesPairs($this->redis->hGetAll($this->metaHashKey));
        foreach ($metasIterator as $name => $encodedMeta) {
            $metas[$name] = json_decode($encodedMeta, true, flags: JSON_THROW_ON_ERROR);
        }

        $staleMetrics = [];
        /** @var array<string, list<\ErickSkrauch\Prometheus\Metric\Sample>> $simpleMetricsSamples */
        $simpleMetricsSamples = [];
        /** @var array<string, \ErickSkrauch\Prometheus\Metric\HistogramSamplesBuilder> $histogramsBuilders */
        $histogramsBuilders = [];

        $metricsIterator = self::iterateRedisKeyValuesPairs($this->redis->hGetAll($this->metricsHashKey));
        foreach ($metricsIterator as $nameWithLabelsValues => $value) {
            [$name, $labelsValues] = self::toMetricNameAndLabels($nameWithLabelsValues);
            if (!isset($metas[$name])) {
                $staleMetrics[] = $nameWithLabelsValues;
                continue;
            }

            $meta = $metas[$name];
            $labelsNames = $metas[$name]['labelsNames'];

            if ($meta['type'] === Histogram::TYPE) {
                if (!isset($histogramsBuilders[$name])) {
                    $histogramsBuilders[$name] = new HistogramSamplesBuilder($name, $meta['buckets'], $meta['help'], $meta['labelsNames']);
                }

                $builder = $histogramsBuilders[$name];
                $bucketOrSumTotal = array_pop($labelsValues);
                if ($bucketOrSumTotal === self::HISTOGRAM_SUM) {
                    $builder->setSum((float)$value, $labelsValues);
                } elseif ($bucketOrSumTotal === self::HISTOGRAM_COUNT) {
                    $builder->setCount((int)$value, $labelsValues);
                } else {
                    // @phpstan-ignore argument.type (The negative value at this point means that somebody manually changed value in the storage, which is invalid)
                    $builder->fillBucket((float)$bucketOrSumTotal, (int)$value, $labelsValues);
                }

                continue;
            }

            $simpleMetricsSamples[$name][] = new Sample($name, (float)$value, Utils::arrayCombine($labelsNames, $labelsValues));
        }

        $results = [];
        foreach ($simpleMetricsSamples as $name => $samples) {
            $results[] = new MetricFamilySamples(
                $name,
                $metas[$name]['type'],
                $metas[$name]['help'],
                $samples,
            );
        }

        foreach ($histogramsBuilders as $builder) {
            $results[] = $builder->build();
        }

        if ($staleMetrics !== []) {
            $this->redis->del(...$staleMetrics);
        }

        return $results;
    }

    public function wipeStorage(): void {
        $this->redis->del($this->metaHashKey, $this->metricsHashKey);
    }

    /**
     * @param array<mixed> $meta
     *
     * @throws \JsonException
     */
    private function storeMeta(string $metricName, array $meta): void {
        $this->redis->hSetNx($this->metaHashKey, $metricName, json_encode($meta, JSON_THROW_ON_ERROR));
    }

    /**
     * @param list<string> $labelsValues
     *
     * @throws \JsonException
     */
    private static function toMetricMember(string $metricName, array $labelsValues): string {
        $member = $metricName;
        if ($labelsValues !== []) {
            $member .= self::LABELS_VALUES_DELIMITER . json_encode($labelsValues, JSON_THROW_ON_ERROR);
        }

        return $member;
    }

    /**
     * @return array{string, list<string>}
     * @throws \JsonException
     */
    private static function toMetricNameAndLabels(string $metricMember): array {
        $delimiterIndex = strpos($metricMember, self::LABELS_VALUES_DELIMITER);
        if ($delimiterIndex === false) {
            return [$metricMember, []];
        }

        return [
            substr($metricMember, 0, $delimiterIndex),
            json_decode(substr($metricMember, $delimiterIndex + 1), true, flags: JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @param list<string> $result
     *
     * @return iterable<string, string>
     */
    private static function iterateRedisKeyValuesPairs(array $result): iterable {
        $key = '';
        foreach ($result as $i => $maybeKeyOrValue) {
            if ($i % 2 === 0) {
                $key = $maybeKeyOrValue;
            } else {
                yield $key => $maybeKeyOrValue;
            }
        }
    }

}
