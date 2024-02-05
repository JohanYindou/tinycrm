<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeService
{
    public function makePayment($apiKey, $amount, $product, $email)
    {
        Stripe::setApiKey($apiKey);
        header('Content-Type: application/json');

        $YOUR_DOMAIN = 'http://127.0.0.1:8000';

        $checkout_session = Session::create([
            'customer_email' => $email,
            'submit_type' => 'pay',
            'billing_address_collection' => 'required',
            'line_items' => [[
                'price_data'=>[
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product
                    ],
                    'unit_amount' => $amount * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/payment/success',
            'cancel_url' => $YOUR_DOMAIN . '/payment/cancel',
        ]);

        header("HTTP/1.1 303 See Other");
        return $checkout_session->url;
    }
}