<?php
// Script de vérification/création de compte superadmin
require_once('../config/database.php');

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Vérification Superadmin</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1>🔐 Vérification des comptes Superadmin</h1>";

try {
    $pdo = getMainDBConnection();
    
    // Vérifier si la table superadmins existe
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'superadmins'");
    if ($tableCheck->rowCount() == 0) {
        echo "<div class='alert alert-warning'>❌ Table 'superadmins' non trouvée</div>";
        
        // Créer la table
        $createTable = "
        CREATE TABLE superadmins (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )";
        
        $pdo->exec($createTable);
        echo "<div class='alert alert-success'>✅ Table 'superadmins' créée</div>";
    } else {
        echo "<div class='alert alert-info'>✅ Table 'superadmins' trouvée</div>";
    }
    
    // Vérifier les comptes existants
    $admins = $pdo->query("SELECT * FROM superadmins")->fetchAll();
    
    echo "<h3>Comptes existants (" . count($admins) . ")</h3>";
    
    if (count($admins) > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Actif</th>
                        <th>Créé le</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($admins as $admin) {
            $status = $admin['active'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-danger">Inactif</span>';
            echo "<tr>
                    <td>{$admin['id']}</td>
                    <td><strong>{$admin['username']}</strong></td>
                    <td>{$admin['full_name']}</td>
                    <td>{$admin['email']}</td>
                    <td>$status</td>
                    <td>{$admin['created_at']}</td>
                  </tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-warning'>❌ Aucun compte superadmin trouvé</div>";
        
        // Proposer de créer un compte
        if (isset($_POST['create_admin'])) {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $fullName = $_POST['full_name'];
            $email = $_POST['email'];
            
            $stmt = $pdo->prepare("INSERT INTO superadmins (username, password, full_name, email) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $password, $fullName, $email])) {
                echo "<div class='alert alert-success'>✅ Compte superadmin créé avec succès !</div>";
                echo "<div class='alert alert-info'>
                        <strong>Identifiants :</strong><br>
                        Nom d'utilisateur : <code>$username</code><br>
                        Mot de passe : <code>{$_POST['password']}</code>
                      </div>";
            } else {
                echo "<div class='alert alert-danger'>❌ Erreur lors de la création du compte</div>";
            }
        } else {
            echo "<div class='card mt-4'>
                    <div class='card-header'>
                        <h5>Créer un compte superadmin</h5>
                    </div>
                    <div class='card-body'>
                        <form method='POST'>
                            <div class='mb-3'>
                                <label class='form-label'>Nom d'utilisateur</label>
                                <input type='text' name='username' class='form-control' value='admin' required>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Mot de passe</label>
                                <input type='password' name='password' class='form-control' value='admin123' required>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Nom complet</label>
                                <input type='text' name='full_name' class='form-control' value='Administrateur Principal' required>
                            </div>
                            <div class='mb-3'>
                                <label class='form-label'>Email</label>
                                <input type='email' name='email' class='form-control' value='admin@geekboard.com'>
                            </div>
                            <button type='submit' name='create_admin' class='btn btn-primary'>Créer le compte</button>
                        </form>
                    </div>
                  </div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
}

echo "<div class='mt-4'>
        <a href='login.php' class='btn btn-success me-2'>🔐 Connexion Superadmin</a>
        <a href='database_manager_test.php' class='btn btn-warning me-2'>🧪 Test DB Manager</a>
        <a href='index.php' class='btn btn-secondary'>🏠 Accueil</a>
      </div>";

echo "</div></body></html>";
?> 