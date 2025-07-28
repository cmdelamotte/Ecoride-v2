-- -----------------------------------------------------
-- Table `Roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE -- Ex: 'ROLE_USER', 'ROLE_EMPLOYEE', 'ROLE_ADMIN'
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Brands`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE -- Ex: 'Peugeot', 'Renault', 'Tesla'
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Users`
-- Stores user information
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `birth_date` DATE NOT NULL,
  `profile_picture_path` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `credits` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `account_status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `driver_pref_smoker` BOOLEAN NOT NULL DEFAULT FALSE,
  `driver_pref_animals` BOOLEAN NOT NULL DEFAULT FALSE,
  `driver_pref_custom` TEXT NULL,
  `functional_role` ENUM('passenger', 'driver', 'passenger_driver') NOT NULL DEFAULT 'passenger',
  `driver_rating` DECIMAL(2,1) NOT NULL DEFAULT 0.0 CHECK (driver_rating >= 0.0 AND driver_rating <= 5.0), -- Nouvelle colonne pour la note du conducteur
  `reset_token` VARCHAR(255) NULL DEFAULT NULL,             -- NOUVELLE COLONNE
  `reset_token_expires_at` DATETIME NULL DEFAULT NULL,      -- NOUVELLE COLONNE
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `UserRoles`
-- Junction table for N-N relationship between Users and Roles
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `userroles` (
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  CONSTRAINT `fk_userroles_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_userroles_role`
    FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Vehicles`
-- Stores information about user vehicles
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,                     -- Foreign key to Users table
  `brand_id` INT NOT NULL,                    -- Foreign key to Brands table
  `model_name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(50) NULL,
  `license_plate` VARCHAR(20) NOT NULL UNIQUE, -- Assuming license plates are unique
  `registration_date` DATE NULL,              -- Date of first registration
  `passenger_capacity` TINYINT UNSIGNED NOT NULL, -- Max passengers (e.g., 1 to 8)
  `is_electric` BOOLEAN NOT NULL DEFAULT FALSE,
  `energy_type` VARCHAR(50) NULL,             -- Optional: 'Gasoline', 'Diesel', 'Hybrid', 'Electric'
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_vehicles_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE -- If the user is deleted, their vehicles are also deleted
    ON UPDATE CASCADE,
  CONSTRAINT `fk_vehicles_brand`
    FOREIGN KEY (`brand_id`)
    REFERENCES `brands` (`id`)
    ON DELETE RESTRICT -- Don't allow deleting a brand if vehicles are associated with it
    ON UPDATE CASCADE  -- (Or ON DELETE SET NULL / ON DELETE NO ACTION depending on desired logic)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Rides`
-- Stores information about offered carpooling rides
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `driver_id` INT NOT NULL,                   -- Foreign key to Users table (the driver)
  `vehicle_id` INT NOT NULL,                  -- Foreign key to Vehicles table
  `departure_city` VARCHAR(150) NOT NULL,     -- For searching and simplified display
  `arrival_city` VARCHAR(150) NOT NULL,       -- For searching and simplified display
  `departure_address` TEXT NOT NULL,              -- Specific departure address
  `arrival_address` TEXT NOT NULL,                -- Specific arrival address
  `departure_time` DATETIME NOT NULL,         -- Precise date and time of departure
  `estimated_arrival_time` DATETIME NOT NULL, -- Estimated date and time of arrival
  `price_per_seat` DECIMAL(10, 2) NOT NULL,
  `seats_offered` TINYINT UNSIGNED NOT NULL,  -- Number of seats offered for this specific ride
  `ride_status` VARCHAR(60) NOT NULL DEFAULT 'planned',
  `total_net_credits_earned` DECIMAL(10,2) DEFAULT 0.00,
  `driver_message` TEXT NULL,                 -- Optional message from the driver to passengers
  `is_eco_ride` BOOLEAN NOT NULL DEFAULT FALSE, -- Determined if the vehicle used is electric
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_rides_driver`
    FOREIGN KEY (`driver_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE -- If the driver's account is deleted, their rides are also deleted
    ON UPDATE CASCADE,
  CONSTRAINT `fk_rides_vehicle`
    FOREIGN KEY (`vehicle_id`)
    REFERENCES `vehicles` (`id`)
    ON DELETE RESTRICT -- Prevent deleting a vehicle if it's associated with planned/ongoing rides
                       -- (Or SET NULL if vehicle_id can be NULL and you want to keep the ride info
                       -- but mark it as 'vehicle unavailable', though this complicates logic)
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Bookings`
-- Stores passenger bookings for rides
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,                       -- Foreign key to Users table (the passenger)
  `ride_id` INT NOT NULL,                       -- Foreign key to Rides table
  `seats_booked` TINYINT UNSIGNED NOT NULL DEFAULT 1, -- Number of seats booked, typically 1
  `booking_status` VARCHAR(60) NOT NULL DEFAULT 'confirmed',
  `confirmation_token` VARCHAR(255) UNIQUE NULL,
  `token_expires_at` DATETIME NULL,
  `passenger_confirmed_at` DATETIME NULL,
  `credits_transferred_for_this_booking` BOOLEAN DEFAULT FALSE,
  `booking_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When the booking was made
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_bookings_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE -- If the user (passenger) is deleted, their bookings are also deleted
    ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_ride`
    FOREIGN KEY (`ride_id`)
    REFERENCES `rides` (`id`)
    ON DELETE CASCADE -- If the ride is deleted, the bookings for that ride are also deleted
    ON UPDATE CASCADE,
  CONSTRAINT `uq_user_ride_booking` UNIQUE (`user_id`, `ride_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Reviews`
-- Stores reviews left by passengers for drivers/rides
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ride_id` INT NOT NULL,                          -- The ride this review is for
  `author_id` INT NOT NULL,                        -- The user (passenger) who wrote the review
  `driver_id` INT NOT NULL,                        -- The user (driver) who is being reviewed
  `rating` TINYINT UNSIGNED NOT NULL,              -- Rating from 1 to 5
  `comment` TEXT NULL,                             -- Optional comment
  `review_status` ENUM('pending_approval', 'approved', 'rejected') NOT NULL DEFAULT 'pending_approval',
  `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When the review was submitted
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_reviews_ride`
    FOREIGN KEY (`ride_id`)
    REFERENCES `rides` (`id`)
    ON DELETE CASCADE, -- If the ride is deleted, associated reviews might also be deleted (or anonymized)
  CONSTRAINT `fk_reviews_author`
    FOREIGN KEY (`author_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE, -- If the author's account is deleted, delete their reviews
  CONSTRAINT `fk_reviews_driver`
    FOREIGN KEY (`driver_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE, -- If the driver's account is deleted, reviews about them are also deleted (debatable, could be anonymized)
  CONSTRAINT `uq_author_ride_review` UNIQUE (`author_id`, `ride_id`) 
  -- One review per passenger for a given ride.

) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `Reports`
-- Stores reports made by passengers about problematic rides/drivers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ride_id` INT NOT NULL,                          -- The ride being reported
  `reporter_id` INT NOT NULL,                      -- The user (passenger) making the report
  `reported_driver_id` INT NOT NULL,               -- The user (driver) being reported
  `reason` TEXT NOT NULL,                          -- Detailed reason for the report
  `report_status` ENUM('new', 'under_investigation', 'resolved_action_taken', 'resolved_no_action', 'closed') NOT NULL DEFAULT 'new',
  `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When the report was submitted
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_reports_ride`
    FOREIGN KEY (`ride_id`)
    REFERENCES `rides` (`id`)
    ON DELETE CASCADE, -- If the ride is deleted, associated reports might also be deleted
  CONSTRAINT `fk_reports_reporter`
    FOREIGN KEY (`reporter_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE, -- If the reporter's account is deleted, delete their reports
  CONSTRAINT `fk_reports_reported_driver`
    FOREIGN KEY (`reported_driver_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE, -- If the reported driver's account is deleted, reports against them are deleted
  CONSTRAINT `uq_reporter_ride_report` UNIQUE (`reporter_id`, `ride_id`)
) ENGINE = InnoDB;