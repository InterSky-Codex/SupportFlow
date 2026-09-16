<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Environment;
use App\Models\User;

if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from the command line.' . PHP_EOL);
}

Environment::load(dirname(__DIR__) . '/.env');

$config = require dirname(__DIR__) . '/config/database.php';
$database = new Database($config);
$userModel = new User($database);

$statement = $database->connection()->query("SELECT COUNT(*) FROM users WHERE role = 'administrator'");
if ((int) $statement->fetchColumn() > 0) {
    exit('An administrator already exists. Bootstrap aborted to prevent duplicates.' . PHP_EOL);
}

echo "SupportFlow Administrator Bootstrap\n";
echo "===================================\n";

$name = trim((string) readline("Enter administrator name: "));
$email = strtolower(trim((string) readline("Enter administrator email: ")));
$password = readline("Enter administrator password: ");

if ($name === '' || $email === '' || $password === '') {
    exit('All fields are required. Bootstrap aborted.' . PHP_EOL);
}

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    exit('Name must be between 2 and 100 characters. Bootstrap aborted.' . PHP_EOL);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Invalid email format. Bootstrap aborted.' . PHP_EOL);
}

if ($userModel->isEmailTaken($email)) {
    exit('An account with that email address already exists. Bootstrap aborted.' . PHP_EOL);
}

if (strlen($password) < 8) {
    exit('Password must be at least 8 characters. Bootstrap aborted.' . PHP_EOL);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
try {
    $userModel->create($name, $email, $passwordHash, 'administrator');
} catch (\PDOException) {
    exit('Administrator could not be created. Bootstrap aborted.' . PHP_EOL);
}

echo "\nAdministrator created successfully! You can now log in at /login\n";
