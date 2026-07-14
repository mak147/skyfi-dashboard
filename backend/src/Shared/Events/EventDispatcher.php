<?php

declare(strict_types=1);
namespace SkyFi\Shared\Events;

final class EventDispatcher
{
    private static array $listeners = [];

    public static function listen(string $event, callable $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    public static function dispatch(string $event, mixed $payload): void
    {
        if (isset(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $listener) {
                $listener($payload);
            }
        }
    }
}
