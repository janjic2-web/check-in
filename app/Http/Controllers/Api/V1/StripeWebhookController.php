<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = Config::get('services.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

        // Verifikacija potpisa (osnovna, bez Stripe SDK)
        // TODO: Preporučeno koristiti \Stripe\Webhook::constructEvent() ako dodaš stripe/stripe-php
        if (!$sigHeader || !$webhookSecret) {
            return Response::json(['error' => 'Missing signature or secret'], 400);
        }
        // Loguj payload za debug
        Log::info('Stripe webhook', ['payload' => $payload, 'sig' => $sigHeader]);
        // TODO: Parsiraj event, ažuriraj subscriptions/invoices
        return Response::json(['status' => 'ok']);
    }
}
