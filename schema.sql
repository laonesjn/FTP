CREATE DATABASE IF NOT EXISTS farm_to_table;
USE farm_to_table;

CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('Farmer', 'Consumer', 'Driver', 'Admin') NOT NULL,
    Address TEXT,
    Coordinates VARCHAR(100) -- Example format: "latitude,longitude"
);

CREATE TABLE IF NOT EXISTS Products (
    ProductID INT AUTO_INCREMENT PRIMARY KEY,
    FarmerID INT NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Description TEXT,
    Price DECIMAL(10, 2) NOT NULL, -- Price in Sri Lankan Rupees (LKR)
    Stock INT NOT NULL DEFAULT 0,
    ImageURL VARCHAR(255),
    FOREIGN KEY (FarmerID) REFERENCES Users(UserID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Orders (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    ConsumerID INT NOT NULL,
    TotalAmount DECIMAL(10, 2) NOT NULL, -- Total Amount in Sri Lankan Rupees (LKR)
    OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending', 'Processing', 'In Transit', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (ConsumerID) REFERENCES Users(UserID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS OrderItems (
    OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    PriceAtTime DECIMAL(10, 2) NOT NULL, -- Price at time of order in Sri Lankan Rupees (LKR)
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Deliveries (
    DeliveryID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    DriverID INT NOT NULL,
    PickupTime DATETIME,
    DeliveryTime DATETIME,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (DriverID) REFERENCES Users(UserID) ON DELETE CASCADE
);
