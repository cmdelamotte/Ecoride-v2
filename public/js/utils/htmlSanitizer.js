/**
 * htmlSanitizer.js
 * Fonctions utilitaires pour la sanitisation de chaînes de caractères HTML.
 */

export function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// Une autre méthode plus simple pour les caractères de base
export function simpleEscapeHtml(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
