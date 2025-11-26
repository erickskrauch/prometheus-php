<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Metric;

final class Sample {

    /**
     * @param array<string, string> $labels
     */
    public function __construct(
        public readonly string $name,
        public readonly int|float $value,
        public readonly array $labels,
    ) {
    }

}
