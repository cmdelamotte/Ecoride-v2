/**
 * apiClient.js
 * Centralise toutes les interactions avec les API backend.
 */

const API_BASE_URL = ''; // Laisser vide si l'API est sur le même domaine

async function callApi(endpoint, method = 'GET', data = null) {
    const url = `${API_BASE_URL}${endpoint}`;
    const options = {
        method: method,
        headers: {
            'Accept': 'application/json',
        },
        credentials: 'same-origin' // Important pour envoyer les cookies de session
    };

    if (data) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const jsonResponse = await response.json();

        // Toujours retourner la réponse JSON, qu'elle soit OK ou non.
        // La logique métier (success: true/false) sera gérée par le code appelant.
        return jsonResponse;
    } catch (error) {
        console.error('API call error:', error);
        // Si la réponse n'est pas JSON ou s'il y a une erreur réseau,
        // nous levons une erreur générique pour que le code appelant puisse la gérer.
        // Nous ajoutons un indicateur pour distinguer les erreurs réseau/parsing des erreurs API métier.
        throw new Error('Erreur de communication ou format de réponse invalide.', { cause: error, isNetworkError: true });
    }
}

export const apiClient = {
    // Véhicules
    getBrands: () => callApi('/api/brands'),
    addVehicle: (data) => callApi('/api/vehicles', 'POST', data),
    updateVehicle: (id, data) => callApi(`/api/vehicles/${id}/update`, 'POST', data),
    deleteVehicle: (id) => callApi(`/api/vehicles/${id}/delete`, 'POST'),

    // Utilisateurs (rôles, préférences, etc.)
    updateUserRole: (role) => callApi('/account/update-role', 'POST', { role: role }),
    updateDriverPreferences: (preferences) => callApi('/account/update-preferences', 'POST', preferences),
    deleteAccount: () => callApi('/account/delete', 'POST'),

    // Ajoutez d'autres méthodes API ici au fur et à mesure du refactoring
};