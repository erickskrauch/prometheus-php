<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage\Redis;

use Redis;

final class PHPRedis implements RedisClient {

    public function __construct(
        private readonly Redis $redis,
    ) {
    }

    /**
     * @see https://github.com/phpredis/phpredis#class-redis
     *
     * @param array<mixed> $options
     */
    public static function create(array $options): self {
        return new self(new Redis($options));
    }

    public static function fromExistsConnection(Redis $connection): self {
        return new self($connection);
    }

    public function del(string ...$keys): void {
        $this->redis->del(...$keys);
    }

    public function hGetAll(string $key): iterable {
        $result = $this->redis->hGetAll($key);
        if ($result === false) {
            return [];
        }

        return $result;
    }

    public function hSet(string $key, string $member, string $value): void {
        $this->redis->hSet($key, $member, $value);
    }

    public function hSetNx(string $key, string $member, string $value): void {
        $this->redis->hSetNx($key, $member, $value);
    }

    public function hIncrByFloat(string $key, string $member, float $value): void {
        $this->redis->hIncrByFloat($key, $member, $value);
    }

}
