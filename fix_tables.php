<?php

try {
    // Connexion à la base de données
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'gestion_reclamations';
    $user = 'root';
    $pass = '';  // Mettez votre mot de passe MySQL ici si nécessaire
    
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n";
    
    // Désactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "Contraintes de clés étrangères désactivées.\n";
    
    // Créer une table temporaire pour les données de reclamation
    $pdo->exec("CREATE TABLE temp_reclamation (
        id INT NOT NULL,
        description TEXT NOT NULL,
        statut VARCHAR(50) NOT NULL,
        date_creation DATETIME NOT NULL,
        reponse TEXT DEFAULT NULL,
        archived TINYINT(1) DEFAULT 0,
        image_path VARCHAR(255) DEFAULT NULL,
        pdf_path VARCHAR(255) DEFAULT NULL,
        utilisateur VARCHAR(100) DEFAULT NULL,
        categorie VARCHAR(100) DEFAULT NULL
    )");
    echo "Table temporaire temp_reclamation créée.\n";
    
    // Copier les données de reclamation dans la table temporaire
    $pdo->exec("INSERT INTO temp_reclamation SELECT * FROM reclamation");
    echo "Données copiées dans la table temporaire.\n";
    
    // Créer une table temporaire pour les données de reponse
    $pdo->exec("CREATE TABLE temp_reponse (
        id INT NOT NULL,
        reclamation_id INT,
        contenu TEXT NOT NULL,
        date_creation DATETIME NOT NULL
    )");
    echo "Table temporaire temp_reponse créée.\n";
    
    // Copier les données de reponse dans la table temporaire
    $pdo->exec("INSERT INTO temp_reponse SELECT * FROM reponse");
    echo "Données copiées dans la table temporaire.\n";
    
    // Supprimer les tables originales
    $pdo->exec("DROP TABLE IF EXISTS reponse");
    echo "Table reponse supprimée.\n";
    
    $pdo->exec("DROP TABLE IF EXISTS reclamation");
    echo "Table reclamation supprimée.\n";
    
    // Recréer la table reclamation avec AUTO_INCREMENT
    $pdo->exec("CREATE TABLE reclamation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        description TEXT NOT NULL,
        statut VARCHAR(50) NOT NULL,
        date_creation DATETIME NOT NULL,
        reponse TEXT DEFAULT NULL,
        archived TINYINT(1) DEFAULT 0,
        image_path VARCHAR(255) DEFAULT NULL,
        pdf_path VARCHAR(255) DEFAULT NULL,
        utilisateur VARCHAR(100) DEFAULT NULL,
        categorie VARCHAR(100) DEFAULT NULL
    )");
    echo "Table reclamation recréée avec AUTO_INCREMENT.\n";
    
    // Réinsérer les données de reclamation SANS spécifier l'ID
    $pdo->exec("INSERT INTO reclamation 
                (description, statut, date_creation, reponse, archived, image_path, pdf_path, utilisateur, categorie)
                SELECT description, statut, date_creation, reponse, archived, image_path, pdf_path, utilisateur, categorie 
                FROM temp_reclamation");
    echo "Données de réclamation réinsérées avec de nouveaux IDs.\n";
    
    // Créer une table de correspondance pour les IDs
    $pdo->exec("CREATE TABLE id_mapping (
        old_id INT NOT NULL,
        new_id INT NOT NULL
    )");
    echo "Table de correspondance id_mapping créée.\n";
    
    // Remplir la table de correspondance
    $stmt = $pdo->query("SELECT t.id as old_id, r.id as new_id 
                         FROM temp_reclamation t 
                         JOIN reclamation r ON 
                            t.description = r.description AND
                            t.date_creation = r.date_creation");
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($mappings as $mapping) {
        $old_id = $mapping['old_id'];
        $new_id = $mapping['new_id'];
        $pdo->exec("INSERT INTO id_mapping (old_id, new_id) VALUES ($old_id, $new_id)");
    }
    echo "Table de correspondance remplie avec " . count($mappings) . " entrées.\n";
    
    // Recréer la table reponse avec AUTO_INCREMENT
    $pdo->exec("CREATE TABLE reponse (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reclamation_id INT,
        contenu TEXT NOT NULL,
        date_creation DATETIME NOT NULL,
        FOREIGN KEY (reclamation_id) REFERENCES reclamation(id)
    )");
    echo "Table reponse recréée avec AUTO_INCREMENT.\n";
    
    // Réinsérer les données de reponse avec les nouveaux IDs de reclamation
    $reponses = $pdo->query("SELECT * FROM temp_reponse")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reponses as $reponse) {
        $old_reclamation_id = $reponse['reclamation_id'];
        $contenu = $pdo->quote($reponse['contenu']);
        $date_creation = $pdo->quote($reponse['date_creation']);
        
        // Trouver le nouvel ID de reclamation
        $stmt = $pdo->query("SELECT new_id FROM id_mapping WHERE old_id = $old_reclamation_id");
        $mapping = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mapping) {
            $new_reclamation_id = $mapping['new_id'];
            $pdo->exec("INSERT INTO reponse (reclamation_id, contenu, date_creation) 
                        VALUES ($new_reclamation_id, $contenu, $date_creation)");
        }
    }
    echo "Données de réponse réinsérées avec les nouveaux IDs de référence.\n";
    
    // Supprimer les tables temporaires
    $pdo->exec("DROP TABLE temp_reclamation");
    $pdo->exec("DROP TABLE temp_reponse");
    $pdo->exec("DROP TABLE id_mapping");
    echo "Tables temporaires supprimées.\n";
    
    // Réactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Contraintes de clés étrangères réactivées.\n";
    
    echo "Opération terminée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 