<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Metric;

final class MetricFamilySamples {

    /**
     * @param list<\ErickSkrauch\Prometheus\Metric\Sample> $samples
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $help,
        public readonly array $samples,
    ) {
    }

}
