<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410121504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation_salle (id INT AUTO_INCREMENT NOT NULL, id_salle INT NOT NULL, date_debut VARCHAR(20) NOT NULL, date_fin VARCHAR(20) NOT NULL, statut ENUM('Confirmée', 'En attente', 'Annulée'), INDEX IDX_DE8AEFBA0123F6C (id_salle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE salle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(30) NOT NULL, capacite INT NOT NULL, email VARCHAR(40) NOT NULL, description VARCHAR(200) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_salle ADD CONSTRAINT FK_DE8AEFBA0123F6C FOREIGN KEY (id_salle) REFERENCES salle (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_salle DROP FOREIGN KEY FK_DE8AEFBA0123F6C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation_salle
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE salle
        SQL);
    }
}
