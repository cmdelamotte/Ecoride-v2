<?php

namespace App\Services;

class ValidationService
{
    /**
     * Valide les données d'inscription.
     *
     * @param array $data Les données du formulaire (username, email, password, etc.).
     * @return array Le tableau des erreurs. Vide s'il n'y a pas d'erreur.
     */
    public static function validateRegistration(array $data): array
    {
        $errors = [];

        // Extraction des données pour plus de clarté
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $phoneNumber = $data['phone_number'] ?? '';
        $birthDate = $data['birth_date'] ?? '';

        // Validation Username
        if (empty($username)) {
            $errors['username'] = 'Le nom d\'utilisateur est requis.';
        } elseif (strlen($username) < 2) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 2 caractères.';
        } elseif (!preg_match("/^[a-zA-Z0-9\s'-]+$/", $username)) {
            $errors['username'] = 'Le nom d\'utilisateur contient des caractères non autorisés.';
        }

        // Validation Email
        if (empty($email)) {
            $errors['email'] = 'L\'adresse email est requise.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'adresse email n\'est pas valide.';
        }

        // Validation First Name
        if (empty($firstName)) {
            $errors['first_name'] = 'Le prénom est requis.';
        } elseif (strlen($firstName) < 2) {
            $errors['first_name'] = 'Le prénom doit contenir au moins 2 caractères.';
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/u", $firstName)) { // Ajout du modificateur 'u' pour UTF-8
            $errors['first_name'] = 'Le prénom contient des caractères non autorisés.';
        }

        // Validation Last Name
        if (empty($lastName)) {
            $errors['last_name'] = 'Le nom de famille est requis.';
        } elseif (strlen($lastName) < 2) {
            $errors['last_name'] = 'Le nom de famille doit contenir au moins 2 caractères.';
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/u", $lastName)) { // Ajout du modificateur 'u' pour UTF-8
            $errors['last_name'] = 'Le nom de famille contient des caractères non autorisés.';
        }

        // Validation Phone Number
        if (empty($phoneNumber)) {
            $errors['phone_number'] = 'Le numéro de téléphone est requis.';
        } elseif (!preg_match("/^[0-9]{10}$/", $phoneNumber)) {
            $errors['phone_number'] = 'Le numéro de téléphone doit contenir 10 chiffres.';
        }

        // Validation Birth Date
        if (empty($birthDate)) {
            $errors['birth_date'] = 'La date de naissance est requise.';
        } else {
            try {
                $birthDateObj = new \DateTime($birthDate);
                $today = new \DateTime();
                $minAgeDate = (new \DateTime())->modify('-16 years');

                if ($birthDateObj > $today) {
                    $errors['birth_date'] = 'La date de naissance ne peut pas être dans le futur.';
                } elseif ($birthDateObj > $minAgeDate) {
                    $errors['birth_date'] = 'Vous devez avoir au moins 16 ans pour vous inscrire.';
                }
            } catch (\Exception $e) {
                $errors['birth_date'] = 'La date de naissance n\'est pas valide.';
            }
        }

        // Validation Password
        if (empty($password)) {
            $errors['password'] = 'Le mot de passe est requis.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
        } else {
            if (strlen($password) < 8 ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[a-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[^a-zA-Z0-9\s]/', $password)) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial.';
            }
        }

        return $errors;
    }
}
