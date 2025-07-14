/**
 * formHelpers.js
 * Gère l'extraction des données de formulaire et l'affichage des messages d'erreur.
 */

/**
 * Extrait les données d'un formulaire en un objet simple.
 * @param {HTMLFormElement} formElement L'élément formulaire.
 * @returns {Object} Un objet clé-valeur des données du formulaire.
 */
export function getFormData(formElement) {
    const formData = new FormData(formElement);
    const data = {};
    for (const [key, value] of formData.entries()) {
        // Gérer les checkboxes non cochées qui ne sont pas incluses dans FormData
        if (formElement.elements[key] && formElement.elements[key].type === 'checkbox') {
            data[key] = formElement.elements[key].checked;
        } else {
            data[key] = value;
        }
    }
    return data;
}

/**
 * Affiche les messages d'erreur sous les champs de formulaire.
 * @param {Object} errors Un objet où les clés sont les noms des champs et les valeurs sont les messages d'erreur.
 * @param {HTMLFormElement} formElement L'élément formulaire.
 */
export function displayFormErrors(errors, formElement) {
    // Supprimer les erreurs précédentes
    formElement.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    formElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    for (const fieldName in errors) {
        if (Object.hasOwnProperty.call(errors, fieldName)) {
            const errorMessage = errors[fieldName];
            const inputElement = formElement.querySelector(`[name="${fieldName}"]`);

            if (inputElement) {
                inputElement.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.classList.add('invalid-feedback');
                errorDiv.textContent = errorMessage;
                inputElement.parentNode.appendChild(errorDiv);
            } else {
                // Gérer les erreurs générales ou non liées à un champ spécifique
                console.error(`Erreur pour le champ ${fieldName}: ${errorMessage}`);
                // Vous pouvez ajouter une logique pour afficher ces erreurs générales
                // par exemple, dans une alerte globale en haut du formulaire.
            }
        }
    }
}
