# 🌿 Farm to Table (Farmer to Public)

A modern, direct-to-consumer agricultural e-commerce platform that connects **Farmers**, **Consumers**, **Delivery Drivers**, and **System Administrators** in real time.

---

## 🔑 Demo Accounts & Credentials

All default seed accounts use the password: **`password`**

| Role | Name | Email | Password | Access & Dashboard Features |
| :--- | :--- | :--- | :--- | :--- |
| 🛡️ **Admin** | Admin User | `admin@farmtopublic.com` | `password` | Live system overview, user management, product editing/deletion, order filtering |
| 🌾 **Farmer** | John the Farmer | `john@farm.com` | `password` | List new produce, set pricing & stock, track incoming orders |
| 🛒 **Consumer** | Alice the Consumer | `alice@home.com` | `password` | Browse available farm produce, add items to cart, checkout, view order history |
| 🚚 **Driver** | Bob the Driver | `bob@delivery.com` | `password` | View assigned deliveries, step-by-step navigation to Farmer & Consumer via Google Maps, update order statuses |

---

## 🗄️ Database Setup & Installation

### 1. Prerequisites
- **XAMPP** (or any Apache + MySQL / MariaDB stack)
- PHP 7.4+ or 8.x

### 2. Database Initialization
1. Start **Apache** and **MySQL** via XAMPP Control Panel.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Import the database schema:
   ```bash
   mysql -u root -p < schema.sql
   ```
4. Import the initial seed data:
   ```bash
   mysql -u root -p farm_to_table < seed.sql
   ```

### 3. Database Configuration
Verify or modify database connection settings in [`includes/db.php`](file:///c:/xampp/htdocs/Farmer%20to%20Public/includes/db.php):
```php
$host    = '127.0.0.1';
$db      = 'farm_to_table';
$user    = 'root';
$pass    = ''; // Default XAMPP password
$charset = 'utf8mb4';
```

---

## 📋 Database Schema Verification & Verification Summary

Both [`schema.sql`](file:///c:/xampp/htdocs/Farmer%20to%20Public/schema.sql) and [`seed.sql`](file:///c:/xampp/htdocs/Farmer%20to%20Public/seed.sql) have been reviewed and verified:

- **Schema Tables**:
  - `Users` (ID, Name, Email [UNIQUE], PasswordHash, Role [ENUM: Farmer, Consumer, Driver, Admin], Address, Coordinates)
  - `Products` (ID, FarmerID [FK -> Users], Name, Description, Price, Stock, ImageURL)
  - `Orders` (ID, ConsumerID [FK -> Users], TotalAmount, OrderDate, Status [ENUM: Pending, Processing, In Transit, Delivered, Cancelled])
  - `OrderItems` (ID, OrderID [FK -> Orders], ProductID [FK -> Products], Quantity, PriceAtTime)
  - `Deliveries` (ID, OrderID [FK -> Orders], DriverID [FK -> Users], PickupTime, DeliveryTime)
- **Foreign Keys**: Configured with `ON DELETE CASCADE` across relational constraints.
- **Seeded Data Alignment**: User IDs, Product IDs, Order Amounts, and Driver assignments in `seed.sql` match table structures and calculation logic.
- **Password Hashes**: Hashed using standard PHP `PASSWORD_DEFAULT` (bcrypt) compatible with `password_verify()` during login.

---

## 🚀 Features & User Roles

- **Real-Time Admin Control Panel**: Live polling updates every 5 seconds to track users, products, orders, and active deliveries without page reloads.
- **Farmer Management**: Simple form to list products with price in LKR, initial stock, and descriptions.
- **Consumer E-Commerce**: Reactive shopping cart, dynamic stock check, and instant driver assignment.
- **Driver Route Guidance**: Integrated Google Maps navigation for pickup from farmer coordinates/address and delivery to consumer location.

---

## 📁 File Directory Overview

```
Farmer to Public/
├── api/
│   ├── admin.php           # Real-time data & statistics endpoint for Admin
│   ├── driver.php          # Delivery status updates & assigned routes
│   ├── orders.php          # Checkout processing & consumer order retrieval
│   └── products.php        # Farmer & Consumer product CRUD operations
├── assets/
│   ├── css/                # Custom styling (style.css, admin.css, auth.css, etc.)
│   └── js/                 # Application logic & cart helpers (app.js)
├── includes/
│   ├── auth.php            # Session validation & role-based access control
│   └── db.php              # PDO database connection setup
├── admin_dashboard.php     # Admin control panel
├── admin_edit_product.php  # Admin product edit view
├── admin_edit_user.php     # Admin user edit view
├── consumer_dashboard.php # Consumer shop & order history
├── driver_dashboard.php   # Driver delivery management & GPS routing
├── farmer_dashboard.php   # Farmer product management & order list
├── index.php              # Role-based landing page redirect
├── login.php              # Authentication page
├── register.php           # User registration with location detection
├── schema.sql             # SQL DDL database creation script
├── seed.sql               # SQL DML seed data script
└── README.md              # Project documentation & passwords
```
