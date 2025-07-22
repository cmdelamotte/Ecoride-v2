<?php

namespace App\Exceptions;

use Exception;

/**
 * ValidationException
 * 
 * Exception personnalisée utilisée pour remonter les erreurs de validation
 * depuis la couche de service vers la couche de contrôleur.
 * Elle transporte un tableau d'erreurs, ce qui permet au contrôleur de renvoyer
 * une réponse JSON structurée au client.
 */
class ValidationException extends Exception
{
    protected array $errors;

    /**
     * Constructeur de l'exception.
     *
     * @param array $errors Le tableau des erreurs de validation (ex: ['field' => 'message']).
     * @param string $message Le message général de l'exception.
     * @param integer $code Le code d'erreur HTTP (généralement 422 Unprocessable Entity).
     * @param Exception|null $previous L'exception précédente.
     */
    public function __construct(array $errors, string $message = "Erreur de validation", int $code = 422, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Récupère le tableau des erreurs de validation.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
