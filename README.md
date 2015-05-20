# php-json-rpc

A PHP JSON-RPC client/server implementation compatible with JSON-RPC 1.0 and 2.0

## Example client usage

```php
require_once('JsonRpcClient.php');

$car = new JsonRpcClient('http://username:password@address:port/');

try {
	$car->drive()
}
catch (Exception $e) { 
    echo $e->getMessage();
}
```

## Example server usage

```php
require_once('JsonRpcServer.php');

class Car {
	function drive() {
		// do something
	}
}

$server = new Car();

JsonRpcServer::handle($server);
```
