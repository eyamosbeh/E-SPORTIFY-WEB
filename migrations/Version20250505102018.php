<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250505102018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Finalisation de la relation entre Reclamation et Utilisateur';
    }

    public function up(Schema $schema): void
    {
        // Mise à jour des données existantes
        $this->addSql('UPDATE reclamation SET utilisateur_id = 1 WHERE utilisateur_id IS NULL');
        
        // Modification de la colonne pour la rendre non nullable
        $this->addSql('ALTER TABLE reclamation MODIFY utilisateur_id INT NOT NULL');
        
        // Suppression de l'ancienne colonne si elle existe encore
        $this->addSql('ALTER TABLE reclamation DROP COLUMN IF EXISTS utilisateur');
    }

    public function down(Schema $schema): void
    {
        // Ajout de l'ancienne colonne
        $this->addSql('ALTER TABLE reclamation ADD utilisateur VARCHAR(255) DEFAULT NULL');
        
        // Copie des données
        $this->addSql('UPDATE reclamation r INNER JOIN utilisateur u ON r.utilisateur_id = u.id SET r.utilisateur = u.email');
        
        // Rendre la colonne utilisateur_id nullable
        $this->addSql('ALTER TABLE reclamation MODIFY utilisateur_id INT NULL');
    }
} 