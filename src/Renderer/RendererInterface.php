<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Renderer;

interface RendererInterface {

    /**
     * @param list<\ErickSkrauch\Prometheus\Metric\MetricFamilySamples> $metrics
     */
    public function render(array $metrics): string;

}
