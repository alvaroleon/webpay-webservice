<?php
include '../src/autoload.php';
/*ini_set('display_errors', 1);
error_reporting(E_ALL);*/
use EstudioMoca\WebpayWebService\WebpayService,
    EstudioMoca\WebpayWebService\getTransactionResult,
    EstudioMoca\WebpayWebService\acknowledgeTransaction,
    EstudioMoca\WebpayWebService\WebpayNormalTransaction;

if (!session_id()) {
    session_start();
}

$wp = new WebpayNormalTransaction(['token_ws' => $_POST['token_ws']]);
$wp->auth([
    'session_id' => session_id(),
    'order_id' => '1234',
    'amount' => '1234'
]);

exit;
$webpayService = new WebpayService('https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl');
$getTransactionResult = new getTransactionResult();
$getTransactionResult->tokenInput = $_POST['token_ws'];
$getTransactionResultResponse = $webpayService->getTransactionResult($getTransactionResult);
//Todo: Se pide invocar solo una vez, guardar en la base de datos.
//Guardar en la db
//$transactionResultOutput = $getTransactionResultResponse->return;
/*Validación de firma del requerimiento de respuesta enviado por Webpay*/
$soapValidation = new SoapValidation($webpayService->soapClient->__getLastResponse(), SERVER_CERT);
$validationResult = $soapValidation->getValidationResult();

/* Validación de firma correcta */
if ($validationResult) {
    $transactionResultOutput = $getTransactionResultResponse->return;
    /*URL donde se debe continuar el flujo*/
    $url = $transactionResultOutput->urlRedirection;
    $wsTransactionDetailOutput = $transactionResultOutput->detailOutput;
    /*Código de autorización*/
    $authorizationCode = $wsTransactionDetailOutput->authorizationCode;
    /*Tipo de Pago*/
    $paymentTypeCode = $wsTransactionDetailOutput->paymentTypeCode;
    /*Código de respuesta*/
    $responseCode = $wsTransactionDetailOutput->responseCode;
    /*Número de cuotas*/
    $sharesNumber = $wsTransactionDetailOutput->sharesNumber;
    /*Monto de la transacción*/
    $amount = $wsTransactionDetailOutput->amount;
    /*Código de comercio*/
    $commerceCode = $wsTransactionDetailOutput->commerceCode;

    /*Orden de compra enviada por el comercio al inicio de la transacción*/
    $buyOrder = $wsTransactionDetailOutput->buyOrder;

   /* print_r($transactionResultOutput);
    print_r($wsTransactionDetailOutput);exit;*/

   /* print_r($getTransactionResultResponse);
    print_r($wsTransactionDetailOutput);
    print_r($url);*/

    if ($wsTransactionDetailOutput->responseCode == 0) { // 0 para OK, -1 Para rechazada
        /* Esto indica que la transacción está autorizada */

        $webpayService = new WebpayService('https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl');
        $acknowledgeTransaction = new acknowledgeTransaction();
        $acknowledgeTransaction->tokenInput = $_POST['token_ws'];

        try {
            $acknowledgeTransactionResponse = $webpayService->acknowledgeTransaction($acknowledgeTransaction);
            $xmlResponse = $webpayService->soapClient->__getLastResponse();
            $soapValidation = new SoapValidation($xmlResponse, SERVER_CERT);
            $validationResult = $soapValidation->getValidationResult();


            print_r($acknowledgeTransactionResponse); exit;

        } catch (Exception $e) {
            echo 'error';
        }
    }
}
?>
<!doctype html>
<html>
<head></head>
<body>
<form method="POST" action="<?php echo $url; ?>">
    <?php print_r($wsTransactionDetailOutput->responseCode); ?>
    <input name="token_ws" value="<?php echo $getTransactionResult->tokenInput; ?>">
    <button type="submit">Enviar</button>
</form>
</body>
</html>
