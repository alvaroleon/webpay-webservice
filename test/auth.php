<?php
include '../src/autoload.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
use EstudioMoca\WebpayWebService\WebpayService,
    EstudioMoca\WebpayWebService\getTransactionResult,
    EstudioMoca\WebpayWebService\acknowledgeTransaction;

$webpayService = new WebpayService('https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl');
$getTransactionResult = new getTransactionResult();
$getTransactionResult->tokenInput = $_POST['token_ws'];
$getTransactionResultResponse = $webpayService->getTransactionResult(
    $getTransactionResult);
$transactionResultOutput = $getTransactionResultResponse->return;
/*Validación de firma del requerimiento de respuesta enviado por Webpay*/
$xmlResponse = $webpayService->soapClient->__getLastResponse();
$soapValidation = new SoapValidation($xmlResponse, SERVER_CERT);
$validationResult = $soapValidation->getValidationResult();
if ($validationResult) {
    /* Validación de firma correcta */
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

    print_r($getTransactionResultResponse);
    print_r($wsTransactionDetailOutput);
    print_r($url);

    if ($wsTransactionDetailOutput->responseCode == 0) {
        /* Esto indica que la transacción está autorizada */

        $webpayService = new WebpayService('https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl');
        $acknowledgeTransaction = new acknowledgeTransaction();
        $acknowledgeTransaction->tokenInput = $_POST['token_ws'];
        try {
            $acknowledgeTransactionResponse = $webpayService->acknowledgeTransaction(
                $acknowledgeTransaction);
            $xmlResponse = $webpayService->soapClient->__getLastResponse();
            $soapValidation = new SoapValidation($xmlResponse, SERVER_CERT);
            $validationResult = $soapValidation->getValidationResult();

        } catch (Exception $e) {
            echo 'error';
        }

    }
}