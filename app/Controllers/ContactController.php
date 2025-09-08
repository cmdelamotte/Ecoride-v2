<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\RequestHelper;
use App\Services\ContactService;
use App\Exceptions\ValidationException;
use \Exception;

/**
 * ContactController
 * 
 * Gère les actions liées au formulaire de contact.
 */
class ContactController extends Controller
{
    private ContactService $contactService;

    public function __construct()
    {
        $this->contactService = new ContactService();
    }

    /**
     * Affiche le formulaire de contact.
     */
    public function index()
    {
        $this->render('contact', ['pageTitle' => 'Nous Contacter']);
    }

    /**
     * Gère la soumission du formulaire de contact.
     */
    public function submit()
    {
        $data = RequestHelper::getPublicJsonData();

        try {
            $this->contactService->submitContactForm($data);
            $this->jsonResponse(['success' => true, 'message' => 'Votre message a bien été envoyé ! Nous vous répondrons dès que possible.']);
        } catch (ValidationException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()], $e->getCode());
        } catch (Exception $e) {
            error_log("Contact form submission failed: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer plus tard.'], 500);
        }
    }
}
