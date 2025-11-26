<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus;

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\Storage\Adapter;

final class CollectorRegistry implements RegistryInterface {

    /**
     * @var array<string, Counter>
     */
    private array $counters = [];

    /**
     * @var array<string, Gauge>
     */
    private array $gauges = [];

    /**
     * @var array<string, Histogram>
     */
    private array $histograms = [];

    public function __construct(
        private readonly Adapter $storage,
    ) {
    }

    public function collectMetrics(): array {
        return $this->storage->collect();
    }

    public function counter(string $name, string $help = '', array $labelsNames = []): Counter {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = new Counter($this->storage, $name, $help, $labelsNames);
        }

        return $this->counters[$name];
    }

    public function gauge(string $name, string $help = '', array $labelsNames = []): Gauge {
        if (!isset($this->gauges[$name])) {
            $this->gauges[$name] = new Gauge($this->storage, $name, $help, $labelsNames);
        }

        return $this->gauges[$name];
    }

    public function histogram(string $name, array $buckets, string $help = '', array $labelsNames = []): Histogram {
        if (!isset($this->histograms[$name])) {
            $this->histograms[$name] = new Histogram(
                $this->storage,
                $name,
                $buckets,
                $help,
                $labelsNames,
            );
        }

        return $this->histograms[$name];
    }

    public function wipeStorage(): void {
        $this->storage->wipeStorage();
    }

}
