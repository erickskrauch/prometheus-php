<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Tests\Storage\Redis;

use ErickSkrauch\Prometheus\Storage\Redis\RedisClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class AbstractRedisClientTestCase extends TestCase {

    #[DataProvider('provideRedis')]
    public function testClientImplementation(RedisClient $redis): void {
        $hash = base64_encode(random_bytes(8)) . '_hash';

        $this->assertSame([], $redis->hGetAll('I_am_sure_this_key_is_empty'));

        $redis->hSet($hash, 'setValue', 'first');
        $redis->hSetNx($hash, 'setValue', 'second');
        $redis->hIncrByFloat($hash, 'incrValue', 1);
        $this->assertSame(['setValue' => 'first', 'incrValue' => '1'], $redis->hGetAll($hash));

        $redis->del($hash);
        $this->assertSame([], $redis->hGetAll($hash));
    }

    /**
     * @return iterable<array{\ErickSkrauch\Prometheus\Storage\Redis\RedisClient}>
     */
    abstract public static function provideRedis(): iterable;

    protected static function getRedisHost(): string {
        return getenv('REDIS_HOST') ?: 'localhost';
    }

    protected static function getRedisPort(): int {
        return (int)getenv('REDIS_PORT') ?: 6379;
    }

}
