<?php

try {
    // Connexion à la base de données
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'gestion_reclamations';
    $user = 'root';
    $pass = '';
    
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n";
    
    // Désactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Créer des tables neuves
    $pdo->exec("DROP TABLE IF EXISTS reponse");
    $pdo->exec("DROP TABLE IF EXISTS reclamation");
    
    // Recréer la table reclamation
    $pdo->exec("CREATE TABLE reclamation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        description TEXT NOT NULL,
        statut VARCHAR(50) NOT NULL DEFAULT 'En attente',
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reponse TEXT DEFAULT NULL,
        archived TINYINT(1) DEFAULT 0,
        image_path VARCHAR(255) DEFAULT NULL,
        pdf_path VARCHAR(255) DEFAULT NULL,
        utilisateur VARCHAR(100) DEFAULT NULL,
        categorie VARCHAR(100) DEFAULT NULL
    )");
    
    // Recréer la table reponse
    $pdo->exec("CREATE TABLE reponse (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reclamation_id INT,
        contenu TEXT NOT NULL,
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reclamation_id) REFERENCES reclamation(id)
    )");
    
    // Réactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Tables recréées avec succès. Auto-increment configuré correctement.\n";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 