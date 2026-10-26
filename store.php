<?php
/**
 * Public front controller. Apache rewrites the store URLs here.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new App\Router();
require __DIR__ . '/app/routes.php';
$router->dispatch();
