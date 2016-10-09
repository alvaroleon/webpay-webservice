# Normal Transaction Webpay Plus Webservice for PHP

Port para Webpay Plus Webservice 2016 para PHP (En desarrollo).

//Port for Webpay Plus Webservice 2016 for PHP (On development).//

PHP Version: 5.4 or higher.

Esta versión está basada en el código que aparece en el manual de Transbank "02 Referencia API SOAP Webpay Transaccion Normal"

¿Cómo ocuparlo?
-
Para empezar a ocupar el plugin debes editar el archivo `autoload.php` que contiene la información de certificados y carga
todos los scripts necesarios para la implementación.

```php
<?php
 require_once 'libs/php-wss-validation-master/wss/xmlseclibs.php';
 require_once 'libs/php-wss-validation-master/wss/soap-wsse.php';
 require_once 'libs/php-wss-validation-master/wss/soap-validation.php';
 
 define('PRIVATE_KEY', dirname(__FILE__) . '/certs/597020000541.key'); 
 define('CERT_FILE', dirname(__FILE__) . '/certs/597020000541.crt');
 define('SERVER_CERT', dirname(__FILE__) . '/certs/tbk.pem');
 
 require_once 'WebpaySoapClient.php'; //Abstracción de SoapClient para WebpayPlus
 require_once 'WebpayService.php'; //Servicio WebpayPlus
 require_once 'WebpayNormalTransaction.php'; //Transacciones`
 ```
 
 Las constantes `PRIVATE_KEY`, `CERT_FILE` y `SERVER_CERT` deben tener como valor el directorio del archivo correspondiente.
 
 _Nota: Las librerías ocupadas en las primeras 3 lineas corresponden a las recomendadas en el manual de Transbank._
 
 Luego de editar el archivo de configuración autoload.php, debemos incluirlo en nuestro código, en la parte que lo necesitemos
 ocupar.
 