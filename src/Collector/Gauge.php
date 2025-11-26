<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Collector;

final class Gauge extends Collector {

    public const TYPE = 'gauge';

    /**
     * @param list<string> $labelsValues e.g. ['status', 'opcode']
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function set(float $value, array $labelsValues = []): self {
        return $this->updateGauge($value, false, $labelsValues);
    }

    /**
     * @param list<string> $labelsValues
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function inc(float $delta = 1, array $labelsValues = []): self {
        return $this->updateGauge($delta, true, $labelsValues);
    }

    /**
     * @param list<string> $labelsValues
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
            $labelsValues,
        );

        return $this;
    }

}
