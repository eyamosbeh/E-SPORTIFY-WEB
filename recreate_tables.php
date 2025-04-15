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
    
    // Récupérer les données de reponse avant de supprimer la table
    $stmt = $pdo->query("SELECT * FROM reponse");
    $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sauvegarde de " . count($reponses) . " réponses.\n";
    
    // Récupérer les données de reclamation avant de supprimer la table
    $stmt = $pdo->query("SELECT * FROM reclamation");
    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sauvegarde de " . count($reclamations) . " réclamations.\n";
    
    // Supprimer les tables dans l'ordre pour éviter les problèmes de clé étrangère
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
        utilisateur VARCHAR(100) DEFAULT 'anonymous',
        categorie VARCHAR(100) DEFAULT 'general'
    )");
    echo "Table reclamation recréée avec AUTO_INCREMENT.\n";
    
    // Recréer la table reponse avec AUTO_INCREMENT
    $pdo->exec("CREATE TABLE reponse (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reclamation_id INT,
        contenu TEXT NOT NULL,
        date_creation DATETIME NOT NULL,
        FOREIGN KEY (reclamation_id) REFERENCES reclamation(id)
    )");
    echo "Table reponse recréée avec AUTO_INCREMENT.\n";
    
    // Réinsérer les données de reclamation
    if (count($reclamations) > 0) {
        echo "Réinsertion des données de réclamation...\n";
        foreach ($reclamations as $reclamation) {
            // Pour éviter de réutiliser les anciens IDs qui causent des problèmes
            $id = isset($reclamation['id']) ? $reclamation['id'] : null;
            $description = isset($reclamation['description']) ? $pdo->quote($reclamation['description']) : 'NULL';
            $statut = isset($reclamation['statut']) ? $pdo->quote($reclamation['statut']) : "'En attente'";
            $date_creation = isset($reclamation['date_creation']) ? $pdo->quote($reclamation['date_creation']) : 'NOW()';
            $reponse = isset($reclamation['reponse']) ? $pdo->quote($reclamation['reponse']) : 'NULL';
            $archived = isset($reclamation['archived']) ? (int)$reclamation['archived'] : 0;
            $image_path = isset($reclamation['image_path']) ? $pdo->quote($reclamation['image_path']) : 'NULL';
            $pdf_path = isset($reclamation['pdf_path']) ? $pdo->quote($reclamation['pdf_path']) : 'NULL';
            $utilisateur = isset($reclamation['utilisateur']) ? $pdo->quote($reclamation['utilisateur']) : 'NULL';
            $categorie = isset($reclamation['categorie']) ? $pdo->quote($reclamation['categorie']) : 'NULL';
            
            $sql = "INSERT INTO reclamation (id, description, statut, date_creation, reponse, archived, image_path, pdf_path, utilisateur, categorie) 
                    VALUES ($id, $description, $statut, $date_creation, $reponse, $archived, $image_path, $pdf_path, $utilisateur, $categorie)";
            $pdo->exec($sql);
        }
        echo "Données de réclamation réinsérées.\n";
    }
    
    // Réinsérer les données de reponse
    if (count($reponses) > 0) {
        echo "Réinsertion des données de réponse...\n";
        foreach ($reponses as $reponse) {
            $reclamation_id = isset($reponse['reclamation_id']) ? $reponse['reclamation_id'] : 'NULL';
            $contenu = isset($reponse['contenu']) ? $pdo->quote($reponse['contenu']) : 'NULL';
            $date_creation = isset($reponse['date_creation']) ? $pdo->quote($reponse['date_creation']) : 'NOW()';
            
            $sql = "INSERT INTO reponse (reclamation_id, contenu, date_creation) 
                    VALUES ($reclamation_id, $contenu, $date_creation)";
            $pdo->exec($sql);
        }
        echo "Données de réponse réinsérées.\n";
    }
    
    // Réactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Contraintes de clés étrangères réactivées.\n";
    
    echo "Opération terminée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 