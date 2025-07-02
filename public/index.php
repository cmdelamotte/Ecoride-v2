<?php

// Front-controller

// 1. Démarrage de la session
session_start();

// 2. Chargement de l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Chargement du routeur
$router = new App\Core\Router();

// 4. Lancement de l'application
$router->run();