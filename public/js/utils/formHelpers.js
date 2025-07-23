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
 * Valide un formulaire entier en se basant sur les attributs HTML5 (required, min, max, etc.).
 * Affiche les messages d'erreur Bootstrap personnalisés.
 *
 * @param {HTMLFormElement} form - L'élément formulaire à valider.
 * @returns {boolean} - `true` si le formulaire est valide, `false` sinon.
 */
export function validateForm(form) {
    let isValid = true;
    // Supprime les anciennes validations
    form.classList.remove('was-validated');
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    if (!form.checkValidity()) {
        isValid = false;
        form.reportValidity(); // Déclenche l'affichage des messages d'erreur natifs du navigateur
    }

    form.classList.add('was-validated');

    return isValid;
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

/**
 * Active ou désactive le bouton de soumission d'un formulaire et affiche un état de chargement.
 *
 * @param {HTMLFormElement} form - L'élément formulaire.
 * @param {boolean} isLoading - `true` pour afficher l'état de chargement, `false` pour le retirer.
 * @param {string} [loadingText='Chargement...'] - Le texte à afficher sur le bouton pendant le chargement.
 */
export function setFormLoadingState(form, isLoading, loadingText = 'Chargement...') {
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;

    if (isLoading) {
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.textContent;

        const spinner = document.createElement('span');
        spinner.classList.add('spinner-border', 'spinner-border-sm');
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');

        submitButton.innerHTML = ''; // Vider le contenu existant de manière sécurisée
        submitButton.appendChild(spinner);
        submitButton.appendChild(document.createTextNode(` ${loadingText}`));
    } else {
        submitButton.disabled = false;
        if (submitButton.dataset.originalText) {
            submitButton.innerHTML = submitButton.dataset.originalText;
        }
    }
}

/**
 * Réinitialise l'état de validation d'un formulaire.
 *
 * @param {HTMLFormElement} form - L'élément formulaire à réinitialiser.
 */
export function resetFormValidation(form) {
    form.classList.remove('was-validated');
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
}
