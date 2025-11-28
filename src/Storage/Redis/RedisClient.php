<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Storage\Redis;

interface RedisClient {

    /**
     * @see https://redis.io/docs/latest/commands/del/
     */
    public function del(string ...$keys): void;

    /**
     * @see https://redis.io/docs/latest/commands/hgetall/
     *
     * @return list<string> MUST return empty array when no results found
     */
    public function hGetAll(string $key): array;

    /**
     * @see https://redis.io/docs/latest/commands/hsetnx/
     *
     * @param scalar $value
     */
    public function hSet(string $key, string $member, mixed $value): void;

    /**
     * @see https://redis.io/docs/latest/commands/hsetnx/
     *
     * @param scalar $value
     */
    public function hSetNx(string $key, string $member, mixed $value): void;

    /**
     * https://redis.io/docs/latest/commands/hincrbyfloat/
     */
    public function hIncrByFloat(string $key, string $member, float $value): void;

}
