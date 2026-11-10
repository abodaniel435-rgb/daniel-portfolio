<?php
declare(strict_types=1);

require dirname(__DIR__) . '/core/bootstrap.php';

// The shared core hardcodes one session cookie name, so other ForgeStack apps
// on the same host can collide with this one. Tag the session and start clean
// if the cookie belongs to a different application.
if (($_SESSION['_app'] ?? '') !== 'routepilot') {
    $_SESSION = ['_app' => 'routepilot'];
}

if (!Installer::installed()) {
    redirect('/install.php');
}

$router = new Router();

require BASE_PATH . '/app/services.php';
foreach (['home', 'auth', 'dispatcher', 'courier', 'admin'] as $controller) {
    require BASE_PATH . '/app/controllers/' . $controller . '.php';
}
require BASE_PATH . '/app/routes.php';

$router->dispatch();
