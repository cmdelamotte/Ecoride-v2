# Stratégie de Test - Projet Ecoride

Ce document décrit l'ensemble des tests effectués sur le projet Ecoride pour garantir la qualité, la robustesse et la sécurité de l'application, en particulier pour la partie dynamique des interfaces utilisateur.

L'approche de test est double : 
1.  **Tests Automatisés** (Unitaires et d'Intégration) pour valider la logique interne des composants et fonctions JavaScript.
2.  **Tests Manuels** (Fonctionnels et de Sécurité) pour valider les parcours utilisateurs de bout en bout et vérifier la robustesse face aux vulnérabilités courantes.

---

## 1. Tests Automatisés (Unitaires et d'Intégration)

Ces tests sont écrits avec le framework **Jest** et sont exécutés en ligne de commande via `npm test`. Ils permettent de vérifier que les briques logiques de l'application fonctionnent comme attendu, de manière rapide et fiable.

### Fichiers Testés

-   `tests/frontend/components/FilterForm.test.js`
-   `tests/frontend/components/RideCard.test.js`
-   `tests/frontend/pages/yourRidesPageHandler.test.js`

### Compétences Démontrées

-   Mise en place d'un environnement de test frontend (Jest, JSDOM, Babel).
-   Test de fonctions pures (utilitaires logiques).
-   Test de composants avec manipulation d'un DOM simulé.
-   Test d'interactions utilisateur (événements `submit`, `click`).
-   Test de code asynchrone et simulation d'appels API (`jest.mock`).
-   Gestion des dépendances de modules et de l'ordre d'exécution des tests.

---

## 2. Jeu d'Essai Fonctionnel (Tests Manuels)

Ces tests valident les parcours utilisateurs de bout en bout dans un environnement réel (navigateur connecté au serveur local Laragon).

| Scénario | Étapes de Reproduction | Résultat Attendu |
| --- | --- | --- |
| **Inscription** | 1. Aller sur /register. 2. Remplir le formulaire avec des données valides. 3. Soumettre. | L'utilisateur est redirigé vers /login avec un message de succès. |
| **Connexion** | 1. Aller sur /login. 2. Entrer des identifiants valides. 3. Soumettre. | L'utilisateur est connecté et redirigé vers la page d'accueil. La barre de navigation est mise à jour. |
| **Recherche de trajet** | 1. Sur la page d'accueil, remplir les champs de recherche (villes, date). 2. Cliquer sur "Rechercher". | Redirection vers la page de résultats. Les trajets correspondants s'affichent. |
| **Application de filtres** | 1. Sur la page de recherche, utiliser le formulaire de filtres (ex: prix max). 2. Cliquer sur "Appliquer". | La liste des trajets se met à jour sans recharger la page, affichant uniquement les résultats filtrés. |
| **Réservation d'un trajet** | 1. Se connecter. 2. Rechercher un trajet. 3. Cliquer sur "Réserver" sur une carte. 4. Confirmer la réservation. | Un message de succès apparaît. Le trajet apparaît dans "Mes Trajets" en tant que passager. |
| **Publication d'un trajet** | 1. Se connecter. 2. Aller sur "Publier un trajet". 3. Remplir le formulaire. 4. Soumettre. | Le trajet est créé. Il apparaît dans "Mes Trajets" en tant que conducteur. |

---

## 3. Tests de Sécurité (Manuels)

Ces tests vérifient la robustesse de l'application face à des tentatives d'exploitation de failles de sécurité courantes.

| Scénario | Étapes de Reproduction | Résultat Attendu |
| --- | --- | --- |
| **Protection XSS** | 1. Se connecter. 2. Laisser un avis sur un trajet terminé. 3. Dans le champ de commentaire, entrer le texte : `<script>alert('XSS')</script>`. 4. Soumettre. | Le commentaire est affiché sur la page avec le texte brut `&lt;script&gt;alert('XSS')&lt;/script&gt;`. **Aucune** boîte de dialogue d'alerte ne doit apparaître. |
| **Contrôle d'accès** | 1. Se connecter avec un compte utilisateur normal (non-admin, non-employé). 2. Tenter d'accéder directement à l'URL du tableau de bord administrateur (`/admin/dashboard`). | L'accès est refusé. L'utilisateur est redirigé, idéalement vers une page d'erreur 403 "Accès Interdit". |
| **Validation des données (Backend)** | 1. Utiliser les outils de développement du navigateur pour intercepter une requête de formulaire (ex: inscription). 2. Modifier la requête pour envoyer des données invalides (ex: un email sans `@`). 3. Envoyer la requête modifiée. | Le serveur doit rejeter la requête et retourner une réponse d'erreur. L'application ne doit pas crasher ou créer de données corrompues. |
