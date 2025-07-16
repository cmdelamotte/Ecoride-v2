-- Données pour `Roles` table
-- On spécifie les ID pour pouvoir facilement y faire référence pour les tests
-- AUTO_INCREMENT commencera après ces valeurs en cas d'ajout d'autres rôles.
INSERT INTO `Roles` (`id`, `name`) VALUES
(1, 'ROLE_USER'),    -- Pour les clients (passagers/chauffeurs)
(2, 'ROLE_EMPLOYEE'), -- Pour les employés de la plateforme
(3, 'ROLE_ADMIN');    -- Pour les administrateurs

-- Données pour `Brands` table
INSERT INTO `Brands` (`id`, `name`) VALUES
(1, 'Peugeot'),
(2, 'Renault'),
(3, 'Citroën'),
(4, 'Volkswagen'),
(5, 'Tesla'),
(6, 'Toyota'),
(7, 'Fiat'),
(8, 'Dacia'),
(9, 'BMW'),
(10, 'Kia'),
(11, 'Hyundai'),
(12, 'Ford'),
(13, 'Opel'),
(14, 'Mercedes-Benz'),
(15, 'Audi');

-- Données pour `Users` table
-- User 1: Admin
INSERT INTO `Users` 
(`first_name`, `last_name`, `username`, `email`, `password_hash`, `phone_number`, `birth_date`, `profile_picture_path`, `address`, `credits`, `account_status`, `driver_pref_smoker`, `driver_pref_animals`, `driver_pref_custom`, `functional_role`) 
VALUES
('Admin', 'EcoRide', 'admin_ecoride', 'admin@ecoride.com', '$2y$10$6BFo8Wiveqnj9iMG/GzNP.MxSw2PHy9Y/17cjxqYJm4ohhg2Q1TnO', '0102030401', '1980-01-01', NULL, '1 Admin Street, Rennes', 100.00, 'active', FALSE, FALSE, NULL, 'passenger');

-- User 2: Employee
INSERT INTO `Users` 
(`first_name`, `last_name`, `username`, `email`, `password_hash`, `phone_number`, `birth_date`, `profile_picture_path`, `address`, `credits`, `account_status`, `driver_pref_smoker`, `driver_pref_animals`, `driver_pref_custom`, `functional_role`) 
VALUES
('Employee', 'EcoRide', 'employee_ecoride', 'employee@ecoride.com', '$2y$10$6BFo8Wiveqnj9iMG/GzNP.MxSw2PHy9Y/17cjxqYJm4ohhg2Q1TnO', '0102030402', '1990-02-15', NULL, '2 Staff Avenue, Rennes', 50.00, 'active', FALSE, FALSE, NULL, 'passenger');

-- User 3: Client - Chauffeur-Passager (mot de passe 'password123')
INSERT INTO `Users` 
(`first_name`, `last_name`, `username`, `email`, `password_hash`, `phone_number`, `birth_date`, `profile_picture_path`, `address`, `credits`, `account_status`, `driver_pref_smoker`, `driver_pref_animals`, `driver_pref_custom`, `functional_role`) 
VALUES
('Alice', 'Martin', 'alice_driver_passenger', 'alice.martin@email.com', '$2y$10$6BFo8Wiveqnj9iMG/GzNP.MxSw2PHy9Y/17cjxqYJm4ohhg2Q1TnO', '0611223344', '1992-07-20', NULL , '10 Rue du Covoiturage, Lyon', 75.50, 'active', FALSE, TRUE, 'Musique pop bienvenue, discussions moderees.', 'passenger_driver');

-- User 4: Client - Passager uniquement (mot de passe 'password123')
INSERT INTO `Users` 
(`first_name`, `last_name`, `username`, `email`, `password_hash`, `phone_number`, `birth_date`, `profile_picture_path`, `address`, `credits`, `account_status`, `driver_pref_smoker`, `driver_pref_animals`, `driver_pref_custom`, `functional_role`) 
VALUES
('Bob', 'Dupont', 'bob_passenger', 'bob.dupont@email.com', '$2y$10$6BFo8Wiveqnj9iMG/GzNP.MxSw2PHy9Y/17cjxqYJm4ohhg2Q1TnO', '0655667788', '1988-11-05', NULL, '25 Boulevard des Voyageurs, Paris', 20.00, 'active', FALSE, FALSE, NULL, 'passenger');

-- User 5: Client - Chauffeur uniquement (mot de passe 'password123')
INSERT INTO `Users` 
(`first_name`, `last_name`, `username`, `email`, `password_hash`, `phone_number`, `birth_date`, `profile_picture_path`, `address`, `credits`, `account_status`, `driver_pref_smoker`, `driver_pref_animals`, `driver_pref_custom`, `functional_role`) 
VALUES
('Carole', 'Petit', 'carole_driver', 'carole.petit@email.com', '$2y$10$6BFo8Wiveqnj9iMG/GzNP.MxSw2PHy9Y/17cjxqYJm4ohhg2Q1TnO', '0712345678', '1995-04-30', NULL, '5 Avenue de la Route, Marseille', 150.00, 'active', TRUE, FALSE, 'Animaux non admis. Prefere le silence ou musique classique.', 'driver');

-- Données pour `UserRoles` table
-- Assignation des rôles aux users créés au dessus.
-- User 1 (admin_ecoride) gets ONLY ROLE_ADMIN
INSERT INTO `UserRoles` (`user_id`, `role_id`) VALUES
(1, 3); -- admin_ecoride is ROLE_ADMIN

-- User 2 (employee_ecoride) gets ONLY ROLE_EMPLOYEE
INSERT INTO `UserRoles` (`user_id`, `role_id`) VALUES
(2, 2); -- employee_ecoride is ROLE_EMPLOYEE

-- User 3 (alice_driver_passenger) is a client -> ROLE_USER
INSERT INTO `UserRoles` (`user_id`, `role_id`) VALUES
(3, 1);

-- User 4 (bob_passenger) is a client -> ROLE_USER
INSERT INTO `UserRoles` (`user_id`, `role_id`) VALUES
(4, 1);

-- User 5 (carole_driver) is a client -> ROLE_USER
INSERT INTO `UserRoles` (`user_id`, `role_id`) VALUES
(5, 1);

-- Données pour `Vehicles` table
-- Véhicule pour Alice (user_id 3), Peugeot 208, not electric
INSERT INTO `Vehicles` 
(`user_id`, `brand_id`, `model_name`, `color`, `license_plate`, `registration_date`, `passenger_capacity`, `is_electric`, `energy_type`) 
VALUES
(3, 1, '208', 'Blue', 'AA-123-BB', '2019-03-15', 4, FALSE, 'Gasoline');

-- Véhicule pour Carole (user_id 5), Tesla Model 3, electric
INSERT INTO `Vehicles` 
(`user_id`, `brand_id`, `model_name`, `color`, `license_plate`, `registration_date`, `passenger_capacity`, `is_electric`, `energy_type`) 
VALUES
(5, 5, 'Model 3', 'Red', 'CC-789-DD', '2021-07-01', 4, TRUE, 'Electric');

-- Second véhicule pour Alice, Renault Clio
INSERT INTO `Vehicles` 
(`user_id`, `brand_id`, `model_name`, `color`, `license_plate`, `registration_date`, `passenger_capacity`, `is_electric`, `energy_type`) 
VALUES
(3, 2, 'Clio V', 'White', 'EE-456-FF', '2020-11-01', 4, FALSE, 'Gasoline');

-- Données pour `Rides` table

-- Ride 1: Proposé par Alice (user_id 3) avec sa Peugeot 208
INSERT INTO `Rides` 
(`driver_id`, `vehicle_id`, `departure_city`, `arrival_city`, `departure_address`, `arrival_address`, `departure_time`, `estimated_arrival_time`, `price_per_seat`, `seats_offered`, `ride_status`, `driver_message`, `is_eco_ride`)
VALUES
(
  3, -- driver_id (Alice)
  1, -- vehicle_id (Alice's Peugeot 208)
  'Lyon', 
  'Paris',
  'Gare de Lyon-Perrache, Lyon', 
  'Tour Eiffel, Paris',
  -- Utiliser une date future pour que le trajet soit "à venir"
  -- Supposons que la date actuelle est autour du 15 Mai 2025
  -- Pour un départ le 20 Mai 2025 à 8h00
  TIMESTAMPADD(DAY, 5, CURDATE() + INTERVAL 8 HOUR), -- Départ dans 5 jours à 8h00
  TIMESTAMPADD(DAY, 5, CURDATE() + INTERVAL 13 HOUR), -- Arrivée prévue 5h plus tard (13h00)
  25.00, 
  3,     -- Sièges proposés
  'planned', 
  'Petit détour possible si sur la route. Bagage cabine uniquement.',
  FALSE  -- Not an eco ride (Peugeot 208 non électrique)
);

-- Ride 2: Proposé par Carole (user_id 5) avec sa Tesla Model 3
INSERT INTO `Rides` 
(`driver_id`, `vehicle_id`, `departure_city`, `arrival_city`, `departure_address`, `arrival_address`, `departure_time`, `estimated_arrival_time`, `price_per_seat`, `seats_offered`, `ride_status`, `driver_message`, `is_eco_ride`)
VALUES
(
  5, -- driver_id (Carole)
  2, -- vehicle_id (Carole's Tesla Model 3)
  'Marseille', 
  'Nice',
  'Vieux Port, Marseille', 
  'Promenade des Anglais, Nice',
  TIMESTAMPADD(DAY, 7, CURDATE() + INTERVAL 10 HOUR), -- Départ dans 7 jours à 10h00
  TIMESTAMPADD(DAY, 7, CURDATE() + INTERVAL 12 HOUR_MINUTE), -- Arrivée prévue 2h30 plus tard (12h30) -> HOUR_MINUTE est pour la précision
  18.50, 
  2,     -- Sièges proposés
  'planned', 
  'Trajet direct sans arrêt.',
  TRUE   -- Eco ride (Tesla Model 3 électrique)
);

-- Ride 3: Proposé par Alice (user_id 3) with avec sa Renault Clio
INSERT INTO `Rides` 
(`driver_id`, `vehicle_id`, `departure_city`, `arrival_city`, `departure_address`, `arrival_address`, `departure_time`, `estimated_arrival_time`, `price_per_seat`, `seats_offered`, `ride_status`, `driver_message`, `is_eco_ride`)
VALUES
(
  3, -- driver_id (Alice)
  3, -- vehicle_id (Alice's Renault Clio)
  'Rennes', 
  'Saint-Malo',
  'Place de la République, Rennes', 
  'Intra-Muros, Saint-Malo',
  TIMESTAMPADD(DAY, 2, CURDATE() + INTERVAL 14 HOUR), -- Départ dans 2 jours à 14h00
  TIMESTAMPADD(DAY, 2, CURDATE() + INTERVAL 15 HOUR), -- Arrivée prévue 1h plus tard (15h00)
  8.00, 
  3,     -- Sièges proposés
  'planned', 
  NULL,  -- Pas de commentaires spécifiques
  FALSE  -- Not an eco ride
);

-- Ride 4: Trajet terminé proposé par Carole (user_id 5) pour test d'historique/avis
INSERT INTO `Rides`
(`driver_id`, `vehicle_id`, `departure_city`, `arrival_city`, `departure_address`, `arrival_address`, `departure_time`, `estimated_arrival_time`, `price_per_seat`, `seats_offered`, `ride_status`, `driver_message`, `is_eco_ride`)
VALUES
(
  5, -- driver_id (Carole)
  2, -- vehicle_id (Carole's Tesla)
  'Bordeaux',
  'Toulouse',
  'Gare Saint-Jean, Bordeaux',
  'Place du Capitole, Toulouse',
  TIMESTAMPADD(DAY, -10, CURDATE() + INTERVAL 9 HOUR), -- Départ il y a 10 jours à 9h00
  TIMESTAMPADD(DAY, -10, CURDATE() + INTERVAL 11 HOUR_MINUTE), -- Arrivée il y a 10 jours à 11h30
  22.00,
  3,
  'completed', -- Ride status
  'Trajet rapide et confortable.',
  TRUE
);

-- Données pour `Bookings` table
-- Booking 1: Bob (user_id 4) books Alice's ride (ride_id 1, Lyon -> Paris)
INSERT INTO `Bookings` 
(`user_id`, `ride_id`, `seats_booked`, `booking_status`, `booking_date`)
VALUES
(
  4, -- user_id (Bob)
  1, -- ride_id (Alice's Lyon->Paris ride)
  1, -- seats_booked
  'confirmed',
  TIMESTAMPADD(DAY, -2, NOW()) -- Booking made 2 days ago for a future ride
);

-- Booking 2: Alice (user_id 3) books Carole's ride (ride_id 2, Marseille -> Nice)
-- (Alice est passenger_driver, donc elle peut aussi réserver des trajets)
INSERT INTO `Bookings` 
(`user_id`, `ride_id`, `seats_booked`, `booking_status`, `booking_date`)
VALUES
(
  3, -- user_id (Alice)
  2, -- ride_id (Carole's Marseille->Nice ride)
  1, -- seats_booked
  'confirmed',
  TIMESTAMPADD(DAY, -1, NOW()) -- Booking made 1 day ago for a future ride
);

-- Booking 3: Bob (user_id 4) booked Carole's past ride (ride_id 4, Bordeaux -> Toulouse)
-- Pour tester le dépôt d'avis.
INSERT INTO `Bookings`
(`user_id`, `ride_id`, `seats_booked`, `booking_status`, `booking_date`)
VALUES
(
  4, -- user_id (Bob)
  4, -- ride_id (Carole's past Bordeaux->Toulouse ride)
  1, -- seats_booked
  'confirmed', -- Still 'confirmed' even if the ride is 'completed', status here is for the booking itself
  TIMESTAMPADD(DAY, -12, CURDATE()) -- Booking made 12 days ago (before the ride 10 days ago)
);

-- -----------------------------------------------------
-- Données pour la table `Rides` (ajout d'un trajet passé pour Alice)
-- Ce trajet est nécessaire pour les exemples d'avis et de signalements ci-dessous.
-- Assurez-vous que l'ID de ce trajet (qui sera probablement 5) est utilisé correctement ensuite.
-- -----------------------------------------------------
INSERT INTO `Rides` 
(`driver_id`, `vehicle_id`, `departure_city`, `arrival_city`, `departure_address`, `arrival_address`, `departure_time`, `estimated_arrival_time`, `price_per_seat`, `seats_offered`, `ride_status`, `driver_message`, `is_eco_ride`)
VALUES
(
  3, -- driver_id (Alice, ID 3)
  1, -- vehicle_id (Sa Peugeot 208, ID 1)
  'Lyon', 
  'Grenoble',
  'Place Bellecour, Lyon', 
  'Gare de Grenoble',
  TIMESTAMPADD(DAY, -15, CURDATE() + INTERVAL 10 HOUR), -- Trajet passé (il y a 15 jours à 10h)
  TIMESTAMPADD(DAY, -15, CURDATE() + INTERVAL 12 HOUR), -- Arrivée il y a 15 jours à 12h
  12.00, 
  2,     -- Sièges offerts
  'completed', 
  'Petit trajet sympa vers Grenoble.',
  FALSE
);

-- -----------------------------------------------------
-- Données pour la table `Bookings` (ajout d'une réservation pour le trajet ci-dessus)
-- Bob réserve le trajet Lyon-Grenoble d'Alice.
-- -----------------------------------------------------
INSERT INTO `Bookings` 
(`user_id`, `ride_id`, `seats_booked`, `booking_status`, `booking_date`)
VALUES
(
  4, -- user_id (Bob, ID 4)
  5, -- ride_id (Trajet Lyon-Grenoble d'Alice, supposons ID 5)
  1, 
  'confirmed',
  TIMESTAMPADD(DAY, -16, CURDATE()) -- Réservation faite avant le trajet
);

-- -----------------------------------------------------
-- Données pour la table `Reviews` (Avis)
-- -----------------------------------------------------

-- Avis 1: Bob (ID 4) évalue Carole (ID 5) pour le trajet Bordeaux-Toulouse (ID 4)
-- Statut: en attente de validation
INSERT INTO `Reviews` 
(`ride_id`, `author_id`, `driver_id`, `rating`, `comment`, `review_status`, `submission_date`)
VALUES
(
  4, -- ride_id (Trajet passé Bordeaux-Toulouse de Carole)
  4, -- author_id (Bob)
  5, -- driver_id (Carole)
  4, 
  'Tres bon trajet dans l''ensemble. La conductrice Carole etait sympathique et la conduite agreable. Un peu de retard au depart mais rien de mechant.',
  'pending_approval',
  TIMESTAMPADD(DAY, -9, NOW()) -- Soumis un jour après le trajet (qui était il y a 10 jours)
);

-- Avis 2: Alice (ID 3) évalue Carole (ID 5) pour le trajet Bordeaux-Toulouse (ID 4)
-- Statut: approuvé
-- (Nécessite une réservation d'Alice pour ce trajet. Si non faite, ajoutez :
-- INSERT INTO `Bookings` (`user_id`, `ride_id`, `booking_date`) VALUES (3, 4, TIMESTAMPADD(DAY, -11, CURDATE()));
-- )
INSERT INTO `Reviews` 
(`ride_id`, `author_id`, `driver_id`, `rating`, `comment`, `review_status`, `submission_date`)
VALUES
(
  4, -- ride_id (Trajet passé Bordeaux-Toulouse de Carole)
  3, -- author_id (Alice)
  5, -- driver_id (Carole)
  5, 
  'Excellent covoiturage avec Carole ! Ponctuelle, voiture impeccable et tres bonne ambiance. Je recommande vivement !',
  'approved',
  TIMESTAMPADD(DAY, -9, NOW() + INTERVAL 1 HOUR) -- Soumis un peu après Bob
);

-- Avis 3: Bob (ID 4) évalue Alice (ID 3) pour le trajet Lyon-Grenoble (ID 5)
-- Statut: rejeté
INSERT INTO `Reviews` 
(`ride_id`, `author_id`, `driver_id`, `rating`, `comment`, `review_status`, `submission_date`)
VALUES
(
  5, -- ride_id (Nouveau trajet Lyon-Grenoble d'Alice)
  4, -- author_id (Bob)
  3, -- driver_id (Alice)
  2, 
  'Commentaire juge inapproprie par la moderation.', -- Raison du rejet (ou commentaire initial)
  'rejected',
  TIMESTAMPADD(DAY, -14, NOW()) -- Soumis un jour après ce trajet (qui était il y a 15 jours)
);

-- -----------------------------------------------------
-- Données pour la table `Reports` (Signalements)
-- -----------------------------------------------------

-- Signalement 1: Bob (ID 4) signale Alice (ID 3) pour le trajet Lyon-Grenoble (ID 5)
INSERT INTO `Reports`
(`ride_id`, `reporter_id`, `reported_driver_id`, `reason`, `report_status`, `submission_date`)
VALUES
(
  5, -- ride_id (Trajet Lyon-Grenoble d'Alice)
  4, -- reporter_id (Bob)
  3, -- reported_driver_id (Alice)
  'La conductrice Alice a fume dans la voiture alors que son profil indiquait non-fumeur. De plus, elle a fait un grand detour non annonce.', 
  'new', 
  TIMESTAMPADD(DAY, -14, NOW() + INTERVAL 2 HOUR) -- Soumis peu après son avis
);

-- -----------------------------------------------------
-- Création de User pour tester la suppression
-- -----------------------------------------------------

INSERT INTO `Users` 
(`first_name`, `last_name`, `username`, `email`, `password_hash`, `phone_number`, `birth_date`, `profile_picture_path`, `address`, `credits`, `account_status`, `driver_pref_smoker`, `driver_pref_animals`, `driver_pref_custom`, `functional_role`) 
VALUES
('ToDelete', 'UserOne', 'todelete_user1', 'todelete1@example.com', '$2y$10$6BFo8Wiveqnj9iMG/GzNP.MxSw2PHy9Y/17cjxqYJm4ohhg2Q1TnO', '0600000015', '2000-01-01', NULL, NULL, 0.00, 'active', FALSE, FALSE, NULL, 'passenger');