<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage\Redis;

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\Metric\MetricFamilySamples;
use ErickSkrauch\Prometheus\Metric\Sample;
use ErickSkrauch\Prometheus\Storage\Adapter;

final class Redis implements Adapter {

    private const HISTOGRAM_COUNT = 'count';
    private const HISTOGRAM_SUM = 'sum';

    /**
     * Use a dot as a delimiter because its cannot be part of a metric name
     */
    private const LABELS_VALUES_DELIMITER = '.';

    private readonly string $metricsHashKey;

    private readonly string $metaHashKey;

    public function __construct(
        private readonly RedisClient $redis,
        private readonly string $keysPrefix = 'PROMETHEUS_',
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
        $this->redis->hSetEx(
            $this->metaHashKey,
            [
                $name => json_encode([
                    'type' => Counter::TYPE,
                    'help' => $help,
                    'labelsNames' => $labelsNames,
                ], JSON_THROW_ON_ERROR),
            ],
            ['FNX'],
        );
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
            $this->redis->hSetEx($this->metricsHashKey, [$member => $value]);
        }

        $this->redis->hSetEx(
            $this->metaHashKey,
            [
                $name => json_encode([
                    'type' => Gauge::TYPE,
                    'help' => $help,
                    'labelsNames' => $labelsNames,
                ], JSON_THROW_ON_ERROR),
            ],
            ['FNX'],
        );
    }

    public function updateHistogram(
        string $name,
        float $value,
        array $buckets,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        foreach ($buckets as $bucket) {
            if ($value >= $bucket) {
                $member = self::toMetricMember($name, [...$labelsValues, (string)$bucket]);
                $this->redis->hIncrByFloat($this->metricsHashKey, $member, 1);
            }
        }

        $member = self::toMetricMember($name, [...$labelsValues, self::HISTOGRAM_COUNT]);
        $this->redis->hIncrByFloat($this->metricsHashKey, $member, 1);

        $member = self::toMetricMember($name, [...$labelsValues, self::HISTOGRAM_SUM]);
        $this->redis->hIncrByFloat($this->metricsHashKey, $member, $value);

        $this->redis->hSetEx(
            $this->metaHashKey,
            [
                $name => json_encode([
                    'type' => Histogram::TYPE,
                    'buckets' => $buckets,
                    'help' => $help,
                    'labelsNames' => $labelsNames,
                ], JSON_THROW_ON_ERROR),
            ],
            ['FNX'],
        );
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
        /** @var array<string, array<string, list<\ErickSkrauch\Prometheus\Metric\Sample>>> $histogramsSamplesGroups */
        $histogramsSamplesGroups = [];

        $metricsIterator = self::iterateRedisKeyValuesPairs($this->redis->hGetAll($this->metricsHashKey));
        foreach ($metricsIterator as $nameWithLabelsValues => $value) {
            [$name, $encodedLabels, $labelsValues] = self::toMetricNameAndLabels($nameWithLabelsValues);
            if (!isset($metas[$name])) {
                $staleMetrics[] = $nameWithLabelsValues;
                continue;
            }

            $meta = $metas[$name];
            $labelsNames = $metas[$name]['labelsNames'];

            if ($meta['type'] === Histogram::TYPE) {
                $bucketOrSumTotal = array_last($labelsValues);
                if ($bucketOrSumTotal === self::HISTOGRAM_SUM || $bucketOrSumTotal === self::HISTOGRAM_COUNT) {
                    $name .= '_' . $bucketOrSumTotal;
                    array_pop($labelsValues);
                } else {
                    $name .= '_bucket';
                    $labelsNames[] = Histogram::LE;
                }

                $histogramsSamplesGroups[$name][$encodedLabels][] = new Sample($name, (float)$value, self::arrayCombine($labelsNames, $labelsValues));

                continue;
            }

            $simpleMetricsSamples[$name][] = new Sample($name, (float)$value, self::arrayCombine($labelsNames, $labelsValues));
        }

        foreach ($histogramsSamplesGroups as $name => $labelsGroups) {
            $meta = $metas[$name];
            $buckets = $meta['buckets'];
            foreach ($labelsGroups as $samples) {
                $acc = 0;
                foreach ($buckets as $bucket) {
                    foreach ($samples as $sample) {
                        if (isset($sample->labels[Histogram::LE])) {
                            continue;
                        }

                        if ((float)$sample->labels[Histogram::LE] === $bucket) {
                            $acc = $sample->value;
                            continue 2;
                        }
                    }

                    /** @var \ErickSkrauch\Prometheus\Metric\Sample $bucketSample */
                    $bucketSample = array_last($samples);
                    $samples[] = new Sample($bucketSample->name, $acc, [...$bucketSample->labels, Histogram::LE => $bucket]);
                }

                usort($samples, static function(Sample $a, Sample $b): int {
                    return ($a->labels[Histogram::LE] ?? null) <=> ($b->labels[Histogram::LE] ?? null);
                });

                $simpleMetricsSamples[$name] = $samples;
            }
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

        if ($staleMetrics !== []) {
            $this->redis->del(...$staleMetrics);
        }

        return $results;
    }

    public function wipeStorage(): void {
        $this->redis->del($this->metaHashKey, $this->metricsHashKey);
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
     * @return array{string, string, list<string>}
     * @throws \JsonException
     */
    private static function toMetricNameAndLabels(string $metricMember): array {
        $delimiterIndex = strpos($metricMember, self::LABELS_VALUES_DELIMITER);
        if ($delimiterIndex === false) {
            return [$metricMember, '', []];
        }

        $encodedLabels = substr($metricMember, $delimiterIndex + 1);

        return [
            substr($metricMember, 0, $delimiterIndex),
            $encodedLabels,
            json_decode($encodedLabels, true, flags: JSON_THROW_ON_ERROR),
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

    /**
     * @param list<string> $keys
     * @param list<string> $values
     *
     * @return array<string, string>
     */
    private static function arrayCombine(array $keys, array $values): array {
        if ($keys === []) {
            return [];
        }

        return array_combine($keys, $values);
    }

}
