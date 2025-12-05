<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage\Redis;

use Predis\Client as PredisClient;

final class Predis implements RedisClient {

    public function __construct(
        private readonly PredisClient $redis,
    ) {
    }

    /**
     * @param string|array<mixed> $options
     *
     * @see https://github.com/predis/predis#connecting-to-redis
     */
    public static function create(string|array $options): self {
        return new self(new PredisClient($options));
    }

    public static function fromExistsConnection(PredisClient $client): self {
        return new self($client);
    }

    public function del(string ...$keys): void {
        $this->redis->del(...$keys);
    }

    public function hGetAll(string $key): iterable {
        return $this->redis->hgetall($key);
    }

    public function hSet(string $key, string $member, string $value): void {
        $this->redis->hset($key, $member, $value);
    }

    public function hSetNx(string $key, string $member, string $value): void {
        $this->redis->hsetnx($key, $member, $value);
    }

    public function hIncrByFloat(string $key, string $member, float $value): void {
        $this->redis->hincrbyfloat($key, $member, $value);
    }

}
