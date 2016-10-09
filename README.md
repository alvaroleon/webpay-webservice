# Normal Transaction Webpay Plus Webservice for PHP

`Current version: 0.8.`

Port para Webpay Plus Webservice 2016 para PHP (En desarrollo).

//Port for Webpay Plus Webservice 2016 for PHP (On development).//

PHP Version: 5.4 or higher.

Esta versión está basada en el código que aparece en el manual de Transbank "02 Referencia API SOAP Webpay Transaccion Normal"

La idea principal es que esta abstracción sea muy facil de usar, por ello, se procuró de que el desarrollador ocupe la menor
cantidad de código posible.

¿Cómo ocuparlo?
-
Para empezar a ocupar el plugin debes editar el archivo `autoload.php` que contiene la información de certificados y carga
todos los scripts necesarios para la implementación.

```php
<?php
 require 'libs/php-wss-validation-master/wss/xmlseclibs.php';
 require 'libs/php-wss-validation-master/wss/soap-wsse.php';
 require 'libs/php-wss-validation-master/wss/soap-validation.php';
 
 define('PRIVATE_KEY', dirname(__FILE__) . '/certs/597020000541.key'); 
 define('CERT_FILE', dirname(__FILE__) . '/certs/597020000541.crt');
 define('SERVER_CERT', dirname(__FILE__) . '/certs/tbk.pem');
 
 require 'WebpaySoapClient.php'; //Abstracción de SoapClient para WebpayPlus
 require 'WebpayService.php'; //Servicio WebpayPlus
 require 'WebpayNormalTransaction.php'; //Transacciones`
 ```
 
 Las constantes `PRIVATE_KEY`, `CERT_FILE` y `SERVER_CERT` deben tener como valor el directorio del archivo correspondiente.
 
 _Nota: Las librerías ocupadas en las primeras 3 lineas corresponden a las recomendadas en el manual de Transbank._
 
 Luego de editar el archivo de configuración `autoload.php`, debemos incluirlo en nuestro código. Agregando:
 
 Ejemplo del archivo `test/test.php`:

  
  Luego de incluir el archivo en nuestro código estamos listos para empezar a ocupar la librería.
  
  En esta versión, por ser inicial, hay 2 métodos principales que representan los pasos más importantes dentro del proceso de
  transacción.
  
  La clase principal de esta librería es `WebpayNormalTransaction`, la cual incluye los 2 métodos previamente mencionados.
  
  Los métodos principales son:
  1. `init_transaction()`: Comienzo de transacción y redireccionamiento a Transbank WebpayPlus.
  2. `auth()`. Verificación de autentificación con banco y validación y comparación de datos de comercio y Transbank.
  Al procesar el resultado de la operación, redireccionará a una página de exito o fracaso.
  
  _Nota: Ambas clases hacen redirecciones automáticas._
  
  Comenzar una transacción
  -
  
  Veremos un ejemplo sencillo para comenzar una transacción (lo pueden ver en el archivo `test/test.php`):

```php
   <?php
   use \EstudioMoca\WebpayWebService\WebpayNormalTransaction;
   
    if (!session_id()) {
        session_start();
    }    
    
    $wp = new WebpayNormalTransaction([
        'auth_url' => 'http://localhost/webpay-webservice/test/auth.php',
        'final_url' => 'http://localhost/webpay-webservice/test/success.php',
    ]);
    
    $wp->init_transaction([
        'session_id' => session_id(),
        'order_id' => '1234',
        'amount' => '1234'
    ]);
```
Es importante use para importar el la clase.

Para inicializar el objeto es necesario ingresar los parámetros, estos van a variar según el contexto del proceso 
(init_transaction() o auth())

El parámetro principal es un array con los datos principales, los datos pueden ser los siguientes:


```php
$args = [
            'auth_url', // (obligatorio para init_transaction()) URL donde se realizará el proceso de autentificación.
            'final_url', // (obligatorio para init_transaction()) URL de exito o fracaso (1)
            'commerce_code' //(opcional para pruebas) Código de comercio
            'commerce_id' , // Para retail llenar este campo que corresponde a la id del comercio.
            'token_ws', // (obligatorio para auth())
            'webservice_url'// URL de webservice, valor predeterminado: 'https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl'
        ];
```

(1) En caso de transaccion fallida, se enviará el parámetro por método GET `success=false`

Autentificación y validación de transacción
-
Como lo indica el manual de Transbank (adjunto en `doc/WS_TR_NORMAL/Manuales/02_Referencia_API_SOAP_Webpay_Transaccion_Normal.pdf`)
en el archivo de autentificación que creemos, se recibirá la variable `$_POST['token_ws']`, que tendrá como valor el token
generado internamente en el paso anterior (init_transaction()).

En este caso, cuando instanciemos el objeto, es necesario dar como parámetro solo el token previamente descrito.

Este es un ejemplo sacado de `test/auth.php`:
```php
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
```

En este ejemplo vemos que auth() tiene como parámetro un array, éste deve contener los valores que vamos a comparar con
los traidos por Transbank. En el caso de que los valores no coincidan, se redireccionará automáticamente a la página de
fracaso.

Descripción de auth():

```php
auth($data_compare, $pdo = null, $table_name = 'webpay', $prefix_table = '')
```

1. (`array) $data_compare`: Datos a comparar con los traidos desde Transbank. Estos son los indices del array:
```php
$data_compare['amount']; // Monto local total de la orden de compra.
$data_compare['order_id']; // ID de orden de compra.
$data_compare['session_id']; // Sesión.
```
En el caso que estos datos no coincidan con los de Transbank, se hará la redirección a la página de exito con el parámetro
`success=false`.
2. `(PDO) $pdo`: (opcional) Objeto instancia de PDO, sirve para guardar los datos en la base de datos. En caso de que el valor
sea nulo, no se guardarán datos en la db.

_Nota: Existe un método llamado `create_table($pdo, $table_name, $prefix)` que crea la tabla necesaria para guardar los datos,
Es necesario enviarle como parámetro una instancia de PDO._ 

3. `(string) $table_name`: (opcional) Es el nombre de la tabla donde se guardarán los datos.
4. `(string) $prefix_table`: (opcional) El prefijo de la tabla.

Este es el manual realizado hasta ahora, espero que les guste esta simple librería. Cualquier aporte, bienvenido sea.