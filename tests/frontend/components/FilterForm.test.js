import { FilterForm } from '../../../public/js/components/FilterForm.js';

/**
 * @jest-environment jsdom
 */
describe('FilterForm', () => {

  beforeEach(() => {
    // On crée une structure HTML de base dans le DOM simulé par JSDOM.
    // Ce HTML doit contenir les éléments dont FilterForm a besoin.
    document.body.innerHTML = `
      <form id="filters-form">
        <input id="price-filter" type="range" />
        <span id="price-output"></span>
        <button type="submit">Appliquer</button>
      </form>
    `;
  });

  
  test('devrait s\'initialiser sans erreur si le formulaire existe', () => {
    // On crée une instance de la classe en lui donnant l'ID de notre faux formulaire.
    const form = new FilterForm('filters-form');
    
    expect(form.form).toEqual(expect.anything());
  });

  test('devrait mettre à jour l\'affichage du prix correctement', () => {
    // Préparation
    const form = new FilterForm('filters-form');
    const priceOutput = document.getElementById('price-output');

    // Action
    form.updatePriceOutputDisplay('75');

    // Assertion
    // On vérifie que le contenu textuel de notre span a bien été mis à jour.
    expect(priceOutput.textContent).toBe('75 crédits');
  });

});
