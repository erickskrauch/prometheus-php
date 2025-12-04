<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Collector;

use ErickSkrauch\Prometheus\Storage\Adapter;
use InvalidArgumentException;

abstract class Collector {

    /**
     * @param list<string> $labelsNames
     */
    public function __construct(
        protected readonly Adapter $storageAdapter,
        public readonly string $name,
        public readonly string $help = '',
        public readonly array $labelsNames = [],
    ) {
        self::assertValidMetricName($this->name);
        foreach ($this->labelsNames as $label) {
            self::assertValidLabel($label);
        }
    }

    /**
     * @param list<scalar> $labelsValues
     */
    protected function assertLabelsAreDefinedCorrectly(array $labelsValues): void {
        if (count($labelsValues) !== count($this->labelsNames)) {
            throw new InvalidArgumentException(sprintf('Labels are not defined correctly: %s', print_r($labelsValues, true)));
        }
    }

    /**
     * @param list<scalar> $labelsValues
     *
     * @return list<string>
     */
    protected static function castLabelsValuesToString(array $labelsValues): array {
        return array_map(static function($value): string {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return (string)$value;
        }, $labelsValues);
    }

    private static function assertValidMetricName(string $metricName): void {
        if (preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:]*$/', $metricName) !== 1) {
            throw new InvalidArgumentException("Invalid metric name: '" . $metricName . "'");
        }
    }

    private static function assertValidLabel(string $label): void {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $label) !== 1) {
            throw new InvalidArgumentException("Invalid label name: '" . $label . "'");
        }

        if (\str_starts_with($label, '__')) {
            throw new InvalidArgumentException("Can't used a reserved label name: '" . $label . "'");
        }
    }

}
