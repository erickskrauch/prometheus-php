<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus;

final class Utils {

    /**
     * PHP's native array_combine throws an error when an empty array provided for keys.
     *
     * @param list<string> $keys
     * @param list<string> $values
     *
     * @return array<string, string>
     */
    public static function arrayCombine(array $keys, array $values): array {
        if ($keys === []) {
            return [];
        }

        return array_combine($keys, $values);
    }

}
