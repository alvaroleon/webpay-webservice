<?php
/**
 * Created by PhpStorm.
 * User: alvar
 * Date: 05-10-2016
 * Time: 16:15
 */

namespace EstudioMoca\WebpayWebService;

use \SoapValidation;

class WebpayNormalTransaction
{
    public $args;
    public $token_ws;
    public $order_id;
    public $session_id;
    public $auth_url;
    public $final_url;
    public $amount;
    public $commerce_code;
    public $commerce_id; //For Mall Stores
    public $transactionResponse;

    /**
     * WebpayNormalTransaction constructor.
     * @throws WebpayException
     * @param array $args
     */
    public function __construct($args)
    {
        $args = [
            'transaction_type' => 'TR_NORMAL_WS',
            //'order_id' => '',
            //'session_id' => '',
            'auth_url' => $args['auth_url'],
            'final_url' => $args['final_url'],
            'commerce_code' => $args['commerce_code'] ? $args['commerce_code'] : '597020000541', // For test
            'commerce_id' => $args['commerce_id'] ? $args['commerce_id'] : false, // For Mall stores
            //'amount' => $args['amount'] ? $args['amount'] : 0,
            'token_ws' => $args['token_ws'] ? $args['token_ws'] : '',
            'action' => $args['action'] ? $args['action'] : '',
            'webservice_url' => $args['webservice_url'] ? $args['webservice_url'] : 'https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl'
        ];

        $this->args = $args;

        if (!$args['token_ws']) {
            $this->check_exceptions();
            $this->commerce_code = $args['commerce_code'];
            $this->auth_url = $args['auth_url'];
            $this->final_url = $args['final_url'];

            if ($args['commerce_id']) {
                $this->commerce_id = $args['commerce_id'];
            }

            $this->commerce_id = $args['commerce_id'];
        } else {
            $this->token_ws = $args['token_ws'];
        }
    }

    public function init_transaction($args)
    {
        /**
         * @var string $order_id
         * @var int $amount
         * @var string $auth_url
         * @var string $final_url
         * @var string $session_id
         */
        extract($args);

        $this->order_id = $order_id;
        $this->amount = $amount;
        $this->session_id = $session_id;

        $this->check_exceptions('init');

        $wsInitTransactionInput = new wsInitTransactionInput();
        $wsTransactionDetail = new wsTransactionDetail();
        $wsInitTransactionInput->wSTransactionType = $this->args['transaction_type'];

        if ($this->args['commerce_id']) {
            $wsInitTransactionInput->commerceId = $this->commerce_id;
        }

        $wsInitTransactionInput->buyOrder = $this->order_id;
        $wsInitTransactionInput->sessionId = $this->session_id;
        $wsInitTransactionInput->returnURL = $this->auth_url;
        $wsInitTransactionInput->finalURL = $this->final_url;
        $wsTransactionDetail->commerceCode = $this->commerce_code;
        $wsTransactionDetail->buyOrder = $this->order_id;
        $wsTransactionDetail->amount = $this->amount;

        $wsInitTransactionInput->transactionDetails = $wsTransactionDetail;

        $webpayService = new WebpayService($this->args['webservice_url']);
        $initTransactionResponse = $webpayService->initTransaction(
            ["wsInitTransactionInput" => $wsInitTransactionInput]
        );

        $xmlResponse = $webpayService->soapClient->__getLastResponse();
        $soapValidation = new SoapValidation($xmlResponse, SERVER_CERT);
        $validationResult = $soapValidation->getValidationResult();

        if ($validationResult) {
            $wsInitTransactionOutput = $initTransactionResponse->return;
            $tokenWebpay = $wsInitTransactionOutput->token;
            $urlRedirect = $wsInitTransactionOutput->url;

            $this->token_ws = $tokenWebpay;
            $this->render_form_redirect($urlRedirect);
        } else {
            throw new WebpayException("Bad certificate.", 6);
        }

        $wsInitTransactionInput->transactionDetails = $wsTransactionDetail;
    }

    /**
     * @param array $data_compare
     * @param \PDO $pdo
     * @param string $table_name
     * @param string $prefix_table
     * @throws WebpayException
     */
    public function auth($data_compare, $pdo = null, $table_name = 'webpay', $prefix_table = '')
    {
        /**
         * $data_compare = [
         * 'amount',
         * 'order_id',
         * 'session_id'
         * ]
         */
        echo $this->args['webservice_url'];

        $webpayService = new WebpayService($this->args['webservice_url']);
        $getTransactionResult = new getTransactionResult();
        $getTransactionResult->tokenInput = $this->token_ws;
        $getTransactionResultResponse = $webpayService->getTransactionResult($getTransactionResult);
        $soapValidation = new SoapValidation($webpayService->soapClient->__getLastResponse(), SERVER_CERT);
        $validationResult = $soapValidation->getValidationResult();

        if ($validationResult) {
            $transactionResultOutput = $getTransactionResultResponse->return;
            $url = $transactionResultOutput->urlRedirection;
            $wsTransactionDetailOutput = $transactionResultOutput->detailOutput;
            $authorizationCode = $wsTransactionDetailOutput->authorizationCode;
            $paymentTypeCode = $wsTransactionDetailOutput->paymentTypeCode;
            $responseCode = $wsTransactionDetailOutput->responseCode;
            $sharesNumber = $wsTransactionDetailOutput->sharesNumber; //Número de cuotas
            $this->amount = $wsTransactionDetailOutput->amount;
            $this->commerce_code = $wsTransactionDetailOutput->commerceCode;
            $this->session_id = $transactionResultOutput->sessionId;
            $transactionDate = $transactionResultOutput->transactionDate;
            $cardDetail = $transactionResultOutput->cardDetail;
            $cardNumber = $cardDetail->cardNumber;
            $this->order_id = $wsTransactionDetailOutput->buyOrder;

            if ($data_compare['amount'] != $this->amount || $data_compare['order_id'] != $this->order_id ||
                $data_compare['session_id'] != $this->session_id
            ) {
                header("Location: {$url}?success=false");
                echo '1';
                return;
            }

            if (get_class($pdo) == 'PDO') {
                $sql = "INSERT INTO {$prefix_table}{$table_name} SET 
                        token_ws = :tokenWs,
                        amount = :amount,
                        session_id = :sessionId,
                        response_code = :responseCode,
                        auth_code = :authCode, 
                        payment_code = :paymentCode,
                        transaction_date = :transactionDate,
                        card_number = :cardNumber,
                        shares_number = :sharesNumber,
                        order_id = :orderId";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':responseCode', $responseCode, \PDO::PARAM_STR);
                $stmt->bindParam(':authCode', $authorizationCode, \PDO::PARAM_STR);
                $stmt->bindParam(':paymentCode', $paymentTypeCode, \PDO::PARAM_STR);
                $stmt->bindParam(':transactionDate', $transactionDate, \PDO::PARAM_STR);
                $stmt->bindParam(':cardNumber', $cardNumber, \PDO::PARAM_STR);
                $stmt->bindParam(':sharesNumber', $sharesNumber, \PDO::PARAM_STR);
                $stmt->bindParam(':tokenWs', $this->token_ws, \PDO::PARAM_STR);
                $stmt->bindParam(':amount', $this->amount, \PDO::PARAM_STR);
                $stmt->bindParam(':sessionId', $this->session_id, \PDO::PARAM_STR);
                $stmt->bindParam(':orderId', $this->order_id, \PDO::PARAM_STR);

                $stmt->execute();
            }

            if ($wsTransactionDetailOutput->responseCode == 0) {
                $webpayService = new WebpayService($this->args['webservice_url']);
                $acknowledgeTransaction = new acknowledgeTransaction();
                $acknowledgeTransaction->tokenInput = $this->token_ws;

                try {
                    $acknowledgeTransactionResponse = $webpayService->acknowledgeTransaction($acknowledgeTransaction);
                    $xmlResponse = $webpayService->soapClient->__getLastResponse();
                    $soapValidation = new SoapValidation($xmlResponse, SERVER_CERT);
                    $validationResult = $soapValidation->getValidationResult();

                    if ($validationResult) {
                        $this->render_form_redirect($url);
                        return;
                    } else {
                        throw new WebpayException("Bad certificate.", 6);
                    }
                } catch (\Exception $e) {
                    $this->render_form_redirect($url . '?success=false');
                    return;
                }
            } else {
                $this->render_form_redirect($url . '?success=false');
                return;
            }
        } else {
            throw new WebpayException("Bad certificate.", 6);
        }
    }

    /**
     * Guarda en la base de datos en caso que se requiera.
     * @param \PDO $pdo
     * @param string $table_name
     * @param string $prefix_table
     */
    public function save_db($pdo, $table_name = 'webpay', $prefix_table = '')
    {
        $sql = "INSERT INTO {$prefix_table}{$table_name} (
            order_id,
            token_ws,
            transaction_response,
            transaction_type,
            amount,
            session_id) VALUES (
            :orderId, 
            :tokenWs, 
            :transactionResponse, 
            :transactionType, 
            :amount, 
            :sessionId)";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':orderId', $this->order_id, \PDO::PARAM_STR);
        $stmt->bindParam(':tokenWs', $this->token_ws, \PDO::PARAM_STR);
        $stmt->bindParam(':transactionResponse', $this->transactionResponse, \PDO::PARAM_STR);
        $stmt->bindParam(':transactionType', $this->args['transaction_type'], \PDO::PARAM_STR);
        $stmt->bindParam(':amount', $this->amount, \PDO::PARAM_INT);
        $stmt->bindParam(':sessionId', $this->session_id, \PDO::PARAM_STR);

        $stmt->execute();
    }

    private function render_form_redirect($url)
    {
        $str = '<!doctype><html><head>
<script>
function init_transaction() {
  document.getElementById("form_transaction").submit();
}
</script>
</head>
<body onload="init_transaction();">
<form id="form_transaction" method="post" action="' . $url . '">
<input type="hidden" name="token_ws" value="' . $this->token_ws . '">
</form>
</body></html>';
        echo $str;
    }

    private function check_exceptions($type = 'constructor')
    {
        if ($type == 'constructor') {
            if (!$this->args['commerce_code']) {
                throw new WebpayException('Commerce Code is required', 5);
            }

            if (!$this->args['auth_url']) {
                throw new WebpayException('Auth URL is required', 3);
            }

            if (!$this->args['final_url']) {
                throw new WebpayException('Final URL is required', 4);
            }
        }

        if ($type == 'init') {
            if (!$this->order_id) {
                throw new WebpayException('Order ID is required', 1);
            }

            if (!$this->session_id) {
                throw new WebpayException('Session ID is required', 2);
            }


        }
    }
}

class WebpayException extends \Exception
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}