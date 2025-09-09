# Dossier de Révision Technique – EcoRide

---

## Sommaire
- [A. Fonctionnalités Publiques (Visiteurs)](#a-fonctionnalités-publiques-visiteurs)
- [B. Espace Utilisateur (Connecté)](#b-espace-utilisateur-connecté)
- [C. Espace Chauffeur](#c-espace-chauffeur)
- [D. Espace Employé](#d-espace-employé)
- [E. Espace Administrateur](#e-espace-administrateur)
- [F. Modélisation des Données (MariaDB + MongoDB)](#f-modélisation-des-données-mariadb--mongodb)
- [G. Sécurité, Rôles & CSRF](#g-sécurité-rôles--csrf)
- [H. Points d’architecture, justifications et alternatives courantes](#h-points-darchitecture-justifications-et-alternatives-courantes)

---

## A. Fonctionnalités Publiques (Visiteurs)

### 1) Consulter la page d'accueil avec une présentation et une barre de recherche

L'affichage est initié par une requête `GET /`. Le mécanisme est le suivant :

- **Routage (`app/routes.php`)**: Le routeur (`App\Core\Router`) lit sa configuration et associe la requête à `HomeController::index()`. Cette approche par tableau de configuration est simple, sans dépendances, et centralise la définition des routes.

- **Contrôleur (`HomeController.php`)**: La méthode `index()` appelle `$this->render('home')`. Elle hérite de `App\Core\Controller` et son rôle se limite à orchestrer l'affichage de la vue, respectant le principe de contrôleur léger (thin controller).

- **Mécanisme de rendu (`App\Core\Controller::render()`)**: Cette méthode utilise la mise en tampon de PHP (`ob_start()`). Elle exécute le fichier de vue (`app/Views/home.php`), capture le HTML généré dans une variable `$content` avec `ob_get_clean()`, puis inclut le gabarit principal (`app/Views/layout.php`) qui se charge d'afficher cette variable. C'est un pattern de templating fondamental en PHP qui permet d'imbriquer des vues dans un layout commun.

- **Gabarit & Partials (`layout.php`, `navbar.php`)**: Le `layout.php` est la structure HTML globale. Il inclut la barre de navigation (`partials/navbar.php`). Cette dernière accède directement à la superglobale `$_SESSION` pour afficher des liens différents selon le statut de connexion et les rôles de l'utilisateur (`ROLE_ADMIN`, etc.). C'est un choix pragmatique pour un composant aussi global. Le layout expose également le jeton CSRF dans une balise `<meta name="csrf-token">` pour le rendre accessible aux scripts JS de manière découplée.

- **Vue & JS (`home.php`, `homePage.js`)**: La vue contient le formulaire de recherche. Le script `homePage.js` intercepte l'événement `submit` du formulaire, empêche le rechargement de la page (`event.preventDefault()`), lit les valeurs des champs, et construit une URL de type `GET` (ex: `/rides-search?from=Paris&to=Lyon&date=...`) vers laquelle il redirige l'utilisateur (`window.location.href`). Cette technique produit une URL propre, partageable et conforme aux principes REST.

### 2) Naviguer via un menu principal

La barre de navigation est un composant central de l'interface, rendu sur toutes les pages via une inclusion dans le gabarit principal.

- **Intégration (`layout.php`)**: Le fichier `app/Views/layout.php` inclut la barre de navigation avec l'instruction `include __DIR__ . '/partials/navbar.php';`. L'utilisation de la constante magique `__DIR__` garantit que le chemin d'accès au fichier est toujours correct, car il est résolu de manière absolue par rapport à l'emplacement du fichier `layout.php` lui-même.

- **Structure et Affichage (`navbar.php`)**: Le fichier `app/Views/partials/navbar.php` est une "vue partielle" qui contient le code HTML et la logique d'affichage du menu.
  - **Framework CSS**: Il s'appuie sur les classes de Bootstrap 5 (`navbar`, `navbar-expand-lg`, `navbar-toggler`) pour assurer un comportement responsive. La complexité de l'adaptation aux différentes tailles d'écran (passage au menu "hamburger" sur mobile) est ainsi déléguée au framework.
  - **Logique d'affichage conditionnel**: La personnalisation du menu en fonction de l'utilisateur est réalisée directement en PHP via l'inspection de la superglobale `$_SESSION`.
    - **Vérification d'authentification**: Un bloc `if (isset($_SESSION['user_id']))` sert de porte d'entrée pour distinguer les utilisateurs connectés des visiteurs.
    - **Contrôle d'accès basé sur les rôles (RBAC)**: Pour les utilisateurs connectés, le code procède à une vérification plus fine en inspectant le tableau des rôles stocké en session. L'expression `$userRoles = $_SESSION['user_roles'] ?? [];` est une mesure de sécurité (null coalescing operator) qui évite les erreurs si la clé `user_roles` n'existe pas. Ensuite, des appels à `in_array('ROLE_ADMIN', $userRoles)` déterminent si des liens spécifiques, comme ceux vers les tableaux de bord d'administration ou d'employé, doivent être affichés.

- **Justification de l'approche**:
  - **Avantage (Pragmatisme)**: L'accès direct à `$_SESSION` dans la vue est une méthode très directe et performante. Pour un composant aussi transversal que la navigation, c'est un compromis acceptable qui évite de surcharger le contrôleur avec la transmission de données relatives à l'état de l'utilisateur à chaque requête.
  - **Inconvénient (Couplage)**: Cette technique crée un couplage entre la vue et la structure de données de la session. Si le nom de la clé de session (ex: `user_roles`) venait à changer, il faudrait impérativement mettre à jour ce fichier. Une alternative plus découplée consisterait à utiliser une classe de service (ex: `AuthService::hasRole('ROLE_ADMIN')`) qui encapsulerait la logique d'accès à la session, mais cela ajouterait une couche d'abstraction qui n'est pas forcément nécessaire pour un projet de cette envergure.

### 3) Rechercher des trajets (ville départ/arrivée + date)

Cette fonctionnalité est architecturée autour d'une page unique (`/rides-search`) qui charge dynamiquement son contenu via une API RESTful (`/api/rides/search`), offrant une expérience utilisateur fluide sans rechargement de page.

**1. L'architecture Front-End (JavaScript)**

L'approche côté client est modulaire et événementielle, orchestrée par `public/js/pages/ridesSearchPage.js`.

-   **Initialisation et Composants** : Au chargement de la page, le script `ridesSearchPage.js` instancie plusieurs classes : `SearchForm` (recherche principale), `FilterForm` (filtres avancés), `Pagination`, et `initBookingHandler` (modales de réservation).

-   **Gestion de l'URL et de l'état** : Les composants `SearchForm` et `FilterForm` synchronisent les formulaires et l'URL.
    -   **Lecture** : À l'initialisation, ils utilisent `new URLSearchParams(window.location.search)` pour lire les paramètres de l'URL et pré-remplir les champs. Un utilisateur peut ainsi recharger ou partager un lien de recherche, l'interface reflètera la recherche en cours.
    -   **Écriture** : À la soumission, le script intercepte l'événement (`event.preventDefault()`), met à jour les paramètres et utilise `window.history.pushState()` pour modifier l'URL sans recharger la page (comportement de type SPA).

-   **Communication par Événements** : Après la mise à jour de l'URL, le composant déclenche un événement personnalisé (`window.dispatchEvent(new CustomEvent('search-updated', ...))`). Le script `ridesSearchPage.js` écoute cet événement et lance la récupération des données. Ce pattern découple les composants.

-   **Appel API et Rendu** : La fonction `fetchAndDisplayRides()` appelle le back-end `GET /api/rides/search`. Pour chaque trajet reçu en JSON, elle instancie une `RideCard`. Cette classe utilise un élément `<template>` du HTML (`#ride-card-template`) pour cloner efficacement la structure DOM d'une carte, une approche plus performante que la manipulation de chaînes HTML.

**2. L'architecture Back-End (PHP)**

Le back-end est divisé en trois couches : Contrôleur, Service, et Helper.

-   **Contrôleur (`RideSearchController.php`)** : Agit comme un aiguilleur. La méthode `searchApi()` récupère les paramètres de `$_GET` et les passe directement au `SearchFilterService`, sans contenir de logique métier.

-   **Service (`SearchFilterService.php`)** : C'est le cerveau de la fonctionnalité.
    -   **Validation et Sécurité** : La première étape est une validation rigoureuse des entrées (`filter_var`, `preg_match`) pour la sécurité et l'intégrité des données.
    -   **Construction de Requête SQL Dynamique** : Le service construit la requête en utilisant un tableau de conditions (`$whereConditions`) et un tableau de paramètres (`$queryParams`) pour les requêtes préparées PDO. C'est la protection standard contre les injections SQL.
    -   **Logique de Calcul des Places Disponibles** : Le calcul des places se fait en SQL avec une combinaison de `LEFT JOIN` sur `bookings`, `GROUP BY r.id`, et une clause `HAVING (r.seats_offered - COALESCE(SUM(b.seats_booked), 0)) >= :seats_needed`. La clause `HAVING` est essentielle car elle filtre les résultats *après* l'agrégation (le `SUM`), ce que `WHERE` ne peut pas faire.
    -   **Pagination Optimisée** : Le service exécute deux requêtes : un `COUNT(*)` pour obtenir le nombre total de résultats (pour la pagination), puis la requête principale avec `LIMIT` et `OFFSET` pour ne récupérer que les données de la page courante.
    -   **Hydratation d'Objets** : Le service transforme les données brutes de la BDD en un graphe d'objets PHP (`Ride`, `User`, `Vehicle`).

-   **Helper (`RideHelper.php`)** : Sert de traducteur entre les objets PHP du back-end et le JSON du front-end.
    -   La méthode `formatCollectionForSearchApi()` convertit les objets `Ride` en un simple tableau associatif, en formatant les dates, calculant des champs dérivés, et aplatissant la structure pour une consommation facile par le JavaScript. Cela respecte le Principe de Responsabilité Unique (le Modèle ne doit pas gérer son format de sortie).

### 4) Appliquer des filtres (écologique, prix, note, durée, animaux)

Le mécanisme de filtrage est une démonstration de la synergie entre le front-end qui gère l'état de l'interface et le back-end qui adapte dynamiquement sa logique de récupération de données.

**1. Front-End : Le composant `FilterForm.js`**

-   **Rôle** : Cette classe JavaScript est entièrement dédiée à la gestion du formulaire de filtres.
-   **Gestion de l'état via l'URL** :
    -   À l'initialisation, la méthode `prefillFormFromURL()` lit les paramètres de l'URL (ex: `?maxPrice=50&ecoOnly=true`) et ajuste les contrôles du formulaire (curseurs, cases à cocher, boutons radio) pour qu'ils correspondent à l'état actuel de la recherche.
    -   Lorsque l'utilisateur clique sur "Appliquer", la méthode `handleSubmit()` est appelée. Elle ne soumet pas le formulaire de manière traditionnelle.
-   **Mécanisme de mise à jour** :
    1.  `event.preventDefault()` est appelé pour bloquer la soumission standard.
    2.  Il récupère les paramètres de recherche de base existants (`departure_city`, `date`, etc.) depuis l'URL.
    3.  Il lit les valeurs actuelles du formulaire de filtres (ex: `price-filter`, `eco-filter`).
    4.  Il construit un nouvel objet `URLSearchParams`, en y ajoutant les filtres. Une étape clé est de **réinitialiser la page à 1** (`currentSearchParams.set('page', '1')`) car une nouvelle application de filtres doit toujours ramener l'utilisateur à la première page de résultats.
    5.  L'URL du navigateur est mise à jour sans rechargement via `window.history.pushState()`.
    6.  Enfin, il déclenche l'événement `new CustomEvent('search-updated')`, signalant à la page qu'elle doit rafraîchir les données en se basant sur la nouvelle URL.

**2. Back-End : Le `SearchFilterService.php`**

-   **Rôle** : Le service reçoit le tableau `$filters` (qui correspond au `$_GET` de l'URL) et l'utilise pour construire une requête SQL sur mesure.
-   **Validation et Préparation** : Pour chaque filtre potentiel, le service vérifie sa présence et sa validité.
    -   `$maxPrice = filter_var($filters['maxPrice'] ?? null, FILTER_VALIDATE_FLOAT);`
    -   `$ecoOnly = filter_var($filters['ecoOnly'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);`
    -   Cette validation systématique est cruciale pour la sécurité et la robustesse.

-   **Traduction des filtres en clauses SQL** : C'est ici que la "magie" opère. Le service ajoute conditionnellement des chaînes de caractères au tableau `$whereConditions`, qui sera ensuite joint par des `AND`.
    -   **Prix** : `if ($maxPrice !== null) { $whereConditions[] = "r.price_per_seat <= :maxPrice"; }`
    -   **Écologique** : `if ($ecoOnly === true) { $whereConditions[] = "v.is_electric = 1"; }`. Cette condition nécessite la jointure avec la table `vehicles` (`v`).
    -   **Durée** : `if ($maxDuration !== null) { $whereConditions[] = "TIMESTAMPDIFF(MINUTE, r.departure_time, r.estimated_arrival_time) <= :maxDurationMinutes"; }`. Ce filtre utilise une fonction SQL native (`TIMESTAMPDIFF`) pour calculer la différence en minutes entre deux `DATETIME`.
    -   **Animaux & Note** : `if ($animalsAllowed !== null) { $whereConditions[] = "u.driver_pref_animals = :animalsAllowed"; }` et `if ($minRating !== null) { $whereConditions[] = "u.driver_rating >= :minRating"; }`. Ces filtres nécessitent la jointure avec la table `users` (`u`).

-   **Flexibilité de l'architecture** : Cette approche de construction dynamique de la requête est extrêmement flexible. Pour ajouter un nouveau filtre, il suffit de :
    1.  Ajouter le champ au formulaire HTML (`search.php`).
    2.  Le gérer dans `FilterForm.js` pour l'ajouter à l'URL.
    3.  Ajouter un `if` dans `SearchFilterService.php` pour l'intégrer à la clause `WHERE`.
    Le reste de l'application (contrôleur, rendu) n'a pas besoin d'être modifié.

### 5) Consulter les détails d'un trajet (avis, véhicule, préférences)

Le mécanisme de consultation des détails est déclenché par une action de l'utilisateur et suit un flux de données asynchrone précis.

**1. Front-End : Déclenchement et Appel (`RideCard.js`)**

-   **Interaction Utilisateur** : L'utilisateur clique sur le bouton "Détails" d'une `RideCard`. Un écouteur d'événement, placé sur ce bouton lors de l'instanciation de la carte, appelle la méthode `loadDetails()` de l'objet `RideCard` correspondant.
-   **Gestion de l'UI** : La méthode `loadDetails()` commence par manipuler le DOM pour améliorer l'expérience utilisateur : elle affiche un indicateur de chargement ("Chargement des détails...") et masque les éventuels messages d'erreur précédents.
-   **Appel API** : Elle utilise ensuite `apiClient.getRideDetails(this.rideData.ride_id)` pour lancer une requête `GET` asynchrone vers le point de terminaison de l'API, par exemple `/api/rides/123/details`, où `123` est l'ID du trajet.

**2. Back-End : Contrôleur et Service**

-   **Route et Contrôleur (`RideSearchController.php`)** : Le routeur intercepte la requête et l'achemine vers la méthode `detailsApi(int $id)`. Le framework de routage est capable d'extraire le `123` de l'URL et de le passer en tant que paramètre `$id` à la méthode. Le contrôleur délègue immédiatement le travail en appelant `rideService->findRideDetailsById($id)`.
-   **Service (`RideService.php`)** : La méthode `findRideDetailsById()` est chargée de construire un "graphe d'objet" complet. Au lieu de ne récupérer que les informations de la table `rides`, elle doit assembler un objet `Ride` complet avec toutes ses dépendances. Pour ce faire, elle exécute plusieurs requêtes pour charger et "hydrater" (remplir les objets) :
    1.  Le trajet principal (`Ride`).
    2.  Le conducteur associé (`User`).
    3.  Le véhicule associé (`Vehicle`), y compris la marque (`Brand`) qui est une autre relation.
    4.  Les réservations (`Booking`) pour ce trajet.
    5.  Les deux derniers avis (`Review`) approuvés pour le conducteur.
    Cette agrégation de données en un seul objet `Ride` cohérent est la responsabilité principale du service.

**3. Back-End : Formatage et Réponse**

-   **Helper (`RideHelper.php`)** : Une fois que le contrôleur reçoit l'objet `Ride` complet du service, il ne le renvoie pas directement. Il le passe à la méthode statique `RideHelper::formatDetailsForApi()`.
-   **Rôle du Helper (Aplatissement)** : Cette méthode prend le graphe d'objets complexe (un objet `Ride` qui contient un objet `User`, qui contient un tableau d'objets `Review`, etc.) et le "traduit" en un simple tableau associatif PHP. Cette transformation est cruciale car elle prépare une structure de données simple, prévisible et "plate" qui peut être facilement encodée en JSON et consommée par le JavaScript, sans que le front-end ait à se soucier de la structure des objets PHP.

**4. Front-End : Rendu des détails (`RideCard.js`)**

-   **Traitement de la réponse** : La promesse de l'appel `fetch` se résout. La méthode `loadDetails()` reçoit le JSON du serveur.
-   **Mise à jour du DOM** :
    -   Elle masque l'indicateur de chargement.
    -   Si la requête a réussi (`response.success`), elle utilise les données de `response.details` pour peupler les sections correspondantes dans le HTML de la carte (le conteneur des détails qui était initialement masqué). Elle remplit le modèle du véhicule, l'année, les préférences du conducteur, et génère dynamiquement les éléments HTML pour chaque avis reçu.
    -   Si la requête a échoué, elle affiche un message d'erreur à l'utilisateur.
    -   Finalement, elle affiche le conteneur des détails rempli.

Cette approche de chargement à la demande est très efficace : la page de résultats initiale est légère et rapide, et les données plus lourdes (détails, avis) ne sont transférées sur le réseau que si l'utilisateur en exprime le besoin.

### 6) Créer un compte utilisateur

Le processus d'inscription est géré par une chaîne de responsabilités claire, allant du contrôleur au service, tout en intégrant des mesures de sécurité essentielles.

**1. Contrôleur (`AuthController.php`) : L'Orchestrateur HTTP**

-   **Affichage du formulaire (GET /register)** : La méthode `registerForm()` se contente de rendre la vue `auth/register.php`, sans aucune logique.
-   **Traitement de la soumission (POST /register)** : La méthode `register()` agit comme un chef d'orchestre :
    1.  Elle récupère les données brutes du formulaire (`$_POST`).
    2.  Elle délègue immédiatement tout le processus métier à `authService->attemptRegistration($_POST)`. Le contrôleur ne sait pas *comment* un utilisateur est enregistré, il fait confiance au service pour cela.
    3.  **En cas de succès**, le service retourne `['success' => true, 'user' => $userObject]`. Le contrôleur connecte alors automatiquement l'utilisateur : il initialise la session (`$_SESSION['user_id']`, etc.), récupère les rôles via `userService->getUserRolesArray()`, puis redirige vers la page de compte (`header('Location: /account')`). Cette connexion automatique est un choix délibéré pour fluidifier le parcours utilisateur.
    4.  **En cas d'échec**, le service retourne `['success' => false, 'errors' => $errorsArray]`. Le contrôleur ré-affiche le formulaire d'inscription en lui passant le tableau d'erreurs et les données précédemment saisies (`oldInput`), afin que l'utilisateur puisse corriger ses erreurs sans tout retaper.

**2. Service (`AuthService.php`) : Le Cerveau Métier et Sécurité**

La méthode `attemptRegistration()` concentre toute la logique et la sécurité :

-   **Étape 1 : Validation des données** : Elle appelle `ValidationService::validateRegistration($data)`. La logique de validation (champs requis, format de l'email, confirmation du mot de passe) est externalisée dans un service dédié, respectant le Principe de Responsabilité Unique.
-   **Étape 2 : Vérification d'unicité** : Avant de créer l'utilisateur, le service vérifie que l'email et le nom d'utilisateur ne sont pas déjà présents en base de données en appelant `userService->findByEmailOrUsername()`.
-   **Étape 3 : Hachage du mot de passe (Sécurité)** : C'est l'étape de sécurité la plus critique. Le mot de passe n'est **jamais** stocké en clair. Il est transformé en une empreinte irréversible via `password_hash($data['password'], PASSWORD_DEFAULT)`. L'utilisation de `PASSWORD_DEFAULT` est une excellente pratique, car cet algorithme est maintenu par PHP et évoluera vers des standards plus forts dans les futures versions de PHP, garantissant la pérennité de la sécurité sans modification du code.
-   **Étape 4 : Création et Persistance** : Un objet `User` est créé et peuplé avec les données validées et le mot de passe haché. Des valeurs par défaut sont appliquées (20 crédits, rôle fonctionnel 'passenger'). L'objet est ensuite passé à `userService->create()` pour l'insertion en base de données.
-   **Étape 5 : Assignation du rôle par défaut** : Juste après la création, le service assigne le rôle système de base `ROLE_USER` au nouvel utilisateur via `UserRoleService`.

**3. Service (`UserService.php`) et Pattern Repository**

-   Le `UserService` n'exécute pas directement de SQL. Il suit le **pattern Repository**. Il contient une instance d'un objet qui implémente `UserRepositoryInterface`. Par défaut, c'est `PdoUserRepository`.
-   **Injection de Dépendances** : Le constructeur du service (`__construct`) permet d'injecter une implémentation du repository. C'est une pratique avancée qui rend le code extrêmement testable : en phase de test, on peut injecter un "faux" repository (`MockUserRepository`) qui ne communique pas avec la base de données.
-   La méthode `create(User $user)` du service se contente donc d'appeler la méthode `create($user)` du repository, qui, elle, contient la requête `INSERT INTO`. Cette séparation isole complètement la logique métier de la technologie de persistance des données.

---

## B. Espace Utilisateur (Connecté)

### 1) Participer à un covoiturage et utiliser le système de crédits

Ce processus critique combine une gestion d'interface utilisateur conditionnelle, un appel API sécurisé, et surtout, une logique de back-end robuste pour garantir l'intégrité des données transactionnelles (les crédits et les places).

**1. Front-End (`BookingHandler.js`) : Gestion de l'interaction**

-   **Initialisation et Détection d'état** : Le script `initBookingHandler()` est appelé sur la page de recherche. Il vérifie d'abord si l'utilisateur est authentifié en lisant la balise `<meta name="auth-status">`.
-   **Logique Conditionnelle** :
    -   **Si l'utilisateur est un visiteur**, le script ouvre une modale dédiée (`#guestLoginModal`) qui l'invite à se connecter ou s'inscrire.
    -   **Si l'utilisateur est connecté**, il laisse Bootstrap gérer l'ouverture de la modale de confirmation (`#confirmationModal`).
-   **Passage de Données** : L'événement `show.bs.modal` de Bootstrap est utilisé pour intercepter l'ouverture de la modale. À ce moment, le script récupère les données du trajet (stockées dans un attribut `data-ride` sur l'élément de la carte) et les utilise pour peupler la modale avec les informations contextuelles (villes, prix, etc.).
-   **Appel API** : Au clic sur le bouton de confirmation final, la fonction `handleConfirmBooking()` est exécutée. Elle envoie une requête `POST` à l'API (`/api/rides/{id}/book`) en utilisant `apiClient.bookRide()`. L'en-tête de la requête inclut automatiquement le jeton CSRF pour se prémunir contre les attaques de ce type.

**2. Back-End (`RideController.php`) : Le Point d'Entrée**

-   La méthode `book(int $id)` du contrôleur reçoit la requête.
-   Elle effectue une première vérification de sécurité : `!isset($_SESSION['user_id'])`.
-   Elle délègue ensuite l'intégralité de la logique métier au `BookingService` en appelant `createBooking($rideId, $userId)`.
-   Elle se contente de "traduire" le résultat du service (succès ou exception) en une réponse JSON appropriée avec le bon code de statut HTTP (200 pour succès, 400 pour erreur métier).

**3. Back-End (`BookingService.php`) : Le Cœur Transactionnel**

La méthode `createBooking()` est le centre névralgique de l'opération. Elle est conçue pour être atomique et sécurisée face à la concurrence.

-   **Transaction SQL** : L'ensemble de la méthode est encapsulé dans un bloc `try/catch` et une transaction de base de données (`$pdo->beginTransaction()`). Cela garantit l'atomicité : soit **toutes** les opérations (vérifications, débit des crédits, création de la réservation) réussissent et la transaction est validée (`$pdo->commit()`), soit une seule d'entre elles échoue et l'ensemble des opérations est annulé (`$pdo->rollBack()`). Cela empêche des états incohérents, comme un utilisateur perdant des crédits sans avoir de réservation.

-   **Verrouillage Pessimiste (`SELECT ... FOR UPDATE`)** : C'est le mécanisme clé contre les **conditions de concurrence (race conditions)**.
    -   **Problème** : Sans verrouillage, si deux utilisateurs tentent de réserver la dernière place en même temps, les deux scripts pourraient lire "1 place disponible" et tous deux tenteraient de procéder, menant à une surréservation.
    -   **Solution** : La ligne `$ride = $this->rideRepository->findByIdForUpdate($rideId);` exécute un `SELECT` qui place un **verrou exclusif** sur la ligne du trajet dans la base de données. Le premier script qui exécute cette ligne obtient le verrou. Si un deuxième script tente d'exécuter la même requête, la base de données le met en attente. Il ne pourra lire la ligne du trajet que lorsque la première transaction sera terminée (commit ou rollback). À ce moment, la place aura déjà été prise, et la vérification des places disponibles pour le deuxième utilisateur échouera logiquement. Ce verrouillage est la justification technique principale pour garantir l'intégrité des données dans un environnement multi-utilisateurs.

-   **Validations Métier** : Une série de vérifications est effectuée à l'intérieur de la transaction : l'utilisateur n'est pas le conducteur, le trajet est toujours planifié, les crédits sont suffisants, l'utilisateur n'a pas déjà réservé, et (grâce au verrou) les places sont réellement disponibles. Si l'une de ces conditions échoue, une `Exception` est levée, ce qui déclenche le `catch` et le `rollBack`.

-   **Opérations de base de données** : Si toutes les vérifications passent, le service met à jour les crédits de l'utilisateur et insère la nouvelle ligne dans la table `bookings`.

### 2) Gérer son profil et choisir son rôle (chauffeur, passager, les deux)

La page "Mon Compte" centralise la gestion des données de l'utilisateur. Elle est initialement rendue par le serveur, puis toutes les mises à jour sont effectuées via des appels API asynchrones en JavaScript, offrant une expérience réactive.

**1. Affichage initial de la page (`GET /account`)**

-   **Contrôleur (`UserController.php`)** : La méthode `account()` est appelée. Elle utilise un `AuthHelper` pour récupérer l'objet `User` de l'utilisateur actuellement connecté (via `$_SESSION['user_id']`). Elle rend ensuite la vue `account/index.php` en lui passant cet objet `$user`.
-   **Vue (`account/index.php`)** : La vue utilise les données de l'objet `$user` pour pré-remplir tous les champs et formulaires. Elle utilise des conditions pour afficher ou masquer certaines sections, comme le bloc "Informations Chauffeur" qui n'est visible que si le `functional_role` de l'utilisateur est 'driver' ou 'passenger_driver'.

**2. Mise à jour du rôle fonctionnel (API `POST /account/update-role`)**

-   **Front-End (`accountPage.js`)** : Un script JS écoute la soumission du formulaire de rôle. Il empêche la soumission par défaut, lit la valeur du bouton radio sélectionné, et envoie une requête `POST` à l'API avec le nouveau rôle.
-   **Contrôleur (`UserController.php`)** : La méthode `updateRole()` reçoit la requête. Elle délègue la logique au `UserRoleService`.
-   **Service (`UserRoleService.php`)** : La méthode `updateFunctionalRole()` effectue la mise à jour.
    1.  Elle met à jour la propriété `functional_role` de l'objet `User` en base de données via le `UserRepository`.
    2.  **Point crucial** : Après la mise à jour en BDD, le contrôleur met à jour la session de l'utilisateur : `$_SESSION['user_roles']`. Il recalcule le tableau des rôles pour y inclure le nouveau rôle fonctionnel (ex: `ROLE_DRIVER`). Cette étape est essentielle pour que les changements soient immédiatement visibles dans l'interface (par exemple, pour faire apparaître le lien "Publier un trajet" dans la `navbar` sans que l'utilisateur ait à se déconnecter/reconnecter).
-   **Réponse** : Le service retourne un statut de succès, et le contrôleur le transforme en JSON. Le JS côté client peut alors afficher une notification de succès et, si nécessaire, masquer/afficher dynamiquement des sections de la page (comme le bloc "Informations Chauffeur").

**3. Mise à jour des préférences et autres informations**

Ce processus suit le même schéma pour les autres actions (préférences, informations personnelles, mot de passe) :

-   **Séparation des responsabilités** : Chaque type de mise à jour est géré par un service dédié (`DriverPreferenceService`, `UserAccountService`). Cette séparation rend le code plus propre et plus facile à maintenir que si toute la logique était concentrée dans le `UserController` ou un unique `UserService`.
-   **Validation** : La validation des données (ex: vérifier que le mot de passe actuel est correct avant de le changer) est toujours effectuée côté serveur, dans le service approprié. C'est une règle de sécurité fondamentale.
-   **Interaction** : Le front-end envoie les données via une API `POST`, le contrôleur agit comme un simple passe-plat vers le service compétent, et le service exécute la logique métier et la persistance des données.

Cette architecture modulaire, où la page est rendue une fois par le serveur puis mise à jour par de multiples petites requêtes API asynchrones, est typique des applications web modernes. Elle offre un bon équilibre entre la performance du rendu initial et la réactivité de l'interface.

### 3) Consulter l'historique des covoiturages

Cette fonctionnalité adopte une architecture hybride : la page est initialement rendue par le serveur, mais le contenu principal (les listes de trajets) est chargé et paginé de manière asynchrone côté client.

**1. Rendu Initial de la Page (`GET /your-rides`)**

-   **Contrôleur (`RideController.php`)** : La méthode `yourRides()` vérifie d'abord l'authentification. Contrairement à la page de recherche, elle pré-charge les données des trajets (`upcoming`, `past`, `all`) via le `RideService`.
-   **Vue (`your-rides.php`)** : La vue reçoit ces données. Cependant, une analyse du code révèle que ces données pré-chargées ne sont **pas directement utilisées pour le rendu initial**. La vue se contente de construire la structure HTML (les onglets "à venir", "passés", etc.) et d'inclure le script `yourRidesPageHandler.js`. Ce pré-chargement est donc potentiellement une redondance ou un vestige d'une ancienne implémentation. La véritable logique d'affichage est entièrement déléguée au JavaScript.

**2. Logique Front-End (`yourRidesPageHandler.js`)**

-   **Orchestration** : Ce script prend le contrôle total de la page. Au chargement, il initialise des gestionnaires pour les onglets, la pagination, et les actions sur les cartes. Il lance immédiatement un premier appel API pour peupler l'onglet actif par défaut ("Trajets à venir").
-   **Appel API** : La fonction `loadUserRides(type, page)` est au cœur du script. Elle appelle le point de terminaison de l'API `GET /api/user-rides` avec des paramètres pour le type de trajet (`upcoming`, `past`, `all`) et la pagination (`page`, `limit`).
-   **Rendu Dynamique** : Lorsque les données JSON sont reçues, le script vide le conteneur approprié et génère dynamiquement les cartes de trajet (`RideCard`) pour chaque élément reçu, en utilisant le même système de `<template>` que la page de recherche. Il met également à jour les contrôles de pagination.

**3. API et Logique Métier Back-End**

-   **Contrôleur (`RideController.php`)** : La méthode `getUserRidesApi()` sert de point d'entrée. Elle valide les paramètres (`type`, `page`), puis appelle les services correspondants.
    -   **Composition de Services** : C'est un bon exemple de composition. Après avoir récupéré les trajets via `RideService`, la méthode boucle sur les résultats et appelle `ReviewService->hasUserReviewedRide()` pour chaque trajet terminé. Cela permet d'ajouter un drapeau `has_reviewed: true/false` aux données, que le front-end utilisera pour désactiver le bouton "Laisser un avis" si nécessaire.
-   **Service (`RideService.php`)** : La complexité réside dans la méthode `getUserRides()`.
    -   **Requête SQL Complexe** : Pour récupérer tous les trajets d'un utilisateur, le service doit trouver les trajets où l'ID de l'utilisateur correspond soit à `driver_id` dans la table `rides`, soit à `user_id` dans la table `bookings`. Ceci est réalisé en exécutant deux ensembles de requêtes distincts (un pour les trajets en tant que conducteur, un pour les trajets en tant que passager) et en fusionnant les résultats en PHP.
    -   **Filtrage temporel** : Les requêtes SQL contiennent des clauses `WHERE` pour filtrer les trajets "à venir" (`departure_time >= NOW()`) des trajets "passés" (`departure_time < NOW()` ou statut `completed`/`cancelled`).
    -   **Pagination** : Le service `countUserRides()` exécute des requêtes `COUNT` correspondantes pour permettre à l'API de retourner les informations de pagination totales.

En résumé, cette fonctionnalité démontre une architecture front-end réactive et une logique back-end capable de répondre à des requêtes de données complexes en agrégeant des informations de plusieurs tables et même de plusieurs services.

### 4) Valider le bon déroulement d’un trajet (transfert de crédits)

Cette fonctionnalité représente le "chemin alternatif" au processus de confirmation. Elle permet à un passager de contester le bon déroulement du trajet, ce qui a pour effet de suspendre le transfert des crédits et de notifier l'équipe de modération.

**1. Déclenchement et Accès au Formulaire**

-   **Point d'entrée (Email)** : Le processus débute avec l'email de demande de confirmation envoyé au passager lorsque le chauffeur termine le trajet. Cet email, généré par `EmailService`, contient deux liens distincts, tous deux utilisant le même jeton de réservation unique :
    1.  Un lien pour confirmer le trajet (`/confirm-ride?token=...`).
    2.  Un lien pour signaler un problème (`/report-ride?token=...`).
-   **Affichage du formulaire (`ReportController.php`)** : Lorsque le passager clique sur le lien de signalement, une requête `GET` est envoyée.
    -   La méthode `reportRide()` du contrôleur reçoit la requête.
    -   Elle valide immédiatement le jeton en appelant un service (ex: `ConfirmationService` ou `ReportService`) pour s'assurer qu'il correspond à une réservation valide et en attente de confirmation.
    -   Si le jeton est valide, le contrôleur rend une vue qui affiche le formulaire de signalement. Le jeton est inséré dans un champ caché (`<input type="hidden">`) du formulaire pour être renvoyé lors de la soumission.

**2. Soumission et Traitement du Signalement**

-   **Soumission (Front-End)** : L'utilisateur remplit le formulaire et le soumet, ce qui déclenche une requête `POST` vers `/api/reports`.
-   **Contrôleur (`ReportController.php`)** : La méthode `submitReport()` reçoit les données du formulaire (`$_POST`), y compris le jeton et le message de l'utilisateur. Elle délègue la totalité de la logique de création au `ReportService`.

**3. Logique Métier (`ReportService.php`)**

La méthode `createReport()` est le cœur de cette fonctionnalité. Elle doit être robuste pour garantir que le processus est juste et ne peut pas être abusé.

1.  **Re-validation du jeton** : Le service valide à nouveau le jeton pour se prémunir contre toute manipulation entre le chargement du formulaire et sa soumission.
2.  **Logique anti-doublons** : Le service vérifie si un signalement avec le statut "new" ou "under_investigation" existe déjà pour cette réservation. Cela empêche un utilisateur de soumettre plusieurs signalements pour le même problème.
3.  **Changement de statut de la réservation** : C'est l'étape la plus critique du processus. Le service met à jour le statut de la réservation (`bookings`) en le passant de `confirmed_pending_passenger_confirmation` à `reported_by_passenger`. Ce changement de statut a deux effets majeurs :
    -   Il **bloque** le transfert de crédits. Le `ConfirmationService` ne pourra plus agir sur cette réservation.
    -   Il **signale** cette réservation comme nécessitant une intervention manuelle.
4.  **Création du signalement** : Une nouvelle entrée est créée dans la table `reports`, contenant le message de l'utilisateur, l'ID de la réservation, et un statut initial (ex: "new").
5.  **Notification (Optionnel)** : Le service pourrait également être configuré pour envoyer une notification (email, etc.) à l'équipe de modération pour les informer qu'un nouveau signalement a été créé.

Le résultat de ce processus est qu'une réservation "litigieuse" est mise de côté, avec les crédits associés gelés, en attente de la décision d'un modérateur qui sera traitée dans le cadre des fonctionnalités de l'Espace Employé.

### 5) Créer un signalement si trajet mal déroulé (passager)

Cette fonctionnalité représente le "chemin alternatif" au processus de confirmation. Elle permet à un passager de contester le bon déroulement du trajet, ce qui a pour effet de suspendre le transfert des crédits et de notifier l'équipe de modération.

**1. Déclenchement et Accès au Formulaire**

-   **Point d'entrée (Email)** : Le processus débute avec l'email de demande de confirmation envoyé au passager lorsque le chauffeur termine le trajet. Cet email, généré par `EmailService`, contient deux liens distincts, tous deux utilisant le même jeton de réservation unique :
    1.  Un lien pour confirmer le trajet (`/confirm-ride?token=...`).
    2.  Un lien pour signaler un problème (`/report-ride?token=...`).
-   **Affichage du formulaire (`ReportController.php`)** : Lorsque le passager clique sur le lien de signalement, une requête `GET` est envoyée.
    -   La méthode `reportRide()` du contrôleur reçoit la requête.
    -   Elle valide immédiatement le jeton en appelant un service (ex: `ConfirmationService` ou `ReportService`) pour s'assurer qu'il correspond à une réservation valide et en attente de confirmation.
    -   Si le jeton est valide, le contrôleur rend une vue qui affiche le formulaire de signalement. Le jeton est inséré dans un champ caché (`<input type="hidden">`) du formulaire pour être renvoyé lors de la soumission.

**2. Soumission et Traitement du Signalement**

-   **Soumission (Front-End)** : L'utilisateur remplit le formulaire et le soumet, ce qui déclenche une requête `POST` vers `/api/reports`.
-   **Contrôleur (`ReportController.php`)** : La méthode `submitReport()` reçoit les données du formulaire (`$_POST`), y compris le jeton et le message de l'utilisateur. Elle délègue la totalité de la logique de création au `ReportService`.

**3. Logique Métier (`ReportService.php`)**

La méthode `createReport()` est le cœur de cette fonctionnalité. Elle doit être robuste pour garantir que le processus est juste et ne peut pas être abusé.

1.  **Re-validation du jeton** : Le service valide à nouveau le jeton pour se prémunir contre toute manipulation entre le chargement du formulaire et sa soumission.
2.  **Logique anti-doublons** : Le service vérifie si un signalement avec le statut "new" ou "under_investigation" existe déjà pour cette réservation. Cela empêche un utilisateur de soumettre plusieurs signalements pour le même problème.
3.  **Changement de statut de la réservation** : C'est l'étape la plus critique du processus. Le service met à jour le statut de la réservation (`bookings`) en le passant de `confirmed_pending_passenger_confirmation` à `reported_by_passenger`. Ce changement de statut a deux effets majeurs :
    -   Il **bloque** le transfert de crédits. Le `ConfirmationService` ne pourra plus agir sur cette réservation.
    -   Il **signale** cette réservation comme nécessitant une intervention manuelle.
4.  **Création du signalement** : Une nouvelle entrée est créée dans la table `reports`, contenant le message de l'utilisateur, l'ID de la réservation, et un statut initial (ex: "new").
5.  **Notification (Optionnel)** : Le service pourrait également être configuré pour envoyer une notification (email, etc.) à l'équipe de modération pour les informer qu'un nouveau signalement a été créé.

Le résultat de ce processus est qu'une réservation "litigieuse" est mise de côté, avec les crédits associés gelés, en attente de la décision d'un modérateur qui sera traitée dans le cadre des fonctionnalités de l'Espace Employé.

### 6) Laisser un avis et une note après un trajet

Cette fonctionnalité permet aux passagers de donner leur feedback, un élément essentiel pour la confiance au sein de la communauté. Le processus est conçu pour s'assurer que seuls les passagers légitimes peuvent laisser un avis, et ce, une seule fois par trajet.

**1. Affichage Conditionnel du Bouton "Laisser un avis"**

-   **Enrichissement des données (Back-End)** : La logique commence dans le `RideController`, au sein de la méthode `getUserRidesApi()`. Après avoir récupéré la liste des trajets d'un utilisateur, le contrôleur itère sur chaque trajet. Si un trajet est terminé et que l'utilisateur était un passager, il appelle une méthode dédiée du `ReviewService` : `hasUserReviewedRide($userId, $rideId)`.
-   **Vérification en base de données** : Cette méthode de service effectue une requête `SELECT COUNT(*)` dans la table `reviews` pour vérifier si une entrée existe déjà pour cette combinaison `user_id` / `ride_id`.
-   **Drapeau pour le Front-End** : Le résultat de cette vérification (un booléen `true` ou `false`) est ajouté à l'objet du trajet sous la forme d'un drapeau `has_reviewed` avant d'être envoyé au client en JSON. Cette préparation des données côté serveur simplifie grandement la logique côté client.
-   **Rendu (Front-End)** : Le script `yourRidesPageHandler.js`, lors de la création de la carte du trajet, lit ce drapeau `ride.has_reviewed`. Si le drapeau est `true`, il affiche un bouton désactivé "Avis soumis". S'il est `false`, il affiche le bouton cliquable "Laisser un avis".

**2. Soumission de l'Avis via une Modale**

-   **Interaction (Front-End)** : Un clic sur le bouton "Laisser un avis" n'entraîne pas une navigation mais ouvre une fenêtre modale (`#reviewModal`). Le script `yourRidesPageHandler.js` (via son `ReviewModalHandler`) peuple cette modale avec les informations du trajet concerné.
-   **Soumission API** : L'utilisateur sélectionne une note et rédige un commentaire. À la soumission du formulaire de la modale, le script envoie une requête `POST` à l'API `/api/reviews`, contenant l'ID du trajet, la note et le commentaire.

**3. Traitement et Sécurité (Back-End)**

-   **Contrôleur (`ReviewController.php`)** : La méthode `store()` reçoit la requête, récupère l'ID de l'utilisateur depuis la session et délègue la création de l'avis au `ReviewService`.
-   **Service (`ReviewService.php`)** : La méthode `createReview()` exécute la logique métier et les contrôles de sécurité :
    1.  **Validation des entrées** : Elle valide les données reçues (ex: la note est bien un entier entre 1 et 5).
    2.  **Vérification d'autorisation** : C'est une étape de sécurité cruciale qui est re-faite côté serveur. Le service vérifie à nouveau que l'utilisateur a bien le droit de laisser cet avis :
        -   Le trajet existe et son statut est "completed".
        -   L'utilisateur était bien un passager de ce trajet (en vérifiant la table `bookings`).
        -   L'utilisateur n'a pas déjà laissé d'avis pour ce trajet (protection contre les doubles soumissions si l'interface utilisateur a un bug ou est malveillamment contournée).
    3.  **Persistance en base de données** : Si toutes les vérifications sont positives, une nouvelle ligne est insérée dans la table `reviews`.
    4.  **Statut `pending_approval`** : L'avis n'est pas publié immédiatement. Il est inséré avec le statut `pending_approval`. C'est une règle métier importante qui assure qu'aucun contenu inapproprié n'est publié sans vérification. Cet avis sera désormais visible dans l'interface de modération des employés.

Ce flux garantit un processus de feedback sécurisé, fiable et modéré, ce qui est vital pour la qualité du service.

---

## C. Espace Chauffeur

### 1) Enregistrer ses véhicules et ses préférences

Cette section est gérée comme une mini-application CRUD (Create, Read, Update, Delete) au sein de la page "Mon Compte", entièrement pilotée par JavaScript via des appels API.

-   **Front-End (`accountPage.js`)** : Le script gère l'affichage et l'interaction avec la section "Informations Chauffeur". Il est responsable de :
    1.  Afficher/masquer le formulaire d'ajout/modification de véhicule.
    2.  Peupler le formulaire avec les données d'un véhicule pour la modification.
    3.  Lancer les appels API pour chaque action CRUD.
    4.  Rafraîchir dynamiquement la liste des véhicules après chaque opération réussie, sans recharger la page.
-   **Contrôleur (`VehicleController.php`)** : Il expose les points de terminaison de l'API (`/api/vehicles`, `/api/vehicles/{id}/update`, etc.). Son rôle est de recevoir les requêtes HTTP, de vérifier les droits de l'utilisateur et de déléguer l'action au service métier.
-   **Service (`VehicleManagementService.php`)** : Il contient la logique métier pour la gestion des véhicules.

**Flux Détaillé des Opérations CRUD**

-   **Read (Lister les véhicules)**
    -   Au chargement de la page, `accountPage.js` appelle l'API `GET /api/user/vehicles`.
    -   `VehicleController::getUserVehiclesApi()` récupère l'ID de l'utilisateur en session et demande au `VehicleService` tous les véhicules associés.
    -   Le résultat est renvoyé en JSON et le script front-end construit la liste en HTML.

-   **Create (Ajouter un véhicule)**
    -   L'utilisateur remplit le formulaire et soumet. Le JS envoie une requête `POST` à `/api/vehicles` avec les données du formulaire.
    -   `VehicleController::add()` appelle `VehicleManagementService::addVehicle()`.
    -   Le service valide les données (format de la plaque, etc.).
    -   Il tente d'insérer le nouveau véhicule en base de données. La table `vehicles` possède une **contrainte d'unicité (`UNIQUE`)** sur la colonne `license_plate`. Si la plaque existe déjà, la base de données renverra une `PDOException` avec un `SQLSTATE` de `23000`. Le service est conçu pour intercepter cette exception spécifique et la transformer en un message d'erreur clair pour l'utilisateur ("Cette plaque d'immatriculation est déjà enregistrée."), plutôt que de laisser l'application crasher avec une erreur 500.

-   **Update (Mettre à jour un véhicule)**
    -   L'utilisateur clique sur "Modifier", le JS remplit le formulaire avec les données du véhicule et passe en "mode édition". À la soumission, une requête `POST` est envoyée à `/api/vehicles/{id}/update`.
    -   **Contrôle de sécurité (`VehicleController.php`)** : Avant d'appeler le service, le contrôleur effectue la vérification la plus importante : **le véhicule à modifier appartient-il bien à l'utilisateur connecté ?** Il récupère le véhicule de la BDD, et compare son `user_id` avec celui de la session. Cela empêche un utilisateur malveillant de modifier les véhicules d'un autre utilisateur en forgeant une requête avec un autre ID.
    -   Si la vérification réussit, `VehicleManagementService::updateVehicle()` est appelé pour persister les changements.

-   **Delete (Supprimer un véhicule)**
    -   Le flux est identique à la mise à jour : le JS envoie une requête `POST` à `/api/vehicles/{id}/delete`.
    -   Le `VehicleController` effectue le **même contrôle de propriété** pour s'assurer que l'utilisateur ne peut supprimer que ses propres véhicules.
    -   `VehicleManagementService::deleteVehicle()` est appelé. Une logique métier additionnelle pourrait (et devrait) être ajoutée ici pour empêcher la suppression d'un véhicule s'il est actuellement associé à un trajet planifié ou en cours.

### 2) Publier une annonce de covoiturage

Cette fonctionnalité permet à un chauffeur de proposer un nouveau trajet. Le processus est initié par l'utilisateur via un formulaire dédié et traité par une API sécurisée.

**1. Affichage et Initialisation du Formulaire**

-   **Accès à la page** : L'utilisateur accède à `/publish-ride`. Le routeur s'assure en amont que l'utilisateur est connecté et possède le rôle `ROLE_DRIVER` ou `ROLE_PASSENGER_DRIVER`.
-   **Contrôleur (`RideController.php`)** : La méthode `publishForm()` rend simplement la vue `rides/publish.php` et y attache le script `publishRidePage.js`.
-   **Logique Front-End (`publishRidePage.js`)** : Ce script rend la page interactive.
    -   **Chargement des véhicules** : Sa première action est de lancer un appel API `GET /api/user/vehicles` pour récupérer la liste des véhicules enregistrés par le chauffeur.
    -   **Remplissage dynamique** : Il utilise la réponse de cet appel pour peupler dynamiquement le champ `<select>` du formulaire, permettant au chauffeur de choisir parmi ses véhicules. Sans véhicule enregistré, l'utilisateur ne peut pas publier de trajet.

**2. Soumission et Traitement de l'Annonce**

-   **Soumission (Front-End)** : L'utilisateur remplit tous les champs (villes, adresses, date, heure, prix, places) et soumet le formulaire. Le script `publishRidePage.js` intercepte la soumission, effectue des validations côté client (ex: la date de départ ne peut être passée), puis envoie toutes les données via une requête `POST` à l'API `/publish-ride` avec un corps JSON.
-   **Contrôleur (`RideController.php`)** : La méthode `publish()` reçoit la requête.
    -   Elle récupère l'ID du chauffeur depuis la session (`$_SESSION['user_id']`).
    -   Elle délègue la création au `RideService` en l'enveloppant dans un bloc `try/catch`. Ce bloc est conçu pour intercepter spécifiquement les `ValidationException` lancées par le service, afin de retourner des erreurs structurées au front-end.
-   **Service (`RideService.php`)** : La méthode `createRide()` exécute la logique métier et les contrôles de sécurité.
    1.  **Validation des données (`ValidationService`)** : La première étape est un appel à `ValidationService::validateRideCreation()`. Ce service externe valide rigoureusement chaque champ (type de données, format, plages de valeurs). Si une ou plusieurs règles ne sont pas respectées, il lève une `ValidationException` qui contient un tableau détaillé des erreurs par champ.
    2.  **Vérification de Propriété (Sécurité)** : C'est une vérification d'autorisation cruciale. Le service s'assure que l'ID du véhicule (`vehicle_id`) fourni dans la requête appartient bien au chauffeur (`driverId`) qui effectue la demande. Pour cela, il récupère le véhicule de la base de données et compare son `user_id`.
    3.  **Persistance** : Si toutes les validations et vérifications de sécurité réussissent, un nouvel objet `Ride` est créé, et ses données sont insérées dans la table `rides` avec le statut par défaut `planned`.

**3. Gestion de la Réponse**

-   **En cas de succès** : Le contrôleur renvoie une réponse JSON de succès. Le script JS affiche alors un message de confirmation et redirige l'utilisateur vers sa page "Mes Trajets".
-   **En cas d'échec de validation** : Le contrôleur intercepte la `ValidationException`, et renvoie une réponse JSON avec un code d'erreur 422 (Unprocessable Entity) et un corps contenant le tableau des erreurs (ex: `{ "success": false, "errors": { "price_per_seat": "Le prix doit être une valeur numérique." } }`). Le script `publishRidePage.js` est conçu pour lire ce tableau et afficher chaque message d'erreur à côté du champ de formulaire correspondant, offrant un retour très précis à l'utilisateur.

### 3) Démarrer et terminer un trajet

Ces deux actions représentent les transitions d'état clés dans le cycle de vie d'un trajet, gérées par le chauffeur. Elles transforment le trajet de `planned` à `ongoing`, puis de `ongoing` à `completed_pending_confirmation`.

**1. Démarrer le Trajet**

-   **Déclenchement (Front-End)** : Sur la page "Mes Trajets", le script `yourRidesPageHandler.js` affiche un bouton "Démarrer le trajet" pour chaque trajet dont le statut est `planned` et où l'utilisateur connecté est le chauffeur. Un clic sur ce bouton, après une boîte de dialogue de confirmation, déclenche un appel `POST` à l'API `/api/rides/{id}/start`.
-   **Contrôleur (`RideController.php`)** : La méthode `start()` reçoit la requête, vérifie que l'utilisateur est authentifié, et délègue l'opération au `RideService`.
-   **Logique Métier (`RideService.php`)** : La méthode `startRide()` est conçue pour être sécurisée et robuste.
    1.  **Transaction et Verrouillage** : L'opération est enveloppée dans une transaction et utilise `SELECT ... FOR UPDATE` pour verrouiller la ligne du trajet. Cela empêche des actions concurrentes, par exemple un passager qui tenterait d'annuler sa réservation à la seconde même où le chauffeur démarre le trajet.
    2.  **Vérification d'Autorisation** : Le service vérifie que l'ID de l'utilisateur en session correspond bien au `driver_id` du trajet.
    3.  **Validation d'État** : Il s'assure que le statut actuel du trajet est bien `planned`. C'est une règle de "machine à états" : on ne peut pas démarrer un trajet qui est déjà en cours, terminé ou annulé.
    4.  **Mise à jour** : Si les vérifications réussissent, le service met à jour le statut du trajet en base de données, le faisant passer de `planned` à `ongoing`.
-   **Réponse et UI** : L'API renvoie un message de succès. Le script JS rafraîchit alors la liste des trajets, où le trajet démarré apparaîtra maintenant avec son nouveau statut et un bouton "Arrivée à destination".

**2. Terminer le Trajet**

-   **Déclenchement (Front-End)** : Le processus est identique au démarrage. Pour un trajet au statut `ongoing`, le chauffeur dispose d'un bouton "Arrivée à destination". Un clic déclenche un appel `POST` à l'API `/api/rides/{id}/finish`.
-   **Contrôleur et Service** : Le `RideController` passe la main au `RideService`. La méthode `finishRide()` prend le relais.
-   **Logique Métier (`RideService.php`)** : Cette méthode, déjà détaillée en B.4, est la porte d'entrée vers le processus de confirmation par le passager.
    1.  Elle effectue les mêmes vérifications transactionnelles, d'autorisation et d'état (le trajet doit être `ongoing`).
    2.  Elle change le statut du trajet à `completed_pending_confirmation`.
    3.  Elle initie le processus de confirmation en générant des jetons uniques pour chaque réservation et en demandant au `EmailService` d'envoyer les emails de confirmation aux passagers.

Ensemble, ces deux fonctionnalités forment une machine à états simple mais robuste, garantissant que les trajets progressent dans leur cycle de vie de manière ordonnée et sécurisée, avec des actions qui ne sont disponibles pour le chauffeur que lorsque le trajet est dans l'état approprié.

### 4) Annuler un covoiturage (emails automatiques)

Cette fonctionnalité est gérée par un unique point d'API (`POST /api/rides/{id}/cancel`), mais sa logique métier est radicalement différente selon que l'initiateur est le chauffeur ou un passager.

**1. Déclenchement et Appel API (Front-End)**

-   **Interface utilisateur contextuelle** : Sur la page "Mes Trajets", le script `yourRidesPageHandler.js` affiche un bouton "Annuler" sur les trajets planifiés. Le texte et l'action de confirmation diffèrent cependant :
    -   Pour le chauffeur : "Annuler ce trajet".
    -   Pour un passager : "Annuler ma réservation".
-   **Appel API unique** : Quel que soit le rôle, un clic sur le bouton (après confirmation) déclenche un appel `POST` vers le même point de terminaison d'API. L'identité de l'utilisateur (`$_SESSION['user_id']`) est la clé qui permettra au back-end de faire la distinction.

**2. Aiguillage et Logique Métier (Back-End)**

-   **Contrôleur (`RideController.php`)** : La méthode `cancel()` reçoit la requête et délègue immédiatement le traitement au `BookingService` en lui passant l'ID du trajet et l'ID de l'utilisateur qui a initié l'action.
-   **Service (`BookingService.php`)** : La méthode `cancelRide()` est le centre de décision.
    1.  **Transaction et Verrouillage** : L'opération est entièrement transactionnelle et verrouille la ligne du trajet (`FOR UPDATE`) pour garantir la cohérence des données.
    2.  **Branchement Logique** : La première action du service est de déterminer le rôle de l'appelant :
        ```php
        if ($ride->getDriverId() === $userId) {
            // Scénario 1: Le chauffeur annule
        } else {
            // Scénario 2: Un passager annule
        }
        ```

**Scénario 1 : Le Chauffeur Annule**

-   **Impact global** : L'annulation est pour tout le monde. Le service entre dans une boucle qui traite chaque réservation associée au trajet.
-   **Remboursement** : Pour chaque passager, le service rembourse intégralement le montant de la réservation en créditant son compte utilisateur.
-   **Notification** : Après chaque remboursement, `EmailService->sendRideCancellationEmailToPassenger()` est appelé pour envoyer un email de notification informant le passager de l'annulation et de son remboursement.
-   **Nettoyage** : Les enregistrements de `bookings` sont supprimés.
-   **Mise à jour du statut** : Le statut du trajet lui-même est mis à jour à `cancelled_driver`.

**Scénario 2 : Un Passager Annule**

-   **Impact local** : L'annulation ne concerne que cet utilisateur.
-   **Remboursement** : Le service trouve la réservation spécifique de cet utilisateur, et lui rembourse ses crédits.
-   **Nettoyage** : Seul l'enregistrement de la réservation de cet utilisateur est supprimé de la table `bookings`. Le trajet reste `planned` et disponible pour les autres passagers et le chauffeur.

**Journalisation (`MongoLogService.php`)**

-   Dans les deux scénarios, après chaque opération d'annulation (qu'elle soit globale ou locale), un document est enregistré dans la collection `cancellations` de MongoDB via `mongoLogService->logCancellation()`. Ce document contient l'ID du trajet, l'ID de l'utilisateur concerné, et la raison de l'annulation (`cancelled_by_driver` ou `cancelled_by_passenger`). Cela fournit une piste d'audit claire et flexible pour le suivi des opérations.

Cette architecture à double scénario au sein d'un même service transactionnel est une méthode efficace pour gérer des logiques métier complexes qui dépendent du rôle de l'utilisateur, tout en garantissant l'intégrité des données financières (les crédits).

---

## D. Espace Employé

### 1) Modérer les avis (valider / refuser)

Cette fonctionnalité est le point de contrôle qualité pour les avis soumis par les utilisateurs. Elle fournit une interface à l'équipe de modération pour approuver ou rejeter les avis, bouclant ainsi le processus initié en B.6.

**1. Affichage de la file de modération**

-   **Accès et Rendu Initial** : Un utilisateur avec le rôle `ROLE_EMPLOYEE` accède à la page `/employee-dashboard`. Le `EmployeeController::manageReviews()` rend la vue de base.
-   **Chargement Asynchrone** : Un script JavaScript dédié à cette page effectue immédiatement un appel `GET` à l'API `/api/employee-dashboard/reviews/pending`.
-   **Récupération des Données (`ModerationService.php`)** : Le contrôleur délègue au `ModerationService`. La méthode `getPendingReviews()` exécute une requête `SELECT` sur la table `reviews` avec une clause `WHERE status = 'pending_approval'`. La requête inclut des jointures avec les tables `users` et `rides` pour récupérer le contexte nécessaire (le nom de l'auteur de l'avis, le nom du chauffeur noté, les détails du trajet, etc.).
-   **Affichage (Front-End)** : Le script JS reçoit la liste des avis en attente au format JSON et construit dynamiquement l'interface, en affichant chaque avis avec son contenu et deux boutons d'action : "Approuver" et "Rejeter".

**2. Processus d'Approbation d'un Avis**

-   **Action** : L'employé clique sur "Approuver". Le JS envoie une requête `POST` à `/api/employee-dashboard/reviews/{id}/approve`.
-   **Contrôleur (`EmployeeController.php`)** : La méthode `approveReviewApi()` reçoit la requête et appelle `ModerationService->approveReview()`.
-   **Logique Métier (`ModerationService.php`)** : L'approbation est un processus en deux temps qui démontre la **composition de services** :
    1.  **Mise à jour du statut** : Le service met à jour le statut de l'avis dans la table `reviews` de `pending_approval` à `approved`. L'avis sera désormais visible publiquement (par exemple, sur le profil du chauffeur).
    2.  **Recalcul de la note** : Le `ModerationService` **appelle un autre service**, le `RatingService`, pour recalculer la note moyenne du chauffeur concerné.
-   **Logique de Notation (`RatingService.php`)** : Ce service spécialisé a une seule responsabilité :
    1.  Il récupère **tous les avis approuvés** pour ce chauffeur.
    2.  Il calcule la nouvelle note moyenne.
    3.  Il met à jour le champ `driver_rating` dans la table `users` pour le chauffeur.
    Cette séparation garantit que la logique de notation est centralisée et peut être réutilisée ailleurs si nécessaire, tout en gardant le `ModerationService` concentré sur son rôle de modération.

**3. Processus de Rejet d'un Avis**

-   **Action** : L'employé clique sur "Rejeter". Le JS envoie une requête `POST` à `/api/employee-dashboard/reviews/{id}/reject`.
-   **Logique Métier (`ModerationService.php`)** : Le processus est plus simple. La méthode `rejectReview()` met simplement à jour le statut de l'avis à `rejected`. L'avis est conservé en base de données à des fins d'archivage et de traçabilité, mais il ne sera jamais affiché publiquement et n'aura aucun impact sur la note du chauffeur.

Après chaque action (approbation ou rejet), le script JS retire l'avis traité de la liste à l'écran, fournissant un retour visuel immédiat à l'employé.

### 2) Gérer les signalements “mal déroulés”

Cette fonctionnalité est le tribunal de l'application. C'est ici que les litiges initiés par les passagers en B.5 sont résolus par un modérateur, qui décide du sort des crédits en attente.

**1. Affichage de la file de signalements**

-   **Chargement des données** : L'interface de l'employé, via son script JavaScript, appelle le point d'API `GET /api/employee-dashboard/reports/pending`.
-   **Service (`ModerationService.php`)** : La méthode `getPendingReports()` est invoquée. Elle interroge la base de données pour tous les enregistrements de la table `reports` ayant le statut `new`. La requête effectue les jointures nécessaires pour récupérer tout le contexte : le message du passager, les détails du trajet, l'identité du passager et du chauffeur.
-   **Affichage (Front-End)** : Le script JS reçoit la liste des signalements et les affiche à l'employé, en présentant clairement toutes les informations et en proposant un ensemble d'actions possibles pour chaque cas.

**2. Actions de Résolution**

L'employé dispose de plusieurs actions pour traiter un signalement. Chaque action déclenche un appel API distinct.

-   **Cas 1 : Résoudre en faveur du chauffeur (Créditer le chauffeur)**
    -   **Action** : L'employé clique sur "Créditer le chauffeur". Le JS appelle `POST /api/employee-dashboard/reports/{id}/credit-driver`.
    -   **Logique Métier (`ModerationService.php`)** : La méthode `creditDriverFromReport()` est appelée.
        -   **Réutilisation de la logique métier** : C'est un point d'architecture très important. Au lieu de réécrire la logique de transfert de crédits, cette méthode **appelle `ConfirmationService->processCreditTransferForBooking()`**. C'est exactement la même méthode qui est utilisée lorsqu'un passager confirme un trajet normally. Cette réutilisation garantit que le processus de paiement (calcul de la commission, transfert, journalisation dans MongoDB) est **identique et cohérent**, qu'il provienne d'une confirmation de passager ou d'une décision de modérateur.
        -   **Mise à jour des statuts** : Une fois le transfert réussi, le statut du signalement (`reports`) est mis à jour à `resolved_driver_credited` et celui de la réservation (`bookings`) à `confirmed_and_credited`. Le cas est clos.

-   **Cas 2 : Résoudre en faveur du passager (non détaillé dans le code source mais implicite)**
    -   Logiquement, il existerait un bouton "Rembourser le passager".
    -   L'action appellerait une méthode `refundPassengerFromReport()` dans le `ModerationService`.
    -   Cette méthode serait transactionnelle, recréditerait le compte du passager, et mettrait à jour les statuts du signalement et de la réservation en conséquence (ex: `resolved_passenger_refunded`).

-   **Cas 3 : Investigation (Prendre contact)**
    -   **Action** : Si le cas est ambigu, l'employé peut cliquer sur "Contacter le chauffeur".
    -   **Logique d'email (`EmailService.php`)** : L'API correspondante appelle directement `EmailService->sendEmailFromReportModeration()`. Ce service envoie un email pré-formaté au chauffeur pour lui demander sa version des faits.
    -   **Mise à jour de statut** : Cette action pourrait également changer le statut du signalement à `under_investigation`, indiquant qu'il est en cours de traitement mais pas encore résolu.

Cette approche par file d'attente et actions de modération permet de gérer les litiges de manière structurée, en s'appuyant sur des services métier existants pour garantir la cohérence des opérations financières.

---

## E. Espace Administrateur

### 1) Créer les comptes des employés

Cette fonctionnalité est une tâche administrative critique, permettant de provisionner des comptes avec des privilèges élevés. Le processus est sécurisé par le rôle `ROLE_ADMIN` et encapsule la création d'un utilisateur et l'assignation d'un rôle spécifique en une seule action.

**1. Interface et Appel API**

-   **Interface Administrateur** : La page `/admin-dashboard` (accessible uniquement aux administrateurs) contient un formulaire simple pour créer un nouvel employé, demandant généralement un email et un mot de passe initial.
-   **Appel API** : À la soumission du formulaire, le script JavaScript de la page envoie une requête `POST` à l'API `/api/admin/employees` avec les informations du nouvel employé.

**2. Traitement Back-End**

-   **Contrôleur (`AdminController.php`)** : La méthode `createEmployeeApi()` sert de point d'entrée. Le routeur a déjà validé que l'utilisateur effectuant l'appel est un administrateur. Le contrôleur délègue immédiatement la logique de création à l'`AdminService`.
-   **Service (`AdminService.php`)** : La méthode `createEmployee()` orchestre le processus de création en deux étapes distinctes, démontrant une bonne séparation des préoccupations.
    1.  **Création d'un utilisateur standard** : Le service commence par appeler le `UserService` pour créer un utilisateur de base. Il valide les données, vérifie l'unicité de l'email, hache le mot de passe, et insère une nouvelle ligne dans la table `users`. À ce stade, le nouvel utilisateur n'est qu'un utilisateur standard sans privilèges particuliers.
    2.  **Assignation du rôle spécifique** : Immédiatement après avoir obtenu l'ID du nouvel utilisateur, l'`AdminService` appelle `UserRoleService->assignRoleToUser($userId, 'ROLE_EMPLOYEE')`.

-   **Service (`UserRoleService.php`)** : C'est ici que le contrôle d'accès basé sur les rôles (RBAC) prend tout son sens.
    -   La méthode `assignRoleToUser()` reçoit l'ID de l'utilisateur et le nom du rôle à assigner.
    -   Elle effectue une recherche dans la table `roles` pour trouver l'ID correspondant au nom `ROLE_EMPLOYEE`.
    -   Elle insère ensuite une nouvelle entrée dans la **table de liaison (pivot)** `user_roles`. Cette table contient simplement des paires `user_id` / `role_id`, créant ainsi la relation "many-to-many" entre les utilisateurs et les rôles.
    -   C'est cette entrée dans la table `user_roles` qui confère formellement à l'utilisateur les permissions d'un employé, que le routeur et le reste de l'application utiliseront pour autoriser ou refuser l'accès aux fonctionnalités de modération.

Ce flux en deux temps (création de l'entité, puis assignation des droits) est une implémentation robuste et découplée du RBAC. Elle permet de gérer la logique des utilisateurs et la logique des rôles de manière indépendante.

### 2) Visualiser des statistiques (trajets, crédits)

Cette fonctionnalité est l'exemple parfait justifiant l'architecture à double base de données du projet. Tandis que MariaDB gère les données opérationnelles transactionnelles, MongoDB est utilisé ici comme un entrepôt de données optimisé pour l'analyse et l'agrégation.

**1. Architecture et Flux de Données**

-   **Journalisation d'Événements (Event Sourcing)** : Tout au long de la vie de l'application, des événements métier clés (fin d'un trajet, perception d'une commission, etc.) sont enregistrés comme des documents immuables dans des collections MongoDB dédiées (`ride_analytics`, `commissions`). Cette approche de "journalisation" est idéale pour une base NoSQL orientée document.
-   **Interface (Front-End)** : Le tableau de bord de l'administrateur contient des canevas HTML (`<canvas>`) pour les graphiques. Au chargement de la page, le script JavaScript lance plusieurs appels API en parallèle aux différents points de terminaison de statistiques (ex: `/api/admin/stats/rides`, `/api/admin/stats/credits_daily`).
-   **Rendu des Graphiques** : Pour chaque réponse reçue de l'API, le script JS utilise la bibliothèque `Chart.js` (déjà incluse dans le `layout.php`) pour dessiner un graphique (ex: un graphique en courbes montrant l'évolution du nombre de trajets par jour).

**2. Traitement Analytique (Back-End)**

-   **Contrôleur (`AdminController.php`)** : Le contrôleur joue un rôle de simple proxy. Chaque méthode de statistique (ex: `getRideStatsApi`) se contente d'appeler la méthode correspondante dans le `MongoLogService` et de renvoyer sa sortie en JSON.
-   **Service (`MongoLogService.php`)** : C'est ici que la puissance de MongoDB est exploitée via son **Framework d'Agrégation**. Ce framework permet de traiter de grands volumes de documents à travers un "pipeline" de plusieurs étapes pour les transformer en résultats agrégés.

**Analyse des Pipelines d'Agrégation**

-   **Statistiques par jour (`getCompletedRidesByDay`, `getCommissionsByDay`)** : Ces méthodes utilisent un pipeline en plusieurs étapes :
    1.  **`$match`** (Optionnel) : Une première étape pourrait filtrer les documents pour ne considérer qu'une période de temps spécifique (ex: les 30 derniers jours).
    2.  **`$group`** : C'est l'étape fondamentale. Elle regroupe tous les documents par une clé commune. Pour obtenir des statistiques journalières, la clé `_id` est une expression qui extrait la partie "jour" de la date de l'événement (ex: `{ $dateToString: { format: "%Y-%m-%d", date: "$timestamp" } }`). Le groupe définit aussi des **accumulateurs**, comme `count: { $sum: 1 }` pour compter le nombre de trajets, ou `total_commissions: { $sum: "$amount" }` pour additionner la valeur des commissions.
    3.  **`$sort`** : Les résultats groupés sont ensuite triés par la clé `_id` (la date) pour s'assurer que les données arrivent au front-end dans l'ordre chronologique, prêtes à être affichées sur un axe temporel.

-   **Statistiques totales (`getTotalCommissions`)** : Le pipeline est plus simple :
    1.  **`$group`** : Il utilise une clé de groupement nulle (`_id: null`), ce qui indique à MongoDB de regrouper **tous** les documents de la collection en un seul résultat. L'accumulateur `$sum: "$amount"` calcule la somme totale des commissions sur toute la période.

Cette architecture permet de décharger la base de données relationnelle principale des requêtes d'analyse potentiellement lourdes, tout en tirant parti de la performance et de la flexibilité de MongoDB pour les opérations d'agrégation, qui est un cas d'usage où les bases NoSQL excellent.

### 3) Lister et Suspendre les Comptes Utilisateurs/Employés

Ces fonctionnalités permettent à l'administrateur de visualiser et de contrôler l'accès des utilisateurs à la plateforme.

**1. Affichage des listes d'utilisateurs**

-   **Flux de données** : Le tableau de bord de l'administrateur, via son script JavaScript, lance des appels `GET` aux points d'API `/api/admin/users` et `/api/admin/employees` pour récupérer les listes d'utilisateurs et d'employés.
-   **Contrôleur (`AdminController.php`)** : Les méthodes `getAllUsersApi()` et `getAllEmployeesApi()` reçoivent ces requêtes et les transmettent directement à l'`AdminService`.
-   **Logique de Service (`AdminService.php`)** : Le service est responsable de la segmentation des utilisateurs.
    -   `getAllUsers()` : Cette méthode sélectionne les utilisateurs mais doit exclure les employés et les autres administrateurs. Pour ce faire, elle exécute une requête SQL qui `JOIN` la table `users` avec `user_roles` et `roles`, et applique une clause `WHERE roles.name NOT IN ('ROLE_ADMIN', 'ROLE_EMPLOYEE')`.
    -   `getAllEmployees()` : Inversement, cette méthode utilise une logique similaire mais avec une clause `WHERE roles.name = 'ROLE_EMPLOYEE'`.
-   **Affichage (Front-End)** : Le script JS reçoit les listes en JSON et les utilise pour construire dynamiquement des tableaux HTML, affichant chaque utilisateur avec ses informations et les boutons d'action pertinents (comme "Suspendre").

**2. Suspension d'un Compte**

-   **Déclenchement** : L'administrateur clique sur le bouton "Suspendre" à côté d'un utilisateur. Le JS affiche une confirmation, puis envoie une requête `POST` à l'API `/api/admin/users/status`, en incluant l'ID de l'utilisateur et le nouveau statut (`suspended`).
-   **Contrôleur (`AdminController.php`)** : La méthode `updateUserStatusApi()` relaie la demande à l'`AdminService`.
-   **Logique Métier (`AdminService.php`)** : La méthode `updateUserAccountStatus()` exécute l'action.
    1.  **Validation de sécurité** : Une vérification importante pourrait être ajoutée ici pour empêcher un administrateur de suspendre un autre administrateur, ou lui-même, afin d'éviter des situations de verrouillage de la plateforme.
    2.  **Mise à jour de la base de données** : Le service exécute une simple requête `UPDATE` sur la table `users` pour modifier la colonne `account_status` de l'utilisateur ciblé.
-   **Conséquence de la suspension** : L'impact de ce changement de statut se manifeste lors de la prochaine tentative de connexion de l'utilisateur suspendu. Dans l'`AuthService`, après avoir vérifié le mot de passe, une vérification supplémentaire sur le statut du compte (`if ($user->getAccountStatus() !== 'active')`) empêcherait la création de la session et renverrait un message d'erreur.
-   **Retour d'information (Front-End)** : Après une réponse de succès de l'API, le script JS met à jour l'interface sans recharger la page. Il peut, par exemple, changer le texte du bouton en "Réactiver" et modifier l'apparence de la ligne pour indiquer visuellement que le compte est suspendu.

---

## F. Modélisation des Données (MariaDB + MongoDB)

### 1) MariaDB (relationnel) : La source de vérité transactionnelle

-   **Rôle et Justification** : MariaDB (un fork de MySQL) est le cœur opérationnel de l'application. Il a été choisi pour sa robustesse, sa fiabilité et, surtout, pour sa conformité ACID (Atomicité, Cohérence, Isolation, Durabilité). C'est la garantie que les opérations critiques, notamment financières comme les transferts de crédits, sont exécutées de manière fiable et intégrale. Le schéma strict et les contraintes de clés étrangères assurent une cohérence et une intégrité des données impossibles à garantir avec la même rigueur dans un modèle NoSQL.

-   **Structure des Données et Relations** : Le modèle relationnel est centré sur les entités principales :
    -   `users` : Contient les informations des utilisateurs, y compris leur solde de crédits.
    -   `rides`, `vehicles`, `brands` : Gèrent les informations sur les trajets et les véhicules.
    -   `bookings` : Table de liaison cruciale qui associe un `user` à un `ride`.
    -   `reviews`, `reports` : Stockent les contenus générés par les utilisateurs.
    -   `roles`, `user_roles` : Une implémentation standard du contrôle d'accès basé sur les rôles (RBAC) via une table de liaison "many-to-many".

-   **Intégrité et Transactions** : L'exemple le plus parlant est le `BookingService`. Lors d'une réservation, plusieurs opérations (`UPDATE` sur les crédits de l'utilisateur, `INSERT` dans les réservations) doivent réussir ou échouer comme un seul bloc. L'utilisation des transactions (`BEGIN TRANSACTION`, `COMMIT`, `ROLLBACK`) par la classe `Database` (wrapper PDO) est la seule manière de garantir cet état. De même, les verrous de ligne (`SELECT ... FOR UPDATE`) sont une fonctionnalité propre aux SGBD relationnels, indispensable pour gérer la concurrence.

### 2) MongoDB (NoSQL) : Le journal d'événements et d'analyse

-   **Rôle et Justification** : MongoDB est utilisé pour des besoins où la flexibilité et la performance en lecture/écriture sur de grands volumes sont plus importantes que la cohérence transactionnelle stricte. Son rôle n'est pas de stocker l'état actuel de l'application, mais de **journaliser les événements** (event logging) et de faciliter l'analyse.
    -   **Flexibilité** : Les collections comme `logs` ou `cancellations` peuvent stocker des documents avec des structures variées sans nécessiter de migrations de schéma complexes. Si demain on veut ajouter un nouveau type de log avec plus d'informations, l'application peut commencer à l'écrire immédiatement.
    -   **Performance** : L'écriture d'un log est une opération très rapide dans MongoDB. Surtout, son **Framework d'Agrégation** est nativement conçu pour des calculs analytiques complexes, comme vu dans la fonctionnalité des statistiques de l'administrateur (E.2). Effectuer des `GROUP BY` sur de grandes tables de logs dans MariaDB pourrait dégrader les performances de la base de données principale.

-   **Mise en œuvre (`MongoLogService.php`)** : Ce service agit comme une façade (façade pattern) pour toutes les interactions avec MongoDB.
    -   **Écriture** : Des méthodes comme `logCommission()` ou `logCancellation()` créent des documents BSON (le format de MongoDB) et les insèrent dans la collection appropriée. Ces documents sont des "photographies" d'un événement à un instant T.
    -   **Lecture et Agrégation** : Des méthodes comme `getCompletedRidesByDay()` utilisent le pipeline d'agrégation de MongoDB pour transformer des milliers de documents de log en un résumé structuré (ex: une liste de jours avec le nombre de trajets correspondants), prêt à être consommé par une bibliothèque de graphiques.

-   **Synergie (Polyglot Persistence)** : Cette architecture à double base de données, parfois appelée "persistance polyglotte", est une approche moderne et puissante. Elle utilise le bon outil pour le bon travail : MariaDB pour la fiabilité transactionnelle et l'intégrité des données, et MongoDB pour la flexibilité, la journalisation et la performance analytique.

---

## G. Sécurité, Rôles & CSRF

La sécurité de l'application repose sur plusieurs piliers, implémentés à différentes couches de l'architecture.

**1. Routage et Contrôle d'Accès (RBAC)**

-   **Approche Déclarative** : La sécurité d'accès est définie de manière déclarative directement dans le fichier `app/routes.php`. Chaque route possède deux clés de sécurité : `'auth' => true/false` et `'roles' => [...]`. Cette approche centralisée permet d'avoir une vision d'ensemble très claire des permissions de l'application.
-   **Mise en Œuvre (`Router.php`)** : Le routeur agit comme un "garde du corps" avant même d'appeler un contrôleur. Pour chaque requête, il vérifie les exigences de la route correspondante :
    1.  Si `'auth'` est `true`, il vérifie la présence de `$_SESSION['user_id']`. Si l'utilisateur n'est pas connecté, il est redirigé vers la page de connexion.
    2.  Si le tableau `'roles'` n'est pas vide (ex: `['ROLE_ADMIN']`), il vérifie que le tableau `$_SESSION['user_roles']` contient au moins un des rôles requis. En cas d'échec, il renvoie une erreur 403 (Accès Interdit).
-   **Construction de la Session de Rôles** : Pour que le contrôle d'accès soit performant, la liste complète des rôles d'un utilisateur est chargée une seule fois et stockée en session lors de la connexion (`AuthController::login()`). Cette liste est une fusion de deux types de rôles :
    -   **Rôles Système** (ex: `ROLE_USER`, `ROLE_EMPLOYEE`) : Persistants et gérés via la table de liaison `user_roles`.
    -   **Rôle Fonctionnel** (ex: `ROLE_DRIVER`) : Dérivé de la colonne `functional_role` de la table `users`, il est ajouté dynamiquement à la liste en session.
    Cette mise en cache en session évite d'interroger la base de données sur les permissions à chaque requête.

**2. Protection contre les attaques CSRF (Cross-Site Request Forgery)**

-   **Mécanisme** : L'application utilise le pattern "Double Submit Cookie" (variante avec la session).
    1.  **Génération** : Un jeton unique et aléatoire est généré par `CsrfHelper` et stocké dans `$_SESSION` au début de la session de l'utilisateur.
    2.  **Exposition** : Ce même jeton est intégré dans une balise `<meta name="csrf-token" content="...">` dans le `layout.php` de chaque page.
    3.  **Soumission** : Le JavaScript côté client (via `apiClient`) lit systématiquement ce jeton depuis la balise meta et l'ajoute comme en-tête (`X-CSRF-Token`) à toutes les requêtes `POST` (ou `PUT`/`DELETE`) qui modifient l'état de l'application.
    4.  **Validation** : Dans le `Router.php`, avant de traiter toute requête `POST`, le routeur compare le jeton reçu dans l'en-tête avec celui stocké en session. S'ils ne correspondent pas, la requête est rejetée. Cela garantit que la requête a bien été émise par un script provenant de la page de l'application elle-même, et non d'un site externe malveillant.

**3. Prévention des Conditions de Concurrence (Race Conditions)**

-   **Verrouillage Pessimiste** : Pour toutes les opérations sensibles où plusieurs utilisateurs pourraient agir en même temps (réserver la dernière place, annuler un trajet, valider un paiement), l'application utilise une stratégie de verrouillage pessimiste.
-   **Mise en Œuvre (`SELECT ... FOR UPDATE`)** : Dans les services critiques (`BookingService`, `ConfirmationService`), les requêtes qui lisent des données avant de les modifier sont exécutées avec la clause `FOR UPDATE`. Comme détaillé en B.1, cela place un verrou exclusif sur les lignes lues au sein d'une transaction, forçant les autres transactions concurrentes à attendre. C'est la méthode la plus robuste pour garantir l'intégrité des données de type "inventaire" (places) ou "financières" (crédits).

**4. Sécurité des Communications et des Données Sensibles**

-   **Emails Transactionnels (`EmailService`)** : Toute la logique d'envoi d'emails est centralisée. Les identifiants du serveur SMTP ne sont pas codés en dur mais chargés depuis des variables d'environnement (`.env`), ce qui est une bonne pratique de sécurité. Le service est également conçu pour désactiver les envois en environnement de test.
-   **Mots de passe (`AuthService`)** : Les mots de passe des utilisateurs sont toujours hachés avec l'algorithme `PASSWORD_DEFAULT` (Bcrypt) avant d'être stockés, ce qui les rend irréversibles.
-   **Validation et Échappement** : Une validation systématique des données est effectuée côté serveur (dans les services) et les données affichées dans les vues sont systématiquement échappées avec `htmlspecialchars()` pour prévenir les attaques XSS (Cross-Site Scripting).

---

## H. Points d’architecture, justifications et alternatives courantes

### 1) Pourquoi séparer Controller / Service / Helper / Repository?

-   **Justification (Architecture en Couches)** : L'application suit une architecture en couches stricte pour maximiser la séparation des préoccupations (SoC) et la testabilité.
    -   **Controller** : Couche la plus externe, responsable uniquement de la gestion des requêtes et réponses HTTP. Il ne contient aucune logique métier. Son rôle est de traduire une requête HTTP en un appel à un service, puis de traduire la réponse du service en une réponse HTTP (HTML ou JSON).
    -   **Service** : Cœur de l'application, il contient la logique et les règles métier. Il orchestre les opérations, peut appeler plusieurs repositories ou d'autres services, et gère les transactions. Par exemple, `BookingService` utilise à la fois `RideRepository` et `UserRepository` pour effectuer une réservation.
    -   **Repository** : Couche d'accès aux données. Son unique responsabilité est de communiquer avec la base de données. Il contient les requêtes SQL et la logique d'hydratation des objets. Cette abstraction permet de changer de SGBD (ex: passer de MariaDB à PostgreSQL) en ne modifiant que cette couche.
    -   **Helper** : Fonctions stateless pures, dédiées à la logique de présentation. `RideHelper`, par exemple, transforme un objet PHP complexe en un simple tableau pour une API JSON, sans altérer l'objet original.
-   **Alternative (Modèle Anémique vs Riche)** : L'alternative principale est le "Fat Controller" où toute la logique est dans le contrôleur, ce qui mène à un code non testable et difficile à maintenir. Une autre alternative serait un "Rich Domain Model" où la logique métier est dans les modèles eux-mêmes (ex: `$ride->addBooking($user)`). L'approche actuelle, un "Modèle Anémique" (les modèles sont de simples conteneurs de données) avec la logique dans les services, est très courante, pragmatique et plus simple à mettre en œuvre et à tester.

### 2) Pourquoi utiliser 2 bases (MariaDB + MongoDB)?

-   **Justification (Persistance Polyglotte)** : C'est une stratégie qui consiste à utiliser le bon outil pour le bon travail.
    -   **MariaDB** est utilisé pour les données opérationnelles qui exigent une forte cohérence et des transactions ACID (ex: les soldes de crédits, les réservations, les relations entre entités). Les contraintes de clés étrangères garantissent l'intégrité des données.
    -   **MongoDB** est utilisé pour la journalisation d'événements (`logs`, `commissions`, `cancellations`) et l'analytique. Sa flexibilité de schéma est parfaite pour des logs qui peuvent évoluer. Surtout, son **Framework d'Agrégation** est nativement conçu pour des calculs analytiques complexes, comme vu dans la fonctionnalité des statistiques de l'administrateur (E.2). Effectuer des `GROUP BY` sur de grandes tables de logs dans MariaDB pourrait dégrader les performances de la base de données principale.

### 3) Pourquoi confirmations post-trajet avec token email?

-   **Justification (Workflow Asynchrone Sécurisé)** : Ce système crée un workflow asynchrone qui requiert une action positive du passager pour débloquer les fonds.
    -   **UX et Sécurité** : Le jeton (token) est une clé d'accès à usage unique qui authentifie une action spécifique (`confirm` ou `report`) sans que le passager ait besoin de se reconnecter, ce qui fluidifie l'expérience.
    -   **Logique Métier** : Cela établit une preuve de consentement du passager avant le transfert des crédits, ce qui est une position métier et légale plus forte qu'un transfert automatique. Cela fournit également le point d'entrée naturel pour le processus de litige (signalement).

### 4) Concurrence et comptes de crédits

-   **Justification (Verrouillage Pessimiste)** : L'application utilise le verrouillage pessimiste via `SELECT ... FOR UPDATE` dans toutes ses transactions critiques.
-   **Alternative (Verrouillage Optimiste)** : L'alternative serait le verrouillage optimiste. Il faudrait ajouter une colonne `version` à chaque table sensible. Le processus serait : 1) Lire la ligne et sa version. 2) Exécuter la logique. 3) Au moment de l'`UPDATE`, ajouter `WHERE version = [version lue]`. 4) Vérifier si une ligne a été affectée. Si non, cela signifie qu'un autre processus a modifié la ligne entre-temps, et il faut recommencer toute l'opération. Pour des ressources à forte contention comme la dernière place d'un trajet, le verrouillage pessimiste est plus simple et plus direct à implémenter.

### 5) Architecture Front

-   **Justification (Amélioration Progressive)** : L'application n'est pas une Single-Page Application (SPA) complète. Elle utilise une approche d'**amélioration progressive**. Les pages sont d'abord rendues par le serveur PHP, ce qui garantit un bon référencement (SEO) et un temps de chargement initial rapide. Ensuite, le JavaScript prend le relais pour "améliorer" la page en ajoutant de l'interactivité et des mises à jour dynamiques sans rechargement (via des appels API et `history.pushState`). L'utilisation de la balise `<template>` est une technique de JavaScript moderne et native pour créer des composants réutilisables sans dépendre d'un framework lourd comme React ou Vue.

### 6) Validation & Sécurité

-   **Justification (Défense en Profondeur)** : La sécurité est appliquée à plusieurs niveaux.
    1.  **Validation Côté Client (HTML5/JS)** : Pour une meilleure expérience utilisateur et un retour immédiat.
    2.  **Validation Côté Serveur (`ValidationService`)** : La source de vérité absolue, car le client peut être contourné.
    3.  **Autorisation au niveau de la Route (`Router`)** : Vérifie si un utilisateur a le droit d'accéder à une fonctionnalité (RBAC).
    4.  **Autorisation au niveau du Métier (`Service`)** : Vérifie si un utilisateur a le droit d'effectuer une action sur une *donnée spécifique* (ex: "êtes-vous bien le propriétaire de ce véhicule que vous essayez de supprimer ?").
    Cette redondance est intentionnelle et constitue une pratique de sécurité robuste.

Fin du dossier.
