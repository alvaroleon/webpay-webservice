<?php
/**
 * Created by PhpStorm.
 * User: alvar
 * Date: 05-10-2016
 * Time: 16:11
 */

namespace EstudioMoca\WebpayWebService;

use SoapClient;

/*abstract class WebpayWebService
{
    protected $commercialCode;
    protected $isCertification;

    /**
     * WebpayNormalTransaction constructor.
     * @param string $commercialCode
     * @param bool $isCertification
     *
    public function __construct($commercialCode, $isCertification = false)
    {
        $this->commercialCode = $commercialCode;
        $this->isCertification = $isCertification;

        $this->soapClient = new SoapClient($url, array("classmap" => self::$classmap, "trace" => true, "exceptions" => true))
    }


}*/


class getTransactionResult
{
    var $tokenInput;//string
}

class getTransactionResultResponse
{
    var $return;//transactionResultOutput
}

class transactionResultOutput
{
    var $accountingDate;//string
    var $buyOrder;//string
    var $cardDetail;//cardDetail
    var $detailOutput;//wsTransactionDetailOutput
    var $sessionId;//string
    var $transactionDate;//dateTime
    var $urlRedirection;//string
    var $VCI;//string
}

class cardDetail
{
    var $cardNumber;//string
    var $cardExpirationDate;//string
}

class wsTransactionDetailOutput
{
    var $authorizationCode;//string
    var $paymentTypeCode;//string
    var $responseCode;//int
}

class wsTransactionDetail
{
    var $sharesAmount;//decimal
    var $sharesNumber;//int
    var $amount;//decimal
    var $commerceCode;//string
    var $buyOrder;//string
}

class acknowledgeTransaction
{
    var $tokenInput;//string
}

class acknowledgeTransactionResponse
{
}

class initTransaction
{
    var $wsInitTransactionInput;//wsInitTransactionInput
}

class wsInitTransactionInput
{
    var $wSTransactionType;//wsTransactionType
    var $commerceId;//string
    var $buyOrder;//string
    var $sessionId;//string
    var $returnURL;//anyURI
    var $finalURL;//anyURI
    var $transactionDetails;//wsTransactionDetail
    var $wPMDetail;//wpmDetailInput
}

class wpmDetailInput
{
    var $serviceId;//string
    var $cardHolderId;//string
    var $cardHolderName;//string
    var $cardHolderLastName1;//string
    var $cardHolderLastName2;//string
    var $cardHolderMail;//string
    var $cellPhoneNumber;//string
    var $expirationDate;//dateTime
    var $commerceMail;//string
    var $ufFlag;//boolean
}

class initTransactionResponse
{
    var $return;//wsInitTransactionOutput
}

class wsInitTransactionOutput
{
    var $token;//string
    var $url;//string
}

class WebpayService
{
    var $soapClient;

    private static $classmap = array('getTransactionResult' => 'EstudioMoca\WebpayWebService\getTransactionResult'
    , 'getTransactionResultResponse' => 'EstudioMoca\WebpayWebService\getTransactionResultResponse'
    , 'transactionResultOutput' => 'EstudioMoca\WebpayWebService\transactionResultOutput'
    , 'cardDetail' => 'EstudioMoca\WebpayWebService\cardDetail'
    , 'wsTransactionDetailOutput' => 'EstudioMoca\WebpayWebService\wsTransactionDetailOutput'
    , 'wsTransactionDetail' => 'EstudioMoca\WebpayWebService\wsTransactionDetail'
    , 'acknowledgeTransaction' => 'EstudioMoca\WebpayWebService\acknowledgeTransaction'
    , 'acknowledgeTransactionResponse' => 'EstudioMoca\WebpayWebService\acknowledgeTransactionResponse'
    , 'initTransaction' => 'EstudioMoca\WebpayWebService\initTransaction'
    , 'wsInitTransactionInput' => 'EstudioMoca\WebpayWebService\wsInitTransactionInput'
    , 'wpmDetailInput' => 'EstudioMoca\WebpayWebService\wpmDetailInput'
    , 'initTransactionResponse' => 'EstudioMoca\WebpayWebService\initTransactionResponse'
    , 'wsInitTransactionOutput' => 'EstudioMoca\WebpayWebService\wsInitTransactionOutput'

    );

    function __construct($url = 'https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl')
    {
        $this->soapClient = new WebpaySoapClient($url, array("classmap" => self::$classmap, "trace" => true, "exceptions" => true));
    }

    function getTransactionResult($getTransactionResult)
    {

        $getTransactionResultResponse = $this->soapClient->getTransactionResult($getTransactionResult);
        return $getTransactionResultResponse;

    }

    function acknowledgeTransaction($acknowledgeTransaction)
    {

        $acknowledgeTransactionResponse = $this->soapClient->acknowledgeTransaction($acknowledgeTransaction);
        return $acknowledgeTransactionResponse;

    }

    function initTransaction($initTransaction)
    {

        $initTransactionResponse = $this->soapClient->initTransaction($initTransaction);
        return $initTransactionResponse;

    }
}