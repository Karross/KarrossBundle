<?php

use Symfony\Component\HttpFoundation\Request;
use TestedApp\Kernel;

require __DIR__.'/../../../../vendor/autoload.php';

$kernel = new Kernel('serve', true, [
    __DIR__.'/../config/doctrine_serve.php',
]);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
