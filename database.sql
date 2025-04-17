-- Create database
CREATE DATABASE IF NOT EXISTS agri_ecommerce;
USE agri_ecommerce;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert products
INSERT INTO products (name, description, price, category, stock, image) VALUES
-- Seeds
('Hybrid Tomato Seeds', 'High-yield hybrid tomato seeds with disease resistance. Ideal for warm climates.', 12.99, 'Seeds', 100, 'https://via.placeholder.com/150?text=Tomato+Seeds'),
('Organic Spinach Seeds', 'Certified organic spinach seeds, fast germination, suitable for backyard gardening.', 4.50, 'Seeds', 100, 'https://via.placeholder.com/150?text=Spinach+Seeds'),

-- Crop Protection
('Neem Oil Pesticide', 'Natural pesticide made from neem extract. Effective against a wide range of pests.', 8.75, 'Crop Protection', 100, 'https://via.placeholder.com/150?text=Neem+Oil'),
('Fungal Shield Spray', 'Broad-spectrum fungicide spray for vegetable and fruit crops.', 14.20, 'Crop Protection', 100, 'https://via.placeholder.com/150?text=Fungal+Spray'),

-- Nutrients
('Nitrogen-Rich Fertilizer (NPK 20-10-10)', 'Boosts vegetative growth. Best for leafy crops.', 18.00, 'Nutrients', 100, 'https://via.placeholder.com/150?text=NPK+Fertilizer'),
('Seaweed Extract', 'Organic plant tonic for better root and shoot development.', 11.90, 'Nutrients', 100, 'https://via.placeholder.com/150?text=Seaweed+Extract'),

-- Equipment
('Handheld Sprayer (5L)', 'Durable pump sprayer for applying pesticides or liquid fertilizers.', 25.00, 'Equipment', 100, 'https://via.placeholder.com/150?text=Handheld+Sprayer'),
('Soil Moisture Meter', 'Accurate soil moisture tester. No batteries required.', 15.99, 'Equipment', 100, 'https://via.placeholder.com/150?text=Soil+Meter'),

-- Organic
('Vermicompost (5kg)', 'Rich organic compost from earthworms. Improves soil health.', 9.50, 'Organic', 100, 'https://via.placeholder.com/150?text=Vermicompost'),
('Panchagavya Plant Tonic', 'Traditional organic growth booster made from five cow products.', 7.25, 'Organic', 100, 'https://via.placeholder.com/150?text=Panchagavya'),

-- Animal Care
('Cattle Mineral Mix', 'Daily supplement for healthy weight gain and milk production.', 6.80, 'Animal Care', 100, 'https://via.placeholder.com/150?text=Mineral+Mix'),
('Poultry Vitamin Tonic', 'Boosts immunity and egg production in chickens and other birds.', 5.30, 'Animal Care', 100, 'https://via.placeholder.com/150?text=Poultry+Tonic');

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@example.com', '$2y$10$8TqZc1qgVxlxU1sL0VLj2ODIx.kpLd6DmHGRwqYpeJXHQJKzXxE0G', 'admin'); 