<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Définir l'environnement et l'utilisateur
$email = "admin@example.com";
$plainPassword = "admin123";
$nom = "Admin";
$prenom = "User";
$role = "ROLE_ADMIN";

// Hash du mot de passe
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

echo "Connexion à la base de données...\n";

try {
    // Récupérer les informations de connexion à la base de données
    $databaseUrl = $_ENV['DATABASE_URL'];
    $dbParams = parse_url($databaseUrl);
    
    $dbHost = $dbParams['host'];
    $dbPort = $dbParams['port'] ?? 3306;
    $dbName = ltrim($dbParams['path'], '/');
    $dbUser = $dbParams['user'];
    $dbPass = $dbParams['pass'];
    
    echo "Paramètres de connexion: Host=$dbHost, DB=$dbName, User=$dbUser\n";
    
    // Connexion à la base de données
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connexion réussie à la base de données.\n";
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "L'utilisateur $email existe déjà. Mise à jour...\n";
        
        // Mettre à jour l'utilisateur
        $stmt = $pdo->prepare("UPDATE user SET password = ?, roles = ?, nom = ?, prenom = ? WHERE email = ?");
        $stmt->execute([
            $hashedPassword,
            json_encode([$role]),
            $nom,
            $prenom,
            $email
        ]);
        
        echo "Utilisateur mis à jour avec succès.\n";
    } else {
        echo "Création d'un nouvel utilisateur: $email\n";
        
        // Créer un nouvel utilisateur
        $stmt = $pdo->prepare("INSERT INTO user (email, roles, password, nom, prenom) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $email,
            json_encode([$role]),
            $hashedPassword,
            $nom,
            $prenom
        ]);
        
        echo "Nouvel utilisateur créé avec succès.\n";
    }
    
    // Créons aussi un utilisateur standard
    $userEmail = "user@example.com";
    $userPassword = "user123";
    $userHashedPassword = password_hash($userPassword, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$userEmail]);
    $userExists = $stmt->fetch();
    
    if ($userExists) {
        $stmt = $pdo->prepare("UPDATE user SET password = ?, roles = ?, nom = ?, prenom = ? WHERE email = ?");
        $stmt->execute([
            $userHashedPassword,
            json_encode(['ROLE_USER']),
            'Simple',
            'Utilisateur',
            $userEmail
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user (email, roles, password, nom, prenom) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userEmail,
            json_encode(['ROLE_USER']),
            $userHashedPassword,
            'Simple',
            'Utilisateur'
        ]);
    }
    
    echo "\n-------------------------------------------\n";
    echo "UTILISATEURS CRÉÉS OU MIS À JOUR\n";
    echo "-------------------------------------------\n";
    echo "ADMIN:\n";
    echo "Email: $email\n";
    echo "Mot de passe: $plainPassword\n";
    echo "\nUTILISATEUR:\n";
    echo "Email: $userEmail\n";
    echo "Mot de passe: $userPassword\n";
    echo "-------------------------------------------\n";
    
} catch (PDOException $e) {
    die("Erreur de base de données: " . $e->getMessage());
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
} 