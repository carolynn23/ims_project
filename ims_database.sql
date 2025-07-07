
USE ims2_db;
CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Employer', 'Lecturer') NOT NULL,
    profile_setup BOOLEAN DEFAULT FALSE,
    field_of_interest VARCHAR(255)

)