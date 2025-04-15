<?php

// Chemin vers le dossier cache
$cacheDir = __DIR__ . '/var/cache';

// Fonction pour supprimer récursivement un dossier sous Windows
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// Supprimer le cache
echo "Suppression du cache...\n";
if (is_dir($cacheDir)) {
    $envs = ['dev', 'prod', 'test'];
    
    foreach ($envs as $env) {
        $envCacheDir = $cacheDir . '/' . $env;
        if (is_dir($envCacheDir)) {
            echo "Suppression du cache pour l'environnement $env...\n";
            deleteDirectory($envCacheDir);
        }
    }
    
    echo "Cache supprimé avec succès.\n";
} else {
    echo "Le dossier cache n'existe pas.\n";
}

// Mettre à jour le schéma de la base de données
echo "\nNettoyage terminé.\n";
echo "Maintenant, redémarrez votre serveur avec la commande 'symfony server:start'\n"; 