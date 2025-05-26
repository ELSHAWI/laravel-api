<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    public function handleCheckoutSessionCompleted($payload)
    {
        // Handle successful payment
        $session = $payload['data']['object'];
        $user = User::find($session['metadata']['user_id']);
        
        if ($user) {
            $user->update([
                'plan_type' => $session['metadata']['plan_id'],
                'plan_status' => 'active',
                'plan_expires_at' => now()->addYear()
            ]);
        }
        
        return $this->successMethod();
    }

    public function handleInvoicePaymentSucceeded($payload)
    {
        // Handle recurring payment success
        $invoice = $payload['data']['object'];
        $user = User::where('stripe_id', $invoice['customer'])->first();
        
        if ($user) {
            $user->update([
                'plan_status' => 'active',
                'plan_expires_at' => now()->addYear()
            ]);
        }
        
        return $this->successMethod();
    }

    public function handleInvoicePaymentFailed($payload)
    {
        // Handle payment failure
        $invoice = $payload['data']['object'];
        $user = User::where('stripe_id', $invoice['customer'])->first();
        
        if ($user) {
            $user->update([
                'plan_status' => 'payment_failed'
            ]);
        }
        
        return $this->successMethod();
    }
}