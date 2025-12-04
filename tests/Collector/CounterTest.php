<?php
declare(strict_types=1);

namespace Collector;

use ErickSkrauch\Prometheus\Collector\Collector;
use ErickSkrauch\Prometheus\Collector\Counter;
use ErickSkrauch\Prometheus\Storage\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collector::class)]
#[CoversClass(Counter::class)]
final class CounterTest extends TestCase {

    public function testInc(): void {
        $adapter = $this->createMock(Adapter::class);
        $adapter
            ->expects($this->once())
            ->method('updateCounter')
            ->with('mock_name', 15, 'mock help text', ['mockLabelName'], ['mockLabelValue']);

        $counter = new Counter($adapter, 'mock_name', 'mock help text', ['mockLabelName']);
        $counter->inc(15, ['mockLabelValue']);
    }

}
