-- Schéma SQLite pour les tests (adaptation du schéma physique MySQL)

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS brands (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  first_name TEXT NOT NULL,
  last_name TEXT NOT NULL,
  username TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  phone_number TEXT NOT NULL,
  birth_date TEXT NOT NULL,
  profile_picture_path TEXT NULL,
  address TEXT NULL,
  credits REAL NOT NULL DEFAULT 0.00,
  account_status TEXT NOT NULL DEFAULT 'active',
  driver_pref_smoker INTEGER NOT NULL DEFAULT 0,
  driver_pref_animals INTEGER NOT NULL DEFAULT 0,
  driver_pref_custom TEXT NULL,
  functional_role TEXT NOT NULL DEFAULT 'passenger',
  driver_rating REAL NOT NULL DEFAULT 0.0,
  reset_token TEXT NULL,
  reset_token_expires_at TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS userroles (
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

CREATE TABLE IF NOT EXISTS vehicles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  brand_id INTEGER NOT NULL,
  model_name TEXT NOT NULL,
  color TEXT NULL,
  license_plate TEXT NOT NULL UNIQUE,
  registration_date TEXT NULL,
  passenger_capacity INTEGER NOT NULL,
  is_electric INTEGER NOT NULL DEFAULT 0,
  energy_type TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rides (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  driver_id INTEGER NOT NULL,
  vehicle_id INTEGER NOT NULL,
  departure_city TEXT NOT NULL,
  arrival_city TEXT NOT NULL,
  departure_address TEXT NOT NULL,
  arrival_address TEXT NOT NULL,
  departure_time TEXT NOT NULL,
  estimated_arrival_time TEXT NOT NULL,
  price_per_seat REAL NOT NULL,
  seats_offered INTEGER NOT NULL,
  ride_status TEXT NOT NULL DEFAULT 'planned',
  total_net_credits_earned REAL DEFAULT 0.00,
  driver_message TEXT NULL,
  is_eco_ride INTEGER NOT NULL DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  ride_id INTEGER NOT NULL,
  seats_booked INTEGER NOT NULL DEFAULT 1,
  booking_status TEXT NOT NULL DEFAULT 'confirmed',
  confirmation_token TEXT UNIQUE NULL,
  token_expires_at TEXT NULL,
  passenger_confirmed_at TEXT NULL,
  credits_transferred_for_this_booking INTEGER DEFAULT 0,
  booking_date TEXT DEFAULT CURRENT_TIMESTAMP,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ride_id INTEGER NOT NULL,
  author_id INTEGER NOT NULL,
  driver_id INTEGER NOT NULL,
  rating INTEGER NOT NULL,
  comment TEXT NULL,
  review_status TEXT NOT NULL DEFAULT 'pending_approval',
  submission_date TEXT DEFAULT CURRENT_TIMESTAMP,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ride_id INTEGER NOT NULL,
  reporter_id INTEGER NOT NULL,
  reported_driver_id INTEGER NOT NULL,
  reason TEXT NOT NULL,
  report_status TEXT NOT NULL DEFAULT 'new',
  submission_date TEXT DEFAULT CURRENT_TIMESTAMP,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);


