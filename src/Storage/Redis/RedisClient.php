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
     * When backend doesn't support this command, must be implemented via HSET
     * with handling of the FNX option (probably via HSETNX).
     *
     * @see https://redis.io/docs/latest/commands/hsetex/
     *
     * @param array<string, scalar> $values
     * @param list<scalar> $options ex. ['FNX', 'EX', 1234]
     */
    public function hSetEx(string $key, array $values, array $options = []): void;

    /**
     * https://redis.io/docs/latest/commands/hincrbyfloat/
     */
    public function hIncrByFloat(string $key, string $member, float $value): void;

}
