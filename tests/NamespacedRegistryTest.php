<?php
declare(strict_types=1);

use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\NamespacedRegistry;
use ErickSkrauch\Prometheus\RegistryInterface;
use ErickSkrauch\Prometheus\Storage\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamespacedRegistry::class)]
final class NamespacedRegistryTest extends TestCase {

    private const PREFIX = 'find_me';

    private RegistryInterface&MockObject $mockRegistry;

    private NamespacedRegistry $registry;

    protected function setUp(): void {
        parent::setUp();

        $this->mockRegistry = $this->createMock(RegistryInterface::class);
        $this->registry = new NamespacedRegistry(self::PREFIX, $this->mockRegistry);
    }

    public function testCounter(): void {
        $counter = new Counter($this->createMock(Adapter::class), 'mock');
        $this->mockRegistry
            ->expects($this->once())
            ->method('counter')
            ->with('find_me_counter', 'Mock help test', ['mockLabelName'])
            ->willReturn($counter);
        $this->assertSame($counter, $this->registry->counter('counter', 'Mock help test', ['mockLabelName']));
    }

    public function testGauge(): void {
        $gauge = new Gauge($this->createMock(Adapter::class), 'mock');
        $this->mockRegistry
            ->expects($this->once())
            ->method('gauge')
            ->with('find_me_gauge', 'Mock help test', ['mockLabelName'])
            ->willReturn($gauge);
        $this->assertSame($gauge, $this->registry->gauge('gauge', 'Mock help test', ['mockLabelName']));
    }

    public function testHistogram(): void {
        $histogram = new Histogram($this->createMock(Adapter::class), 'mock', Histogram::DEFAULT_BUCKETS);
        $this->mockRegistry
            ->expects($this->once())
            ->method('histogram')
            ->with('find_me_histogram', Histogram::DEFAULT_BUCKETS, 'Mock help test', ['mockLabelName'])
            ->willReturn($histogram);
        $this->assertSame($histogram, $this->registry->histogram('histogram', Histogram::DEFAULT_BUCKETS, 'Mock help test', ['mockLabelName']));
    }

    public function testWipeStorage(): void {
        $this->mockRegistry->expects($this->once())->method('wipeStorage');
        $this->registry->wipeStorage();
    }

}
