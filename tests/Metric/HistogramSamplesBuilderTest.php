<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Tests\Metric;

use ErickSkrauch\Prometheus\Collector\Histogram;
use ErickSkrauch\Prometheus\Metric\HistogramSamplesBuilder;
use ErickSkrauch\Prometheus\Metric\Sample;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HistogramSamplesBuilder::class)]
final class HistogramSamplesBuilderTest extends TestCase {

    public function testFillBucketMatchesAnExactFloatBoundary(): void {
        $builder = new HistogramSamplesBuilder('request_duration', [0.1, 0.5, 1.0], 'help text', []);
        $builder->fillBucket(0.5, 1, []);
        $builder->fillBucket(1.0, 1, []);
        $builder->setCount(2, []);
        $builder->setSum(1.5, []);

        $result = $builder->build();
        $this->assertSame('request_duration', $result->name);
        $this->assertSame('histogram', $result->type);
        $this->assertSame('help text', $result->help);
        $this->assertSame(0, self::findBucketSample($result->samples, '0.1')->value);
        $this->assertSame(1, self::findBucketSample($result->samples, '0.5')->value);
        $this->assertSame(2, self::findBucketSample($result->samples, '1')->value);
    }

    public function testFillBucketThrowsWhenBoundaryIsNotAmongTheBuckets(): void {
        $builder = new HistogramSamplesBuilder('request_duration', [0.1, 0.5, 1.0], 'help text', []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find bucket for provided value');

        $builder->fillBucket(0.75, 1, []);
    }

    /**
     * @param list<Sample> $samples
     */
    private static function findBucketSample(array $samples, string $le): Sample {
        foreach ($samples as $sample) {
            if (str_contains($sample->name, 'bucket') && $sample->labels[Histogram::LE] === $le) {
                return $sample;
            }
        }

        self::fail("No bucket sample with le=\"{$le}\" found");
    }

}
