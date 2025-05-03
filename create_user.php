<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/config/bootstrap.php';

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine.orm.entity_manager');

// Créer un nouvel utilisateur
$user = new User();
$user->setEmail('joueur2@example.com');
$user->setRoles(['ROLE_USER']);
$user->setNom('Joueur');
$user->setPrenom('Deux');

// Hasher le mot de passe - différentes méthodes selon la version de Symfony
if ($container->has(UserPasswordHasherInterface::class)) {
    $passwordHasher = $container->get(UserPasswordHasherInterface::class);
    $hashedPassword = $passwordHasher->hashPassword($user, 'MotDePasse123!');
} elseif ($container->has(UserPasswordEncoderInterface::class)) {
    $passwordEncoder = $container->get(UserPasswordEncoderInterface::class);
    $hashedPassword = $passwordEncoder->encodePassword($user, 'MotDePasse123!');
} else {
    die("Impossible de trouver le service de hachage de mot de passe");
}

$user->setPassword($hashedPassword);

// Persister l'utilisateur
$entityManager->persist($user);
$entityManager->flush();

echo "Utilisateur créé avec succès!\n";
echo "Email: joueur2@example.com\n";
echo "Mot de passe: MotDePasse123!\n"; 