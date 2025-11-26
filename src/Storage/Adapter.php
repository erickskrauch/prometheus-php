<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage;

interface Adapter {

    /**
     * @param list<string> $labelsNames
     * @param list<string> $labelsValues
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function updateCounter(
        string $name,
        float $delta,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void;

    /**
     * @param bool $valueIsDelta false value means SET operation, true - INCREMENT
     * @param list<string> $labelsNames
     * @param list<string> $labelsValues
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function updateGauge(
        string $name,
        float $value,
        bool $valueIsDelta,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void;

    /**
     * @param non-empty-list<float> $buckets will always be sorted ascending and will not contains the +Inf bucket
     * @param list<string> $labelsNames will not contains the "le" name
     * @param list<string> $labelsValues
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function updateHistogram(
        string $name,
        float $value,
        array $buckets,
        string $help,
        array $labelsNames,
        array $labelsValues,
    ): void;

    /**
     * @return list<\ErickSkrauch\Prometheus\Metric\MetricFamilySamples>
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function collect(): array;

    /**
     * Removes all previously stored metrics from underlying storage
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function wipeStorage(): void;

}
