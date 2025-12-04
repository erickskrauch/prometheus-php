<?php
declare(strict_types=1);

use ErickSkrauch\Prometheus\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Utils::class)]
final class UtilsTest extends TestCase {

    public function testArrayCombineShouldActAsNormal(): void {
        $this->assertSame(['key' => 'value'], Utils::arrayCombine(['key'], ['value']));
    }

    public function testArrayCombineShouldHandleEmptyArrays(): void {
        $this->assertSame([], Utils::arrayCombine([], []));
    }

}
