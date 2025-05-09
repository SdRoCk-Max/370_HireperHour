
CREATE DATABASE IF NOT EXISTS hireperhour;
USE hireperhour;

-- Users table (customers and providers)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('customer', 'provider') NOT NULL
);

-- Service Providers table (extends users)
CREATE TABLE service_providers (
    provider_id INT PRIMARY KEY,
    is_hired BOOLEAN DEFAULT FALSE,
    profession ENUM('Plumber', 'Cleaner', 'Electrician') NOT NULL,
    bio TEXT,
    FOREIGN KEY (provider_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Services offered by providers
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    rate_per_hour DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id) ON DELETE CASCADE
);

-- Bookings made by customers
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    customer_id INT NOT NULL,
    booking_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'accepted', 'done') DEFAULT 'pending',
    total_hours INT NOT NULL,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Payments related to bookings
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('paid', 'unpaid', 'refunded') DEFAULT 'unpaid',
    paid_at DATETIME,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Reviews and Ratings
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);
