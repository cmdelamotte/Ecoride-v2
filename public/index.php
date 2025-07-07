<?php

// Front-controller

// 1. Démarrage de la session
session_start();

// 2. Chargement de l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Chargement des variables d'environnement
use App\Core\DotEnv;
DotEnv::load(__DIR__ . '/../');

// 4. Chargement du routeur
$router = new App\Core\Router();

// 5. Lancement de l'application
$router->run();