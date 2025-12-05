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
     * @return iterable<string, string> MUST return empty array when no results found.
     *                                  Might be implemented with HSCAN if applied to the extra large metrics sets.
     */
    public function hGetAll(string $key): iterable;

    /**
     * @see https://redis.io/docs/latest/commands/hset/
     */
    public function hSet(string $key, string $member, string $value): void;

    /**
     * @see https://redis.io/docs/latest/commands/hsetnx/
     */
    public function hSetNx(string $key, string $member, string $value): void;

    /**
     * https://redis.io/docs/latest/commands/hincrbyfloat/
     */
    public function hIncrByFloat(string $key, string $member, float $value): void;

}
