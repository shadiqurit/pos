<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Stripe API Configuration
| -------------------------------------------------------------------
| Keep credentials in .env. Never commit live or test keys.
*/

$config['stripe_api_key']         = env('stripe.apiKey', '');
$config['stripe_publishable_key'] = env('stripe.publishableKey', '');
$config['stripe_currency']        = env('stripe.currency', 'usd');

