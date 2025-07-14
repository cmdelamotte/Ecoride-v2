/**
 * domHelpers.js
 * Fournit des fonctions utilitaires pour la manipulation du DOM.
 */

/**
 * Vide un élément de tous ses enfants.
 * @param {HTMLElement} element L'élément à vider.
 */
export function clearChildren(element) {
    while (element.firstChild) {
        element.removeChild(element.firstChild);
    }
}

/**
 * Crée un élément HTML avec des classes, attributs et contenu textuel.
 * @param {string} tag Le nom de la balise HTML (ex: 'div', 'p', 'button').
 * @param {Array<string>} [classes=[]] Un tableau de noms de classes à ajouter.
 * @param {Object} [attributes={}] Un objet d'attributs clé-valeur à définir.
 * @param {string} [textContent=''] Le contenu textuel de l'élément.
 * @returns {HTMLElement} L'élément HTML créé.
 */
export function createElement(tag, classes = [], attributes = {}, textContent = '') {
    const element = document.createElement(tag);

    if (classes.length > 0) {
        element.classList.add(...classes);
    }

    for (const key in attributes) {
        if (Object.hasOwnProperty.call(attributes, key)) {
            element.setAttribute(key, attributes[key]);
        }
    }

    if (textContent) {
        element.textContent = textContent;
    }

    return element;
}
