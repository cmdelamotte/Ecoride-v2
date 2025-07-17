/**
 * displayFlashMessage.js
 * Gère l'affichage dynamique des messages flash (alertes Bootstrap) côté client.
 */

/**
 * Affiche une alerte Bootstrap dynamique.
 * @param {string} message Le message à afficher.
 * @param {string} type Le type d'alerte (ex: 'success', 'danger', 'warning', 'info').
 * @param {string} containerId L'ID du conteneur où insérer l'alerte (par défaut 'dynamic-alerts-container').
 */
export function displayFlashMessage(message, type = 'info', containerId = 'dynamic-alerts-container') {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Conteneur d'alertes non trouvé: #${containerId}`);
        return;
    }

    // Créer l'élément alerte
    const alertDiv = document.createElement('div');
    alertDiv.classList.add('alert', `alert-${type}`, 'alert-dismissible', 'fade', 'show');
    alertDiv.setAttribute('role', 'alert');
    alertDiv.textContent = message;

    // Ajouter le bouton de fermeture
    const closeButton = document.createElement('button');
    closeButton.setAttribute('type', 'button');
    closeButton.classList.add('btn-close');
    closeButton.setAttribute('data-bs-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');
    alertDiv.appendChild(closeButton);

    // Insérer l'alerte dans le conteneur
    container.appendChild(alertDiv);

    // Optionnel: masquer l'alerte après quelques secondes
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000); // 5 secondes
}
