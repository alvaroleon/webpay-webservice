<?php
include '../src/autoload.php';

if (!session_id()) {
    session_start();
}

use \EstudioMoca\WebpayWebService\WebpayNormalTransaction;


$wp = new WebpayNormalTransaction([
    'auth_url' => 'http://localhost/webpay-webservice/test/auth.php',
    'final_url' => 'http://localhost/webpay-webservice/test/success.php',
]);

$wp->init_transaction([
    'session_id' => session_id(),
    'order_id' => '1234',
    'amount' => '1234'
]);