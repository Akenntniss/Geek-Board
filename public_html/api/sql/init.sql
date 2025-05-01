-- Supprimer la table si elle existe déjà
DROP TABLE IF EXISTS conges_jours_disponibles;

-- Table pour les jours disponibles dans le calendrier des congés
CREATE TABLE conges_jours_disponibles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer l'index après la table
CREATE INDEX idx_date ON conges_jours_disponibles(date);

-- Table des catégories de produits
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des produits
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix_achat DECIMAL(10,2) NOT NULL,
    prix_vente DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL DEFAULT 0,
    seuil_alerte INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Index pour la recherche par référence
CREATE INDEX idx_produits_reference ON produits(reference);

-- Table des mouvements de stock
CREATE TABLE mouvements_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    type_mouvement ENUM('entree', 'sortie') NOT NULL,
    quantite INT NOT NULL,
    motif TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Index pour les mouvements de stock
CREATE INDEX idx_mouvements_produit ON mouvements_stock(produit_id);
CREATE INDEX idx_mouvements_date ON mouvements_stock(created_at);

-- Table des fournisseurs
CREATE TABLE IF NOT EXISTS fournisseurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    contact_nom VARCHAR(100),
    email VARCHAR(255),
    telephone VARCHAR(20),
    adresse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des commandes fournisseurs
CREATE TABLE IF NOT EXISTS commandes_fournisseurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fournisseur_id INT NOT NULL,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'validee', 'recue', 'annulee') DEFAULT 'en_attente',
    montant_total DECIMAL(10,2),
    notes TEXT,
    user_id INT NOT NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des lignes de commandes fournisseurs
CREATE TABLE IF NOT EXISTS lignes_commande_fournisseur (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes_fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
); 