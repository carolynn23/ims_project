-- database/ims_database.sql

USE ims_db;

CREATE TABLE Users (
    userID INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    role ENUM('Admin', 'Student', 'Employer', 'Lecturer') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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


-- Users
INSERT INTO Users (name, email, passwordHash, phone, role, status) VALUES
('John Admin', 'john.admin@university.com', 'hash1', '123-456-7890', 'Admin', 'Active'),
('Alice Student', 'alice.student@university.com', 'hash2', '234-567-8901', 'Student', 'Active'),
('Bob Employer', 'bob.employer@company.com', 'hash3', '345-678-9012', 'Employer', 'Active'),
('Carol Lecturer', 'carol.lecturer@university.com', 'hash4', '456-789-0123', 'Lecturer', 'Active'),
('Dave Student', 'dave.student@university.com', 'hash5', '567-890-1234', 'Student', 'Active');

-- Internships
INSERT INTO Internships (employerID, title, description, location, duration, requirements, status) VALUES
(3, 'Software Engineering Intern', 'Develop web applications', 'New York', '12 weeks', 'Python, JavaScript', 'Open'),
(3, 'Data Analysis Intern', 'Analyze business data', 'Boston', '10 weeks', 'SQL, Excel', 'Open'),
(3, 'Marketing Intern', 'Social media marketing', 'Remote', '8 weeks', 'Communication skills', 'Closed');

-- Applications
INSERT INTO Applications (studentID, internshipID, status) VALUES
(2, 1, 'Approved'),  -- Alice approved for Software Engineering
(2, 2, 'Pending'),   -- Alice pending for Data Analysis (won't be approved due to constraint)
(5, 2, 'Approved');  -- Dave approved for Data Analysis

-- Assessments
INSERT INTO Assessments (studentID, employerID, performanceScore, feedback) VALUES
(2, 3, 85, 'Great coding skills'),
(5, 3, 78, 'Good analytical skills'),
(2, 3, 90, 'Improved performance');

-- Grades
INSERT INTO Grades (studentID, lecturerID, grade, comments) VALUES
(2, 4, 'A', 'Excellent work'),
(5, 4, 'B', 'Good effort'),
(2, 4, 'A', 'Consistent performance');

-- Reports
INSERT INTO Reports (studentID, internshipID, documentPath) VALUES
(2, 1, '/reports/alice_se_intern.pdf'),
(5, 2, '/reports/dave_da_intern.pdf'),
(2, 1, '/reports/alice_se_final.pdf');

-- Notifications
INSERT INTO Notifications (userID, message, status) VALUES
(2, 'Your application was approved!', 'Read'),
(5, 'New assessment received', 'Unread'),
(3, 'New application received', 'Unread');
