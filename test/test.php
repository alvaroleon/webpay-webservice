<?php
/*ini_set('display_errors', 1);
error_reporting(E_ALL);*/


include '../src/autoload.php';

if (!session_id()) {
    session_start();
}

use EstudioMoca\WebpayWebService\WebpayService,
    EstudioMoca\WebpayWebService\wsInitTransactionInput,
    EstudioMoca\WebpayWebService\wsTransactionDetail,
    \EstudioMoca\WebpayWebService\WebpayNormalTransaction;


$wp = new WebpayNormalTransaction([
    //'commerce_code' => '597020000541',
    'auth_url' => 'http://localhost/webpay-webservice/test/auth.php',
    'final_url' => 'http://localhost/webpay-webservice/test/success.php',
]);

$wp->init_transaction([
    'session_id' => session_id(),
    'order_id' => '1234',
    'amount' => '1234'
]);

exit;

$wsInitTransactionInput = new wsInitTransactionInput();
$wsTransactionDetail = new wsTransactionDetail();
/*Variables de tipo string*/
$wsInitTransactionInput->wSTransactionType = 'TR_NORMAL_WS';
//$wsInitTransactionInput->commerceId = '597020000541'; // Ocupado solo para MALL
$wsInitTransactionInput->buyOrder = '123';
$wsInitTransactionInput->sessionId = '12345';
$wsInitTransactionInput->returnURL = 'http://localhost/webpay-webservice/test/auth.php';
$wsInitTransactionInput->finalURL = 'http://localhost/webpay-webservice/test/success.php';
$wsTransactionDetail->commerceCode = '597020000541';
$wsTransactionDetail->buyOrder = '123';
$wsTransactionDetail->amount = 100;

$wsInitTransactionInput->transactionDetails = $wsTransactionDetail;

$webpayService = new WebpayService('https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl');
$initTransactionResponse = $webpayService->initTransaction(
    array("wsInitTransactionInput" => $wsInitTransactionInput)
);
/*Validación de firma del requerimiento de respuesta enviado por Webpay*/
$xmlResponse = $webpayService->soapClient->__getLastResponse();
$soapValidation = new SoapValidation($xmlResponse, SERVER_CERT);
$validationResult = $soapValidation->getValidationResult();
/*Invocar sólo sí $validationResult es TRUE*/
if ($validationResult) {
    $wsInitTransactionOutput = $initTransactionResponse->return;
    /*TOKEN de Transacción entregado por Webpay*/
    $tokenWebpay = $wsInitTransactionOutput->token;
    /*URL donde se debe continuar el flujo*/
    $urlRedirect = $wsInitTransactionOutput->url;
 /*   header("Location: {$urlRedirect}");
    echo $tokenWebpay . "\n";
    echo $urlRedirect . "\n";*/
    //
}
?>
<html>
<head>

</head>
<body>
<form method="POST" action="<?php echo $urlRedirect; ?>">
    <input name="token_ws" value="<?php echo $tokenWebpay; ?>">
    <button type="submit">Enviar</button>
</form>
</body>
</html>
