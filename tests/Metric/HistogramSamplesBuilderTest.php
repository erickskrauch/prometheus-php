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
        $this->assertSame(2, self::findBucketSample($result->samples, '+Inf')->value);
    }

    public function testFillBucketWithDifferentLabels(): void {
        $builder = new HistogramSamplesBuilder('mock', [0.1, 0.5], '', ['label']);
        $builder->fillBucket(0.1, 1, ['first']);
        $builder->setCount(1, ['first']);
        $builder->setSum(1.5, ['first']);

        $builder->fillBucket(0.5, 1, ['second']);
        $builder->setCount(1, ['second']);
        $builder->setSum(1.5, ['second']);

        $result = $builder->build();
        $this->assertSame(1, self::findBucketSample($result->samples, '0.1', ['first'])->value);
        $this->assertSame(1, self::findBucketSample($result->samples, '0.5', ['first'])->value);
        $this->assertSame(0, self::findBucketSample($result->samples, '0.1', ['second'])->value);
        $this->assertSame(1, self::findBucketSample($result->samples, '0.5', ['second'])->value);
    }

    public function testFillBucketThrowsWhenBoundaryIsNotAmongTheBuckets(): void {
        $builder = new HistogramSamplesBuilder('request_duration', [0.1, 0.5, 1.0], 'help text', []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find bucket for provided value');

        $builder->fillBucket(0.75, 1, []);
    }

    /**
     * @param list<Sample> $samples
     * @param list<string> $labels
     */
    private static function findBucketSample(array $samples, string $le, array $labels = []): Sample {
        foreach ($samples as $sample) {
            if (!str_contains($sample->name, 'bucket')) {
                continue;
            }

            if ($sample->labels[Histogram::LE] !== $le) {
                continue;
            }

            if ($labels !== [] && array_diff($labels, array_values($sample->labels)) !== []) {
                continue;
            }

            return $sample;
        }

        self::fail(sprintf('No bucket sample with le="%s" and labels [%s] found', $le, implode(', ', $labels)));
    }

}
