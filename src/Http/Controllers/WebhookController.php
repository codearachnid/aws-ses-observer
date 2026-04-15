<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Http\Controllers;

use codearachnid\AwsSesObserver\Actions\ProcessWebhook;
use codearachnid\AwsSesObserver\Actions\VerifySnsSignature;
use codearachnid\AwsSesObserver\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function store(
        Request $request,
        string $sourceToken,
        VerifySnsSignature $verifySnsSignature,
        ProcessWebhook $processWebhook,
    ): JsonResponse {
        $source = Source::where('token', $sourceToken)->firstOrFail();

        $verifySnsSignature->execute($request);

        $snsType = $request->header('x-amz-sns-message-type', $request->input('Type', ''));
        $payload = $request->all();

        return match ($snsType) {
            'SubscriptionConfirmation' => $this->handleSubscriptionConfirmation($payload),
            'Notification' => $this->handleNotification($payload, $source, $processWebhook),
            'UnsubscribeConfirmation' => $this->handleUnsubscribeConfirmation(),
            default => new JsonResponse(['error' => 'Unknown SNS message type.'], 400),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSubscriptionConfirmation(array $payload): JsonResponse
    {
        $subscribeUrl = $payload['SubscribeURL'] ?? null;

        if ($subscribeUrl) {
            Http::get($subscribeUrl);
        }

        return new JsonResponse(['message' => 'Subscription confirmed.'], 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleNotification(array $payload, Source $source, ProcessWebhook $processWebhook): JsonResponse
    {
        $processWebhook->execute($payload, $source);

        return new JsonResponse(['message' => 'Notification processed.'], 200);
    }

    private function handleUnsubscribeConfirmation(): JsonResponse
    {
        Log::info('SES Observer: SNS unsubscribe confirmation received.');

        return new JsonResponse(['message' => 'Unsubscribe acknowledged.'], 200);
    }
}
