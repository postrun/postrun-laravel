<?php

namespace PostRun\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPostRunSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-PostRun-Signature');
        $timestamp = $request->header('X-PostRun-Timestamp');
        $secret = config('postrun.webhook.secret');

        if (! $signature || ! $timestamp || ! $secret) {
            abort(401, 'Missing webhook signature');
        }

        // Check timestamp tolerance (prevent replay attacks)
        $tolerance = config('postrun.webhook.tolerance', 300);
        if (abs(time() - (int) $timestamp) > $tolerance) {
            abort(401, 'Webhook timestamp expired');
        }

        // Verify signature
        $payload = $timestamp.'.'.$request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
