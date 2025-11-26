<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Collector;

use ErickSkrauch\Prometheus\Storage\Adapter;
use InvalidArgumentException;

final class Histogram extends Collector {

    public const TYPE = 'histogram';

    public const INF = '+Inf';
    public const LE = 'le';

    /**
     * List of default buckets suitable for typical web application latency metrics
     *
     * @var non-empty-list<float>
     */
    public const DEFAULT_BUCKETS = [0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0];

    /**
     * @param list<string> $labelsNames
     * @param non-empty-list<float> $buckets
     */
    public function __construct(
        Adapter $adapter,
        string $name,
        private readonly array $buckets,
        string $help,
        array $labelsNames = [],
    ) {
        parent::__construct($adapter, $name, $help, $labelsNames);

        for ($i = 0; $i < count($this->buckets) - 1; $i++) {
            if ($this->buckets[$i] >= $this->buckets[$i + 1]) {
                throw new InvalidArgumentException(
                    'Histogram buckets must be in increasing order: '
                    . $this->buckets[$i] . ' >= ' . $this->buckets[$i + 1],
                );
            }
        }

        if (in_array(self::LE, $labelsNames, true)) {
            throw new InvalidArgumentException("Histogram cannot have a label named 'le'.");
        }
    }

    /**
     * @param positive-int $numberOfBuckets
     *
     * @return non-empty-list<float>
     */
    public static function exponentialBuckets(float $start, float $growthFactor, int $numberOfBuckets): array {
        if ($start <= 0) {
            throw new InvalidArgumentException('The starting position of a set of buckets must be a positive integer');
        }

        if ($growthFactor <= 1) {
            throw new InvalidArgumentException('The growth factor must greater than 1');
        }

        $buckets = [];
        for ($i = 0; $i < $numberOfBuckets; $i++) {
            $buckets[] = $start;
            $start *= $growthFactor;
        }

        // @phpstan-ignore return.type (I can't convince PHPStan, that this array can't be an empty)
        return $buckets;
    }

    /**
     * @param list<string> $labelsValues e.g. ['status', 'opcode']
     *
     * @throws \ErickSkrauch\Prometheus\Exception\StorageException
     */
    public function observe(float $value, array $labelsValues = []): void {
        $this->assertLabelsAreDefinedCorrectly($labelsValues);

        $this->storageAdapter->updateHistogram(
            $this->name,
            $value,
            $this->buckets,
            $this->help,
            $this->labelsNames,
            $labelsValues,
        );
    }

}
