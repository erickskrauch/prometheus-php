<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage;

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Metric\HistogramSamplesBuilder;
use ErickSkrauch\Prometheus\Metric\MetricFamilySamples;
use ErickSkrauch\Prometheus\Metric\Sample;

final class InMemory implements Adapter {

    /**
     * @var array<string, array{
     *     help: string,
     *     labelsNames: list<string>,
     *     samples: array<string, array{
     *         value: float,
     *         labelsValues: list<string>,
     *     }>,
     * }>
     */
    private array $counters = [];

    /**
     * @var array<string, array{
     *     help: string,
     *     labelsNames: list<string>,
     *     samples: array<string, array{
     *         value: float,
     *         labelsValues: list<string>,
     *     }>,
     * }>
     */
    private array $gauges = [];

    /**
     * @var array<string, array{
     *     buckets: non-empty-list<float>,
     *     help: string,
     *     labelsNames: list<string>,
     *     samples: array<string, array{
     *         buckets: list<non-negative-int>,
     *         count: non-negative-int,
     *         sum: float,
     *         labelsValues: list<string>,
     *     }>,
     * }>
     */
    private array $histograms = [];

    public function updateCounter(
        string $name,
        float $delta,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = [
                'help' => $help,
                'labelsNames' => $labelsNames,
                'samples' => [],
            ];
        }

        $labelsKey = self::labelsValuesKey($labelsValues);
        if (!isset($this->counters[$name]['samples'][$labelsKey])) {
            $this->counters[$name]['samples'][$labelsKey] = [
                'value' => 0,
                'labelsValues' => $labelsValues,
            ];
        }

        $this->counters[$name]['samples'][$labelsKey]['value'] += $delta;
    }

    public function updateGauge(
        string $name,
        float $value,
        bool $valueIsDelta,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        if (!isset($this->gauges[$name])) {
            $this->gauges[$name] = [
                'help' => $help,
                'labelsNames' => $labelsNames,
                'samples' => [],
            ];
        }

        $labelsKey = self::labelsValuesKey($labelsValues);
        if (!isset($this->gauges[$name]['samples'][$labelsKey])) {
            $this->gauges[$name]['samples'][$labelsKey] = [
                'value' => 0,
                'labelsValues' => $labelsValues,
            ];
        }

        if ($valueIsDelta) {
            $this->gauges[$name]['samples'][$labelsKey]['value'] += $value;
        } else {
            $this->gauges[$name]['samples'][$labelsKey]['value'] = $value;
        }
    }

    public function updateHistogram(
        string $name,
        float $value,
        array $buckets,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void {
        if (!isset($this->histograms[$name])) {
            $this->histograms[$name] = [
                'buckets' => $buckets,
                'help' => $help,
                'labelsNames' => $labelsNames,
                'samples' => [],
            ];
        }

        $labelsKey = self::labelsValuesKey($labelsValues);
        if (!isset($this->histograms[$name]['samples'][$labelsKey])) {
            $this->histograms[$name]['samples'][$labelsKey] = [
                'buckets' => array_fill(0, count($buckets), 0),
                'count' => 0,
                'sum' => 0,
                'labelsValues' => $labelsValues,
            ];
        }

        // Update only the first matched bucket since HistogramSamplesBuilder will accumulate all values itself
        foreach ($buckets as $i => $bucket) {
            if ($value <= $bucket) {
                // @phpstan-ignore assign.propertyType (PHPStan thinks that setting $i key in this case might brake uniform index growth)
                $this->histograms[$name]['samples'][$labelsKey]['buckets'][$i]++;
                break;
            }
        }

        $this->histograms[$name]['samples'][$labelsKey]['count']++;
        $this->histograms[$name]['samples'][$labelsKey]['sum'] += $value;
    }

    public function collect(): array {
        $metrics = [];

        foreach ($this->counters as $name => $counter) {
            $metrics[] = new MetricFamilySamples(
                $name,
                Counter::TYPE,
                $counter['help'],
                array_values(
                    array_map(static function(array $sample) use ($name, $counter) {
                        return new Sample(
                            $name,
                            $sample['value'],
                            array_combine($counter['labelsNames'], $sample['labelsValues']),
                        );
                    }, $counter['samples']),
                ),
            );
        }

        foreach ($this->gauges as $name => $gauge) {
            $metrics[] = new MetricFamilySamples(
                $name,
                Gauge::TYPE,
                $gauge['help'],
                array_values(
                    array_map(static function(array $sample) use ($name, $gauge) {
                        return new Sample(
                            $name,
                            $sample['value'],
                            array_combine($gauge['labelsNames'], $sample['labelsValues']),
                        );
                    }, $gauge['samples']),
                ),
            );
        }

        foreach ($this->histograms as $name => $histogram) {
            foreach ($histogram['samples'] as $sampleData) {
                $builder = new HistogramSamplesBuilder($name, $histogram['buckets'], $histogram['help'], $histogram['labelsNames']);
                $builder->setSum($sampleData['sum'], $sampleData['labelsValues']);
                $builder->setCount($sampleData['count'], $sampleData['labelsValues']);
                foreach ($sampleData['buckets'] as $i => $bucketValue) {
                    $builder->fillBucket($histogram['buckets'][$i], $bucketValue, $sampleData['labelsValues']);
                }

                $metrics[] = $builder->build();
            }
        }

        return $metrics;
    }

    public function wipeStorage(): void {
        $this->counters = [];
        $this->gauges = [];
        $this->histograms = [];
    }

    /**
     * @param list<string> $labelsValues
     */
    private static function labelsValuesKey(array $labelsValues): string {
        return implode(':', $labelsValues);
    }

}
