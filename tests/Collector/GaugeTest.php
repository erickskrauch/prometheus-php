<?php
declare(strict_types=1);

namespace Collector;

use ErickSkrauch\Prometheus\Collector\Collector;
use ErickSkrauch\Prometheus\Collector\Gauge;
use ErickSkrauch\Prometheus\Storage\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collector::class)]
#[CoversClass(Gauge::class)]
final class GaugeTest extends TestCase {

    public function testInc(): void {
        $adapter = $this->createMock(Adapter::class);
        $adapter
            ->expects($this->once())
            ->method('updateGauge')
            ->with('mock_name', 12, true, 'mock help text', ['mockLabelName'], ['mockLabelValue']);

        $gauge = new Gauge($adapter, 'mock_name', 'mock help text', ['mockLabelName']);
        $gauge->inc(12, ['mockLabelValue']);
    }

    public function testSet(): void {
        $adapter = $this->createMock(Adapter::class);
        $adapter
            ->expects($this->once())
            ->method('updateGauge')
            ->with('mock_name', 10, false, 'mock help text', ['mockLabelName'], ['mockLabelValue']);

        $gauge = new Gauge($adapter, 'mock_name', 'mock help text', ['mockLabelName']);
        $gauge->set(10, ['mockLabelValue']);
    }

}
