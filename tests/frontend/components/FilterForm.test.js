// On importe la classe que l'on veut tester.
// Le chemin doit être relatif depuis ce fichier de test vers le fichier du composant.
import { FilterForm } from '../../../public/js/components/FilterForm.js';

/**
 * @jest-environment jsdom
 */
describe('FilterForm', () => {

  // 'beforeEach' est une fonction de Jest qui s'exécute avant chaque 'test'
  // dans ce bloc 'describe'. C'est parfait pour préparer le DOM.
  beforeEach(() => {
    // On crée une structure HTML de base dans le DOM simulé par JSDOM.
    document.body.innerHTML = `
      <form id="filters-form">
        <input id="price-filter" name="price-filter" type="range" />
        <span id="price-output"></span>
        <button type="submit">Appliquer</button>
      </form>
    `;
    // On définit une URL de base pour pouvoir tester ses changements
    window.history.pushState({}, '', '/search?city=Paris');
  });

  // Premier vrai test : est-ce que la classe s'initialise correctement ?
  test('devrait s\'initialiser sans erreur si le formulaire existe', () => {
    const form = new FilterForm('filters-form');
    expect(form.form).toEqual(expect.anything());
  });

  // Deuxième test : est-ce qu'une méthode simple fonctionne comme prévu ?
  test('devrait mettre à jour l\'affichage du prix correctement', () => {
    const form = new FilterForm('filters-form');
    const priceOutput = document.getElementById('price-output');
    form.updatePriceOutputDisplay('75');
    expect(priceOutput.textContent).toBe('75 crédits');
  });

  // Troisième test, plus complexe : la soumission du formulaire
  test('devrait mettre à jour l\'URL et déclencher un événement lors de la soumission', () => {
    // Préparation
    const formElement = document.getElementById('filters-form');
    const priceInput = document.getElementById('price-filter');
    priceInput.value = '100'; // On simule une valeur choisie par l'utilisateur

    // On "espionne" les événements pour savoir si le nôtre est bien déclenché
    const eventListenerMock = jest.fn();
    window.addEventListener('search-updated', eventListenerMock);

    // On instancie la classe APRES avoir mis en place l'espion
    new FilterForm('filters-form');

    // Action : on simule la soumission du formulaire
    formElement.dispatchEvent(new Event('submit'));

    // Assertions
    // 1. L'URL a-t-elle été mise à jour correctement ?
    expect(window.location.search).toContain('maxPrice=100');
    expect(window.location.search).toContain('page=1');

    // 2. L'événement personnalisé a-t-il été déclenché ?
    expect(eventListenerMock).toHaveBeenCalled();

    // On nettoie l'écouteur d'événement
    window.removeEventListener('search-updated', eventListenerMock);
  });

});