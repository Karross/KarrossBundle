<?php

use Symfony\Component\HttpFoundation\Request;
use TestedApp\Kernel;

require __DIR__.'/../../../../vendor/autoload.php';

$kernel = new Kernel('serve_with_config', true, [
    __DIR__.'/../config/doctrine_serve.php',
    __DIR__.'/../config/karross_with_config.php',
]);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
