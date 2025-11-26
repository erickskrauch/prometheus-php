<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Collector;

final class Counter extends Collector {

    public const TYPE = 'counter';

    /**
     * @param list<string> $labelsValues e.g. ['status', 'opcode']
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function inc(float $delta = 1, array $labelsValues = []): self {
        $this->assertLabelsAreDefinedCorrectly($labelsValues);

        $this->storageAdapter->updateCounter(
            $this->name,
            $delta,
            $this->help,
            $this->labelsNames,
            $labelsValues,
        );

        return $this;
    }

}
