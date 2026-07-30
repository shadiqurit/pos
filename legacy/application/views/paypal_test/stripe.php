<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Stripe Payment</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
  <style>
    .container { padding: 0.5%; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row">
      <div class="col-md-12"><pre id="token_response"></pre></div>
    </div>
    <div class="row">
      <div class="col-md-4">
        <button class="btn btn-primary btn-block" onclick="pay(100)">Pay $100</button>
      </div>
      <div class="col-md-4">
        <button class="btn btn-success btn-block" onclick="pay(500)">Pay $500</button>
      </div>
      <div class="col-md-4">
        <button class="btn btn-info btn-block" onclick="pay(1000)">Pay $1000</button>
      </div>
    </div>
  </div>

  <script src="https://checkout.stripe.com/checkout.js"></script>
  <script>
    function pay(amount) {
      const publishableKey = <?= json_encode((string) ($stripe_publishable_key ?? '')) ?>;
      if (!publishableKey) {
        $('#token_response').text('Stripe is not configured.');
        return;
      }

      const handler = StripeCheckout.configure({
        key: publishableKey,
        locale: 'auto',
        token: function (token) {
          $('#token_response').text(JSON.stringify(token));
          $.ajax({
            url: <?= json_encode(base_url('stripe/payment')) ?>,
            method: 'post',
            data: {
              tokenId: token.id,
              amount: amount,
              <?= json_encode(csrf_token()) ?>: <?= json_encode(csrf_hash()) ?>
            },
            dataType: 'json',
            success: function (response) {
              $('#token_response').append('\n' + JSON.stringify(response.data));
            }
          });
        }
      });

      handler.open({
        name: 'POS',
        description: 'Payment',
        amount: amount * 100
      });
    }
  </script>
</body>
</html>

