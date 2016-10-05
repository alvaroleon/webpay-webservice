<?php
require_once 'libs/php-wss-validation-master/wss/xmlseclibs.php';
require_once 'libs/php-wss-validation-master/wss/soap-wsse.php';
require_once 'libs/php-wss-validation-master/wss/soap-validation.php';

define('PRIVATE_KEY', dirname(__FILE__) . '/certs/597020000541.key');
define('CERT_FILE', dirname(__FILE__) . '/certs/597020000541.crt');
define('SERVER_CERT', dirname(__FILE__) . '/certs/tbk.pem');

require_once 'WebpaySoapClient.php';
require_once 'WebpayService.php';
require_once 'WebpayNormalTransaction.php';