-- database/ims_database.sql

USE ims_db;

CREATE TABLE Users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Lecturer', 'Employer') NOT NULL,
    studentID VARCHAR(20) DEFAULT NULL UNIQUE, -- Optional, unique for students
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reset_tokens (
    tokenID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT,
    token VARCHAR(100) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (userID) REFERENCES Users(userID)
);

CREATE TABLE Internships (
    internshipID INT PRIMARY KEY AUTO_INCREMENT,
    employerID INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255),
    duration VARCHAR(50),
    requirements TEXT,
    status ENUM('Open', 'Closed') DEFAULT 'Open',
    postedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employerID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE Applications (
    applicationID INT PRIMARY KEY AUTO_INCREMENT,
    studentID INT NOT NULL,
    internshipID INT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    appliedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (studentID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (internshipID) REFERENCES Internships(internshipID) ON DELETE CASCADE
);

DELIMITER //
CREATE TRIGGER check_one_approved
BEFORE UPDATE ON Applications
FOR EACH ROW
BEGIN
    IF NEW.status = 'Approved' THEN
        IF EXISTS (
            SELECT 1 
            FROM Applications 
            WHERE studentID = NEW.studentID 
            AND status = 'Approved' 
            AND applicationID != NEW.applicationID
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Student already has an approved application';
        END IF;
    END IF;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER check_one_approved_insert
BEFORE INSERT ON Applications
FOR EACH ROW
BEGIN
    IF NEW.status = 'Approved' THEN
        IF EXISTS (
            SELECT 1 
            FROM Applications 
            WHERE studentID = NEW.studentID 
            AND status = 'Approved'
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Student already has an approved application';
        END IF;
    END IF;
END//
DELIMITER ;

CREATE TABLE Assessments (
    assessmentID INT PRIMARY KEY AUTO_INCREMENT,
    studentID INT NOT NULL,
    employerID INT NOT NULL,
    performanceScore INT CHECK (performanceScore BETWEEN 1 AND 100),
    feedback TEXT,
    assessedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (studentID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (employerID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE Grades (
    gradeID INT PRIMARY KEY AUTO_INCREMENT,
    studentID INT NOT NULL,
    lecturerID INT NOT NULL,
    grade CHAR(2) CHECK (grade IN ('A', 'B', 'C', 'D', 'F')),
    comments TEXT,
    gradedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (studentID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (lecturerID) REFERENCES Users(userID) ON DELETE CASCADE
);

CREATE TABLE Reports (
    reportID INT PRIMARY KEY AUTO_INCREMENT,
    studentID INT NOT NULL,
    internshipID INT NOT NULL,
    documentPath VARCHAR(255) NOT NULL,
    submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (studentID) REFERENCES Users(userID) ON DELETE CASCADE,
    FOREIGN KEY (internshipID) REFERENCES Internships(internshipID) ON DELETE CASCADE
);

CREATE TABLE Notifications (
    notificationID INT PRIMARY KEY AUTO_INCREMENT,
    userID INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Unread', 'Read') DEFAULT 'Unread',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
);

-- Sample Data
INSERT INTO Users (name, email, passwordHash, role, studentID) VALUES
('Admin User', 'admin@ims.com', 'hashedpass1', 'Admin', NULL),
('Alice Student', 'alice@ims.com', 'hashedpass2', 'Student', 'STU001'),
('Bob Employer', 'bob@ims.com', 'hashedpass3', 'Employer', NULL);

