<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Metric;

use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\Utils;
use InvalidArgumentException;

/**
 * @phpstan-type MetricGroup array{
 *     count: int,
 *     sum: float,
 *     labelsValues: list<string>,
 *     bucketsValues: non-empty-list<non-negative-int>,
 * }
 */
final class HistogramSamplesBuilder {

    /**
     * @var array<string, MetricGroup>
     */
    private array $metricsGroups = [];

    /**
     * @param non-empty-list<float> $buckets
     * @param list<string> $labelsNames
     */
    public function __construct(
        private readonly string $name,
        private readonly array $buckets,
        private readonly string $help = '',
        private readonly array $labelsNames = [],
    ) {
    }

    /**
     * @param list<string> $labelsValues
     */
    public function setCount(int $count, array $labelsValues): void {
        $metricsGroup = &$this->ensureGroupExists($labelsValues);
        $metricsGroup['count'] = $count;
    }

    /**
     * @param list<string> $labelsValues
     */
    public function setSum(float $sum, array $labelsValues): void {
        $metricsGroup = &$this->ensureGroupExists($labelsValues);
        $metricsGroup['sum'] = $sum;
    }

    /**
     * @param non-negative-int $value
     * @param list<string> $labelsValues
     *
     * @throws \InvalidArgumentException
     */
    public function fillBucket(float $bucket, int $value, array $labelsValues): void {
        $bucketN = array_search($bucket, $this->buckets, true);
        if ($bucketN === false) {
            throw new InvalidArgumentException('Unable to find bucket for provided value');
        }

        $metricsGroup = &$this->ensureGroupExists($labelsValues);
        $metricsGroup['bucketsValues'][$bucketN] = $value;
    }

    public function build(): MetricFamilySamples {
        $samples = [];
        foreach ($this->metricsGroups as $metricsGroup) {
            $groupSamples = [];
            $acc = 0;
            $labels = Utils::arrayCombine($this->labelsNames, $metricsGroup['labelsValues']);
            foreach ($metricsGroup['bucketsValues'] as $i => $bucketValue) {
                $acc += $bucketValue;

                $groupSamples[] = new Sample(
                    $this->name . '_bucket',
                    $acc,
                    [...$labels, Histogram::LE => (string)$this->buckets[$i]],
                );
            }

            $groupSamples[] = new Sample($this->name . '_bucket', $metricsGroup['count'], [...$labels, Histogram::LE => Histogram::INF]);
            $groupSamples[] = new Sample($this->name . '_count', $metricsGroup['count'], $labels);
            $groupSamples[] = new Sample($this->name . '_sum', $metricsGroup['sum'], $labels);

            $samples += $groupSamples;
        }

        return new MetricFamilySamples($this->name, Histogram::TYPE, $this->help, $samples);
    }

    /**
     * @param list<string> $labelsValues
     *
     * @return MetricGroup
     */
    private function &ensureGroupExists(array $labelsValues): array {
        $labelsHash = self::labelsHash($labelsValues);
        if (!isset($this->metricsGroups[$labelsHash])) {
            $this->metricsGroups[$labelsHash] = [
                'count' => 0,
                'sum' => 0,
                'labelsValues' => $labelsValues,
                'bucketsValues' => array_fill(0, count($this->buckets), 0),
            ];
        }

        return $this->metricsGroups[$labelsHash];
    }

    /**
     * @param array<mixed> $labelsValues
     *
     * @throws \JsonException
     */
    private static function labelsHash(array $labelsValues): string {
        if ($labelsValues === []) {
            return '';
        }

        return json_encode($labelsValues, JSON_THROW_ON_ERROR);
    }

}
