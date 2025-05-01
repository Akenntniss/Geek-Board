-- Table pour stocker les SMS envoyés
CREATE TABLE IF NOT EXISTS sms_outgoing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') NOT NULL DEFAULT 'pending',
    sent_timestamp DATETIME NULL,
    delivery_timestamp DATETIME NULL,
    retry_count INT NOT NULL DEFAULT 0,
    reference_type VARCHAR(50) NULL COMMENT 'Type de référence (reparation, client, etc.)',
    reference_id INT NULL COMMENT 'ID de la référence',
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table pour stocker les SMS reçus
CREATE TABLE IF NOT EXISTS sms_incoming (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    received_timestamp DATETIME NOT NULL,
    status ENUM('new', 'processed', 'responded') NOT NULL DEFAULT 'new',
    processed_timestamp DATETIME NULL,
    reference_type VARCHAR(50) NULL COMMENT 'Type de référence (reparation, client, etc.)',
    reference_id INT NULL COMMENT 'ID de la référence',
    response_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (response_id) REFERENCES sms_outgoing(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table pour les configurations SMS
CREATE TABLE IF NOT EXISTS sms_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    param_name VARCHAR(50) NOT NULL UNIQUE,
    param_value TEXT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insérer les paramètres de configuration par défaut
INSERT INTO sms_config (param_name, param_value, description) VALUES
('api_key', MD5(CONCAT(RAND(), NOW())), 'Clé API pour authentifier les requêtes du téléphone'),
('default_sender_name', 'VotreEntreprise', 'Nom d\'expéditeur par défaut'),
('max_retries', '3', 'Nombre maximum de tentatives d\'envoi'),
('sms_enabled', '1', 'Activation du service SMS (1=actif, 0=inactif)'),
('notification_types', 'reparation_status,appointment_reminder', 'Types de notifications activées');

-- Ajouter le type de notification SMS
INSERT INTO notification_types (type_code, description, icon, color, importance) VALUES
('sms_notification', 'Notification SMS', 'message', '#28a745', 'normale')
ON DUPLICATE KEY UPDATE description = 'Notification SMS'; 