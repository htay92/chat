<?php

namespace App\Chatbot;

use Illuminate\Support\Facades\Redis;

class AtomicQuestionLimiter
{
    private const SCRIPT = <<<'LUA'
local count = tonumber(redis.call('GET', KEYS[1]) or '0')
if count == 0 then
    redis.call('SET', KEYS[1], 1, 'PX', 60000)
    return 0
end
if count >= tonumber(ARGV[1]) then
    local ttl = redis.call('PTTL', KEYS[1])
    if ttl < 0 then redis.call('PEXPIRE', KEYS[1], 60000); ttl = 60000 end
    return math.max(1, math.ceil(ttl / 1000))
end
redis.call('INCR', KEYS[1])
return 0
LUA;

    public function acquire(string $key, int $maximum): int
    {
        return (int) Redis::connection('chatbot')->eval(self::SCRIPT, 1, $key, $maximum);
    }
}
