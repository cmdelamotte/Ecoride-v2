<?php

namespace App\Helpers;

use App\Models\User;

class UserHelper
{
    /**
     * Formate un objet User en tableau associatif pour l'API ou les vues.
     *
     * @param User $user L'objet User à formater.
     * @return array Le tableau associatif formaté.
     */
    public static function formatUserForDisplay(User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'address' => $user->getAddress(),
            'birth_date' => $user->getBirthDate(),
            'phone_number' => $user->getPhoneNumber(),
            'profile_picture_path' => $user->getProfilePicturePath(),
            'system_role' => $user->getSystemRole(),
            'functional_role' => $user->getFunctionalRole(),
            'driver_rating' => $user->getDriverRating(),
            'account_status' => $user->getAccountStatus(),
            'credits' => $user->getCredits(),
            'driver_pref_animals' => $user->getDriverPrefAnimals(),
            'driver_pref_smoker' => $user->getDriverPrefSmoker(),
            'driver_pref_custom' => $user->getDriverPrefCustom(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ];
    }

    /**
     * Formate une collection d'objets User en tableaux associatifs pour l'API ou les vues.
     *
     * @param array $users La collection d'objets User.
     * @return array Le tableau de tableaux associatifs formatés.
     */
    public static function formatCollectionForDisplay(array $users): array
    {
        $formattedUsers = [];
        foreach ($users as $user) {
            $formattedUsers[] = self::formatUserForDisplay($user);
        }
        return $formattedUsers;
    }
}