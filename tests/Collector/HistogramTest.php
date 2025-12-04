<?php
declare(strict_types=1);

namespace Collector;

use ErickSkrauch\Prometheus\Collector\Collector;
use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\Storage\Adapter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collector::class)]
#[CoversClass(Histogram::class)]
final class HistogramTest extends TestCase {

    public function testObserve(): void {
        $adapter = $this->createMock(Adapter::class);
        $adapter
            ->expects($this->once())
            ->method('updateHistogram')
            ->with('mock_name', 0.123, Histogram::DEFAULT_BUCKETS, 'mock help text', ['mockLabelName'], ['mockLabelValue']);

        $histogram = new Histogram($adapter, 'mock_name', Histogram::DEFAULT_BUCKETS, 'mock help text', ['mockLabelName']);
        $histogram->observe(0.123, ['mockLabelValue']);
    }

    public function testShouldThrowOnNonMonotonicBucketsSizes(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram buckets must be in increasing order: 0.1 >= 0.05');

        new Histogram($this->createMock(Adapter::class), 'mock_name', [0.1, 0.05]);
    }

    public function testShouldThrowOnLabelNamedLe(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Histogram cannot have a label named "le"');

        new Histogram($this->createMock(Adapter::class), 'mock_name', Histogram::DEFAULT_BUCKETS, labelsNames: ['mockLabelName', 'le']);
    }

}
