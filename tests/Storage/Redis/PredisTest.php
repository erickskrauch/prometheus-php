<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Tests\Storage\Redis;

use ErickSkrauch\Prometheus\Storage\Redis\Predis;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Predis\Client as PredisClient;

#[Group('redis')]
#[Group('integrational')]
#[CoversClass(Predis::class)]
final class PredisTest extends AbstractRedisClientTestCase {

    public static function provideRedis(): iterable {
        $options = [
            'scheme' => 'tcp',
            'host' => self::getRedisHost(),
            'port' => self::getRedisPort(),
        ];

        yield [Predis::create($options)];
        yield [Predis::fromExistsConnection(new PredisClient($options))];
    }

}
