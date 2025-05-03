<?php

// Connexion directe à la base de données
$dbParams = require __DIR__ . '/config/db_params.php';

try {
    $pdo = new PDO(
        "mysql:host={$dbParams['host']};dbname={$dbParams['dbname']};charset=utf8mb4",
        $dbParams['user'],
        $dbParams['password']
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mot de passe simple à utiliser pour la connexion
    $plainPassword = 'password123';
    
    // Hash du mot de passe généré avec password_hash qui est compatible avec Symfony
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 13]);
    
    // Vérifier si l'utilisateur test@example.com existe déjà
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
    $stmt->execute(['email' => 'test@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE email = :email");
        $stmt->execute([
            'password' => $hashedPassword,
            'email' => 'test@example.com'
        ]);
        
        echo "Mot de passe mis à jour pour test@example.com\n";
    } else {
        // Créer un nouvel utilisateur
        $stmt = $pdo->prepare("INSERT INTO user (email, roles, password, nom, prenom) 
                               VALUES (:email, :roles, :password, :nom, :prenom)");
        $stmt->execute([
            'email' => 'test@example.com',
            'roles' => json_encode(['ROLE_USER']),
            'password' => $hashedPassword,
            'nom' => 'Test',
            'prenom' => 'User'
        ]);
        
        echo "Nouvel utilisateur créé: test@example.com\n";
    }
    
    echo "--------------------------------------\n";
    echo "Informations de connexion:\n";
    echo "Email: test@example.com\n";
    echo "Mot de passe: {$plainPassword}\n";
    echo "--------------------------------------\n";
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
} 