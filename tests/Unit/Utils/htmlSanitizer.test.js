/**
 * htmlSanitizer.test.js
 * Tests unitaires pour les fonctions de sanitisation HTML.
 */

import { simpleEscapeHtml } from '../../../public/js/utils/htmlSanitizer.js';

describe('simpleEscapeHtml', () => {
    test('devrait échapper les caractères HTML de base', () => {
        const input = '<script>alert("XSS")</script>';
        const expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
        expect(simpleEscapeHtml(input)).toBe(expected);
    });

    test('devrait échapper les apostrophes', () => {
        const input = "Ceci est un test d'apostrophe.";
        const expected = "Ceci est un test d&#039;apostrophe.";
        expect(simpleEscapeHtml(input)).toBe(expected);
    });

    test('devrait retourner la même chaîne si aucun caractère spécial', () => {
        const input = 'Hello World';
        expect(simpleEscapeHtml(input)).toBe(input);
    });

    test('devrait gérer une chaîne vide', () => {
        const input = '';
        expect(simpleEscapeHtml(input)).toBe('');
    });
});
