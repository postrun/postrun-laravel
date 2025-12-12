<?php

namespace PostRun\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PostRun\Laravel\Events\MessageBounced;
use PostRun\Laravel\Events\MessageClicked;
use PostRun\Laravel\Events\MessageComplained;
use PostRun\Laravel\Events\MessageDelivered;
use PostRun\Laravel\Events\MessageOpened;
use PostRun\Laravel\Events\MessageRejected;
use PostRun\Laravel\Events\MessageSent;
use PostRun\Laravel\Events\PostRunWebhookReceived;

class WebhookController
{
    /**
     * Handle incoming PostRun webhooks.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = $request->header('X-PostRun-Event');

        // Dispatch base event for catch-all listeners
        event(new PostRunWebhookReceived($payload, $eventType));

        // Dispatch specific event based on type
        $event = match ($eventType) {
            'message.sent' => new MessageSent($payload),
            'message.delivered' => new MessageDelivered($payload),
            'message.bounced' => new MessageBounced($payload),
            'message.complained' => new MessageComplained($payload),
            'message.rejected' => new MessageRejected($payload),
            'message.opened' => new MessageOpened($payload),
            'message.clicked' => new MessageClicked($payload),
            default => null,
        };

        if ($event) {
            event($event);
        }

        return response()->json(['status' => 'ok']);
    }
}
