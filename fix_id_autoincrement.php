<?php

// Charger les variables d'environnement du .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

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
    
    // Modifier la colonne ID pour qu'elle soit en AUTO_INCREMENT
    $pdo->exec("ALTER TABLE reclamation MODIFY id INT AUTO_INCREMENT");
    echo "Modification de la colonne id en AUTO_INCREMENT réussie pour la table reclamation.\n";
    
    // Faire de même pour la table reponse
    $pdo->exec("ALTER TABLE reponse MODIFY id INT AUTO_INCREMENT");
    echo "Modification de la colonne id en AUTO_INCREMENT réussie pour la table reponse.\n";
    
    // Réactiver les contraintes de clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Contraintes de clés étrangères réactivées.\n";
    
    echo "Opération terminée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 