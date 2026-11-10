<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

if (!Installer::installed()) {
    redirect('/install.php');
}

$router = new Router();

require BASE_PATH . '/app/services.php';
foreach (['home', 'auth', 'patient', 'doctor', 'admin'] as $controller) {
    require BASE_PATH . '/app/controllers/' . $controller . '.php';
}
require BASE_PATH . '/app/routes.php';

$router->dispatch();
