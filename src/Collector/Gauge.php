<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Collector;

final class Gauge extends Collector {

    public const TYPE = 'gauge';

    /**
     * @param list<scalar> $labelsValues e.g. ['status', 'opcode']
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function set(float $value, array $labelsValues = []): self {
        return $this->updateGauge($value, false, $labelsValues);
    }

    /**
     * @param list<scalar> $labelsValues
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function inc(float $delta = 1, array $labelsValues = []): self {
        return $this->updateGauge($delta, true, $labelsValues);
    }

    /**
     * @param list<scalar> $labelsValues
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    private function updateGauge(float $value, bool $valueIsDelta, array $labelsValues): self {
        $this->assertLabelsAreDefinedCorrectly($labelsValues);
        $this->storageAdapter->updateGauge(
            $this->name,
            $value,
            $valueIsDelta,
            $this->help,
            $this->labelsNames,
            self::castLabelsValuesToString($labelsValues),
        );

        return $this;
    }

}
