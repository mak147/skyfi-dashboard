<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http;

use Closure;
use SkyFi\Shared\Exceptions\NotFoundException;

final class Router
{
    /** @var array<int, array{method: string, path: string, handler: Closure}> */
    private array $routes = [];

    /** Registers a route handler. */
    public function add(string $method, string $path, Closure $handler): void
    {
        $this->routes[] = ['method' => strtoupper($method), 'path' => $path, 'handler' => $handler];
    }

    /** Dispatches a request to its registered handler. */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $request->method() && $route['path'] === $request->path()) {
                return ($route['handler'])($request);
            }
        }

        throw new NotFoundException('The requested API endpoint was not found.');
    }
}
