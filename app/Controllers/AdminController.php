<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AdminService;
use Exception;

/**
 * Contrôleur pour la section d'administration.
 * Je gère ici les requêtes liées au tableau de bord de l'administrateur.
 * Mon rôle est de rester "mince" : je reçois les requêtes, j'appelle le service approprié (AdminService)
 * pour effectuer la logique métier, et je retourne la réponse (soit une vue, soit du JSON).
 */
class AdminController extends Controller
{
    private AdminService $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
    }

    /**
     * Affiche la page principale du tableau de bord de l'administrateur.
     */
    public function dashboard()
    {
        // Je rends simplement la vue. Le JavaScript côté client se chargera
        // de faire les appels API pour récupérer les données dynamiques.
        $this->render('admin/dashboard', ['pageTitle' => 'Tableau de Bord Administrateur']);
    }

    /**
     * Endpoint API pour créer un nouvel employé.
     */
    public function createEmployeeApi()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $employee = $this->adminService->createEmployee($data);
            $this->jsonResponse($employee, 201); // 201 Created
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400); // 400 Bad Request
        }
    }

    /**
     * Endpoint API pour mettre à jour le statut d'un utilisateur.
     */
    public function updateUserStatusApi()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $success = $this->adminService->updateUserAccountStatus($data['userId'], $data['status']);
            if ($success) {
                $this->jsonResponse(['success' => true]);
            } else {
                throw new Exception('La mise à jour du statut a échoué.');
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Endpoint API pour récupérer les statistiques des trajets.
     */
    public function getRideStatsApi()
    {
        $data = $this->adminService->getRideStatisticsByDay();
        $this->jsonResponse($data);
    }

    /**
     * Endpoint API pour récupérer les statistiques des crédits.
     */
    public function getCreditStatsApi()
    {
        $data = $this->adminService->getPlatformCreditEarningsByDay();
        $this->jsonResponse($data);
    }

    /**
     * Endpoint API pour récupérer le total des crédits gagnés.
     */
    public function getTotalCreditsEarnedApi()
    {
        $total = $this->adminService->getTotalPlatformCreditsEarned();
        $this->jsonResponse(['total' => $total]);
    }

    /**
     * Endpoint API pour récupérer tous les utilisateurs.
     */
    public function getAllUsersApi()
    {
        $users = $this->adminService->getAllUsers();
        $this->jsonResponse($users);
    }

    /**
     * Endpoint API pour récupérer tous les employés.
     */
    public function getAllEmployeesApi()
    {
        $employees = $this->adminService->getAllEmployees();
        $this->jsonResponse($employees);
    }
}