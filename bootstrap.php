<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Environment;
use App\Core\Router;
use App\Core\View;

define('BASE_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';

Environment::load(BASE_PATH . '/.env');

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

ini_set('display_errors', $appConfig['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

session_name('supportflow_session');
session_save_path(BASE_PATH . '/storage/sessions');
session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

$router = new Router();
require BASE_PATH . '/routes/web.php';

return new Application(BASE_PATH, $router, new View(BASE_PATH . '/resources/views'));
