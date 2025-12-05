<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Tests\Storage\Redis;

use ErickSkrauch\Prometheus\Storage\Redis\PHPRedis;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Redis;

#[Group('redis')]
#[Group('integrational')]
#[CoversClass(PHPRedis::class)]
final class PHPRedisTest extends AbstractRedisClientTestCase {

    public static function provideRedis(): iterable {
        $options = [
            'host' => self::getRedisHost(),
            'port' => self::getRedisPort(),
        ];

        yield [PHPRedis::create($options)];
        yield [PHPRedis::fromExistsConnection(new Redis($options))];
    }

}
