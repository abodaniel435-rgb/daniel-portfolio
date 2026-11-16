<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

if (!Installer::installed()) {
    redirect('/install.php');
}

$router = new Router();

// services.php pulls in app/engines/Pricing.php, so the engine is not required
// again here — a second plain require would redeclare the class.
require BASE_PATH . '/app/services.php';
foreach (['home', 'auth', 'record', 'admin'] as $controller) {
    require BASE_PATH . '/app/controllers/' . $controller . '.php';
}
require BASE_PATH . '/app/routes.php';

$router->dispatch();
