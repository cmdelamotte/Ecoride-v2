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
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
    getUserVehicles: () => callApi('/api/user/vehicles'),

    // Utilisateurs (rôles, préférences, etc.)
    updateUserRole: (role) => callApi('/account/update-role', 'POST', { role: role }),
    updateDriverPreferences: (preferences) => callApi('/account/update-preferences', 'POST', preferences),
    deleteAccount: () => callApi('/account/delete', 'POST'),

    // Trajets
    searchRides: (params) => callApi(`/api/rides/search?${params}`),
    getRideDetails: (id) => callApi(`/api/rides/${id}/details`),
    bookRide: (id) => callApi(`/rides/${id}/book`, 'POST'),
    getUserRides: (type, page = 1, limit = 10) => callApi(`/api/user-rides?type=${type}&page=${page}&limit=${limit}`),
    cancelRide: (id) => callApi(`/rides/${id}/cancel`, 'POST'),
    startRide: (id) => callApi(`/rides/${id}/start`, 'POST'),
    finishRide: (id) => callApi(`/rides/${id}/finish`, 'POST'),
    publishRide: (data) => callApi('/publish-ride', 'POST', data),

    // Contact
    submitContactForm: (data) => callApi('/contact', 'POST', data),

    // Reports
    submitReport: (data) => callApi('/api/reports', 'POST', data),

    // Reviews
    submitReview: (data) => callApi('/api/reviews', 'POST', data),

    // Employee Reviews Moderation
    getPendingReviews: (page = 1, limit = 10) => callApi(`/api/employee-dashboard/reviews/pending?page=${page}&limit=${limit}`),
    approveReview: (reviewId) => callApi(`/api/employee-dashboard/reviews/${reviewId}/approve`, 'POST'),
    rejectReview: (reviewId) => callApi(`/api/employee-dashboard/reviews/${reviewId}/reject`, 'POST'),

    getPendingReports: (page = 1, limit = 5) => callApi(`/api/employee-dashboard/reports/pending?page=${page}&limit=${limit}`),
    creditDriver: (reportId) => callApi(`/api/employee-dashboard/reports/${reportId}/credit-driver`, 'POST'),
    contactDriver: (reportId) => callApi(`/api/employee-dashboard/reports/${reportId}/contact-driver`, 'POST'), // Nouvelle méthode

    // Admin Dashboard
    createEmployee: (data) => callApi('/api/admin/employees', 'POST', data),
    updateUserStatus: (userId, status) => callApi('/api/admin/users/status', 'POST', { userId, status }),
    getRideStats: () => callApi('/api/admin/stats/rides'),
    getCreditStats: () => callApi('/api/admin/stats/credits_daily'),
    getTotalCreditsEarned: () => callApi('/api/admin/stats/credits_total'),
    getAllUsers: () => callApi('/api/admin/users'),
    getAllEmployees: () => callApi('/api/admin/employees'),

    // Ajouter d'autres méthodes API ici au fur et à mesure du refactoring
};