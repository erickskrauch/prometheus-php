<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus;

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Collector\Histogram;

final class NamespacedRegistry implements RegistryInterface {

    private readonly string $namespace;

    /**
     * @param non-empty-string $namespace
     */
    public function __construct(
        string $namespace,
        private readonly RegistryInterface $registry,
    ) {
        $this->namespace = trim($namespace, '_');
    }

    public function counter(string $name, string $help = '', array $labelsNames = []): Counter {
        return $this->registry->counter($this->prefixName($name), $help, $labelsNames);
    }

    public function gauge(string $name, string $help = '', array $labelsNames = []): Gauge {
        return $this->registry->gauge($this->prefixName($name), $help, $labelsNames);
    }

    public function histogram(string $name, array $buckets, string $help = '', array $labelsNames = []): Histogram {
        return $this->registry->histogram($this->prefixName($name), $buckets, $help, $labelsNames);
    }

    public function collectMetrics(): array {
        return $this->registry->collectMetrics();
    }

    public function wipeStorage(): void {
        $this->registry->wipeStorage();
    }

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function prefixName(string $name): string {
        return "{$this->namespace}_{$name}";
    }

}
