--انشاء قاعدة البيانات taskmanager
CREATE DATABASE IF NOT EXISTS taskmanager 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

USE taskmanager;

--انشاء جدول المستخدمين 
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'member') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

--انشاء جدول المهام
CREATE TABLE IF NOT EXISTS tasks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'inProgress', 'completed') DEFAULT 'pending',
    assigned_to INT(11),
    created_by INT(11),
    due_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
