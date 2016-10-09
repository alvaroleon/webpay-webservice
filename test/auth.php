<?php
include '../src/autoload.php';

use EstudioMoca\WebpayWebService\WebpayNormalTransaction;

if (!session_id()) {
    session_start();
}

$wp = new WebpayNormalTransaction(['token_ws' => $_POST['token_ws']]);

$wp->auth([
    'session_id' => session_id(),
    'order_id' => '1234',
    'amount' => '1234'
]);
