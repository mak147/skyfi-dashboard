<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http\Middleware;

use SkyFi\Shared\Http\Request;

final class TraceIdMiddleware
{
    /** Returns an existing trusted-format trace ID or creates one for the request. */
    public function traceId(Request $request): string
    {
        $traceId = $request->header('X-Trace-Id');
        if ($traceId !== null && preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $traceId) === 1) {
            return $traceId;
        }

        return bin2hex(random_bytes(16));
    }
}
