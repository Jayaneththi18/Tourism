CREATE DATABASE IF NOT EXISTS tourism_db;
USE tourism_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('client', 'admin') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password, full_name, role) VALUES 
('demouser', 'demo@dreamtoursrilanka.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', 'client'),
('admin', 'admin@dreamtoursrilanka.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration VARCHAR(50),
    location VARCHAR(100),
    image_url VARCHAR(255),
    rating DECIMAL(2, 1) DEFAULT 5.0,
    inclusions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    booking_date DATE NOT NULL,
    num_people INT DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO packages (title, description, price, duration, location, image_url, rating, inclusions) VALUES 
('Cultural Triangle Tour', 'Explore ancient cities of Anuradhapura, Polonnaruwa, and Sigiriya', 299.99, '5 Days / 4 Nights', 'Central Province', 'https://images.unsplash.com/photo-1548013284-72e7b461ea60?w=500&h=300&fit=crop', 4.8, 'Accommodation, Breakfast, Guide, Transport'),
('Beach Paradise Getaway', 'Relax on pristine beaches of Mirissa, Unawatuna, and Bentota', 249.99, '4 Days / 3 Nights', 'Southern Coast', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=500&h=300&fit=crop', 4.9, 'Beachfront Hotel, All Meals, Water Sports'),
('Hill Country Adventure', 'Experience tea plantations, waterfalls, and cool climate', 349.99, '6 Days / 5 Nights', 'Nuwara Eliya', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=500&h=300&fit=crop', 4.7, 'Hotels, Meals, Train Ride, Tea Factory Tour'),
('Wildlife Safari', 'Spot elephants, leopards, and exotic birds in Yala National Park', 399.99, '3 Days / 2 Nights', 'Yala', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=500&h=300&fit=crop', 4.9, 'Safari Jeep, Accommodation, Meals, Park Fees'),
('Complete Sri Lanka', 'Comprehensive tour covering beaches, mountains, culture, and wildlife', 899.99, '12 Days / 11 Nights', 'Island Wide', 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=500&h=300&fit=crop', 5.0, 'All Hotels, All Meals, Transport, Guides, Activities');
