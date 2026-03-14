CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) DEFAULT NULL,
    fullname VARCHAR(100) NOT NULL,
    role VARCHAR(50) DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    is_archived TINYINT(1) DEFAULT 0, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (created_at),
    INDEX (is_archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id VARCHAR(20) NOT NULL UNIQUE, 
    fullname VARCHAR(100) NOT NULL, 
    email VARCHAR(100) NOT NULL UNIQUE, 
    password VARCHAR(255) NOT NULL, 
    role VARCHAR(50) NOT NULL, 
    department VARCHAR(100) NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

CREATE TABLE IF NOT EXISTS schedules ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    shift_name VARCHAR(100) NOT NULL, 
    time_from TIME NOT NULL, 
    time_to TIME NOT NULL, 
    status ENUM('Active', 'Inactive') DEFAULT 'Active', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

CREATE TABLE IF NOT EXISTS item_declarations ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id VARCHAR(50) NOT NULL, 
    fullname VARCHAR(100) NOT NULL, 
    department VARCHAR(100) DEFAULT NULL, -- Added department
    purpose VARCHAR(255) DEFAULT NULL, -- Purpose of visit (Visitors only)
    shift_id INT NOT NULL, 
    declaration_date DATE NOT NULL, 
    timeout_date DATE DEFAULT NULL, 
    time_in TIME NOT NULL, 
    time_out TIME DEFAULT NULL, 
    items_json TEXT NOT NULL, 
    status ENUM('Logged In', 'Logged Out') DEFAULT 'Logged In', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    INDEX (user_id), 
    INDEX (declaration_date) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

CREATE TABLE IF NOT EXISTS security_logs ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    action VARCHAR(255) NOT NULL, 
    details TEXT NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    INDEX (created_at) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

CREATE TABLE IF NOT EXISTS tip_ledger ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    recipient_name VARCHAR(100) NOT NULL, 
    amount DECIMAL(10, 2) NOT NULL, 
    remarks VARCHAR(255) DEFAULT NULL, 
    processed_by VARCHAR(100) NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    INDEX (created_at) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
