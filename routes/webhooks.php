<?php

use Illuminate\Support\Facades\Route;
use PostRun\Laravel\Http\Controllers\WebhookController;
use PostRun\Laravel\Http\Middleware\VerifyPostRunSignature;

Route::middleware(array_merge(
    config('postrun.webhook.middleware', ['api']),
    [VerifyPostRunSignature::class]
))->group(function () {
    Route::post(
        config('postrun.webhook.path', '/postrun/webhooks'),
        [WebhookController::class, 'handle']
    )->name('postrun.webhooks');
});
