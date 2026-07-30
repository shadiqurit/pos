<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Stripe extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index()
    {
        $this->load->view('paypal_test/stripe', [
            'stripe_publishable_key' => (string) env('stripe.publishableKey', ''),
        ]);
    }

    public function payment()
    {
        require_once LEGACY_APPPATH . 'libraries/stripe/init.php';

        $stripeSecret = (string) env('stripe.apiKey', '');
        if ($stripeSecret === '') {
            legacy_show_error('Stripe is not configured.');
        }

        \Stripe\Stripe::setApiKey($stripeSecret);

        $stripe = \Stripe\Charge::create([
            'amount'      => $this->input->post('amount'),
            'currency'    => (string) env('stripe.currency', 'usd'),
            'source'      => $this->input->post('tokenId'),
            'description' => 'POS payment',
        ]);

        echo json_encode(['success' => true, 'data' => $stripe]);
    }
}

