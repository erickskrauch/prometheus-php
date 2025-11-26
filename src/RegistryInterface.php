<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus;

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Collector\Histogram;

interface RegistryInterface {

    /**
     * @param string $name e.g. requests_count
     * @param string $help e.g. The number of requests made
     * @param list<string> $labelsNames e.g. ['controller', 'action']
     */
    public function counter(string $name, string $help = '', array $labelsNames = []): Counter;

    /**
     * @param string $name e.g. temperature_celsius
     * @param string $help e.g. The temperature outside in degrees Celsius
     * @param list<string> $labelsNames e.g. ['controller', 'action']
     */
    public function gauge(string $name, string $help = '', array $labelsNames = []): Gauge;

    /**
     * @param string $name e.g. duration_seconds
     * @param string $help e.g. A histogram of the duration in seconds.
     * @param non-empty-list<float> $buckets e.g. [0.10, 0.50, 1.0]
     * @param list<string> $labelsNames e.g. ['controller', 'action']
     */
    public function histogram(string $name, array $buckets, string $help = '', array $labelsNames = []): Histogram;

    /**
     * @return list<\ErickSkrauch\Prometheus\Metric\MetricFamilySamples>
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function collectMetrics(): array;

    /**
     * Removes all previously stored metrics from underlying storage adapter
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function wipeStorage(): void;

}
