ALTER TABLE Rides
ADD COLUMN total_net_credits_earned DECIMAL(10,2) DEFAULT 0.00 AFTER ride_status;

ALTER TABLE Bookings
ADD COLUMN confirmation_token VARCHAR(255) UNIQUE NULL AFTER booking_status,
ADD COLUMN token_expires_at DATETIME NULL AFTER confirmation_token,
ADD COLUMN passenger_confirmed_at DATETIME NULL AFTER token_expires_at,
ADD COLUMN credits_transferred_for_this_booking BOOLEAN DEFAULT FALSE AFTER passenger_confirmed_at;