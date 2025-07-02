<?php

namespace App\Core;

class Controller
{
    protected function render($viewName, $data = [])
    {
        extract($data); // Extrait les données pour les rendre accessibles dans la vue

        ob_start(); // Commence la mise en mémoire tampon de la sortie
        require __DIR__ . '/../Views/' . $viewName . '.php';
        $content = ob_get_clean(); // Récupère le contenu et termine la mise en mémoire tampon

        // Inclut le layout principal et injecte le contenu
        require __DIR__ . '/../Views/layout.php';
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}