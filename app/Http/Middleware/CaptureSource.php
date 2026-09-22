<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\OrderSourceCaptureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CaptureSource
{
    public function __construct(private readonly OrderSourceCaptureService $sources) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $this->sources->capture($request);
        }

        return $next($request);
    }
}
