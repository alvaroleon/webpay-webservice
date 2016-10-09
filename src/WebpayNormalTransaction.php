<?php
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
            'auth_url' => $args['auth_url'],
            'final_url' => $args['final_url'],
            'commerce_code' => $args['commerce_code'] ? $args['commerce_code'] : '597020000541', // For test
            'commerce_id' => $args['commerce_id'] ? $args['commerce_id'] : false, // For Mall stores
            'token_ws' => $args['token_ws'] ? $args['token_ws'] : '',
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

    /**
     * Crea la tabla webpay a la base de datos
     * @param \PDO $pdo
     * @param string $table_name
     * @param string $prefix
     */
    public static function create_table($pdo, $table_name = 'webpay', $prefix = '')
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}{$table_name}` (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            buy_order VARCHAR(26) NOT NULL,
            payment_type_code VARCHAR(2) NOT NULL,
            response_code VARCHAR(1) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            card_number INT(16) NOT NULL,
            card_exp INT(4) NOT NULL,
            shares_number INT(1) NOT NULL,
            auth_code VARCHAR(6) NOT NULL,
            accouting_date VARCHAR(4) NOT NULL,
            transaction_date VARCHAR(6) NOT NULL,
            vci VARCHAR(3) NOT NULL,
            commerce_code VARCHAR(12) NOT NULL, 
            token_ws VARCHAR(64) NOT NULL,
            session_id VARCHAR(61)  NOT NULL)
            ENGINE=InnoDB";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $stmt = $pdo->prepare("ALTER TABLE `{$prefix}{$table_name}` ADD INDEX (buy_order)");
            $stmt->execute();
        } catch (\Exception $e) {
            die('Error creando la nase de datos: ' . print_r($e->getMessage(), true));
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
     * Autentificación de Transbank,
     * @param array $data_compare
     * @param \PDO $pdo
     * @param string $table_name
     * @param string $prefix_table
     * @throws WebpayException
     */
    public function auth($data_compare, $pdo = null, $table_name = 'webpay', $prefix_table = '')
    {
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
            $this->amount = $wsTransactionDetailOutput->amount;
            $this->commerce_code = $wsTransactionDetailOutput->commerceCode;
            $this->session_id = $transactionResultOutput->sessionId;
            $cardDetail = $transactionResultOutput->cardDetail;
            $this->order_id = $wsTransactionDetailOutput->buyOrder;

            if ($data_compare['amount'] != $this->amount || $data_compare['order_id'] != $this->order_id ||
                $data_compare['session_id'] != $this->session_id
            ) {
                header("Location: {$url}?success=false");
                return;
            }

            if (get_class($pdo) == 'PDO') {
                $sql = "INSERT INTO {$prefix_table}{$table_name} (
                            buy_order,
                            payment_type_code,
                            response_code,
                            amount,
                            card_number,
                            card_exp,
                            shares_number,
                            auth_code,
                            accouting_date,
                            transaction_date,
                            vci,
                            commerce_code,
                            token_ws,
                            session_id
                        ) VALUES (
                            :buyOrder,
                            :paymentTypeCode,
                            :responseCode,
                            :amount,
                            :cardNumber,
                            :cardExp,
                            :sharesNumber,
                            :authCode,
                            :accoutingDate,
                            :transactionDate,
                            :vci,
                            :commerceCode,
                            :tokenWs,
                            :sessionId
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':buyOrder', $this->order_id, \PDO::PARAM_STR);
                $stmt->bindParam(':paymentTypeCode', $wsTransactionDetailOutput->paymentTypeCode, \PDO::PARAM_STR);
                $stmt->bindParam(':responseCode', $wsTransactionDetailOutput->responseCode, \PDO::PARAM_STR);
                $stmt->bindParam(':amount', $this->amount, \PDO::PARAM_STR);
                $stmt->bindParam(':cardNumber', $cardDetail->cardNumber, \PDO::PARAM_STR);
                $stmt->bindParam(':cardExp', $cardDetail->cardExpirationDate, \PDO::PARAM_STR);
                $stmt->bindParam(':sharesNumber', $wsTransactionDetailOutput->sharesNumber, \PDO::PARAM_STR);
                $stmt->bindParam(':authCode', $wsTransactionDetailOutput->authorizationCode, \PDO::PARAM_STR);
                $stmt->bindParam(':accoutingDate', $transactionResultOutput->accoutingDate, \PDO::PARAM_STR);
                $stmt->bindParam(':transactionDate', $transactionResultOutput->transactionDate, \PDO::PARAM_STR);
                $stmt->bindParam(':vci', $transactionResultOutput->VCI, \PDO::PARAM_STR);
                $stmt->bindParam(':commerceCode', $this->commerce_code, \PDO::PARAM_STR);
                $stmt->bindParam(':tokenWs', $this->token_ws, \PDO::PARAM_STR);
                $stmt->bindParam(':sessionId', $this->session_id, \PDO::PARAM_STR);

                $stmt->execute();
            }

            if ($wsTransactionDetailOutput->responseCode == 0) {
                $webpayService = new WebpayService($this->args['webservice_url']);
                $acknowledgeTransaction = new acknowledgeTransaction();
                $acknowledgeTransaction->tokenInput = $this->token_ws;

                try {
                    $webpayService->acknowledgeTransaction($acknowledgeTransaction);
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

    protected function render_form_redirect($url)
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