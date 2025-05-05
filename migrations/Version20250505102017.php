<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250505102017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modification de la relation entre Reclamation et Utilisateur';
    }

    public function up(Schema $schema): void
    {
        // Ajout de la nouvelle colonne utilisateur_id, nullable au début
        $this->addSql('ALTER TABLE reclamation ADD utilisateur_id INT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_CE606404FB88E14F ON reclamation (utilisateur_id)');

        // Mise à jour des données : associer chaque réclamation à un utilisateur par défaut (id=1)
        $this->addSql('UPDATE reclamation SET utilisateur_id = 1 WHERE utilisateur_id IS NULL');

        // Rendre la colonne non nullable après la mise à jour des données
        $this->addSql('ALTER TABLE reclamation MODIFY utilisateur_id INT NOT NULL');

        // Suppression de l'ancienne colonne
        $this->addSql('ALTER TABLE reclamation DROP COLUMN utilisateur');
    }

    public function down(Schema $schema): void
    {
        // Ajout de l'ancienne colonne
        $this->addSql('ALTER TABLE reclamation ADD utilisateur VARCHAR(255) DEFAULT NULL');

        // Copie des données si nécessaire
        $this->addSql('UPDATE reclamation r INNER JOIN utilisateur u ON r.utilisateur_id = u.id SET r.utilisateur = u.email');

        // Suppression de la nouvelle structure
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404FB88E14F');
        $this->addSql('DROP INDEX IDX_CE606404FB88E14F ON reclamation');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN utilisateur_id');
    }
} 