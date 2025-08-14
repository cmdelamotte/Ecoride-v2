<?php

// Renforcer la sécurité de la session (cookies) AVANT d'ouvrir la session
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();

// 2. Chargement de l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Chargement des variables d'environnement
use App\Core\DotEnv;
DotEnv::load(__DIR__ . '/../');

// 4. Chargement du routeur
use App\Core\Router;
$router = new Router();

// 5. Lancement de l'application
$router->run();