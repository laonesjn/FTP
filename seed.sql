USE farm_to_table;

-- Seed Users (Passwords are hashed versions of 'password')
INSERT INTO Users (Name, Email, PasswordHash, Role, Address, Coordinates) VALUES
('John the Farmer', 'john@farm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Farmer', '123 Country Road, Nuwara Eliya', '6.9497,80.7891'),
('Alice the Consumer', 'alice@home.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Consumer', '456 Galle Road, Colombo 03', '6.9016,79.8547'),
('Bob the Driver', 'bob@delivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Driver', '789 Transport Blvd, Kelaniya', '6.9553,79.9172'),
('Admin User', 'admin@farmtopublic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Admin Office, Colombo', '6.9271,79.8612');

-- Seed Products (Prices in LKR)
INSERT INTO Products (FarmerID, Name, Description, Price, Stock, ImageURL) VALUES
(1, 'Fresh Organic Apples', 'Crisp and sweet apples straight from Nuwara Eliya orchards.', 650.00, 100, 'apples.jpg'),
(1, 'Farm Fresh Eggs', 'A dozen organic free-range eggs.', 480.00, 50, 'eggs.jpg'),
(1, 'Heirloom Tomatoes', 'Juicy and ripe Sri Lankan heirloom tomatoes.', 350.00, 200, 'tomatoes.jpg');

-- Seed Orders (Amounts in LKR)
INSERT INTO Orders (ConsumerID, TotalAmount, Status) VALUES
(2, 1130.00, 'Pending'),
(2, 480.00, 'Processing');

-- Seed Order Items (PriceAtTime in LKR)
INSERT INTO OrderItems (OrderID, ProductID, Quantity, PriceAtTime) VALUES
(1, 1, 1, 650.00),
(1, 2, 1, 480.00),
(2, 2, 1, 480.00);

-- Seed Deliveries
INSERT INTO Deliveries (OrderID, DriverID, PickupTime, DeliveryTime) VALUES
(2, 3, '2026-09-23 10:00:00', NULL);
