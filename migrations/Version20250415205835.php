<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250415205835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date DATE NOT NULL, localisation VARCHAR(255) NOT NULL, capacite INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reactions (id INT AUTO_INCREMENT NOT NULL, id_evenement INT DEFAULT NULL, id_joueur INT NOT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_38737FB38B13D439 (id_evenement), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation (id_reservation INT AUTO_INCREMENT NOT NULL, id_event INT DEFAULT NULL, id_utilisateur INT DEFAULT NULL, nombrepersonnes INT NOT NULL, qrcode_path VARCHAR(255) DEFAULT NULL, archived TINYINT(1) NOT NULL, date_reservation DATETIME NOT NULL, INDEX IDX_42C84955D52B4B97 (id_event), INDEX IDX_42C8495550EAE44 (id_utilisateur), PRIMARY KEY(id_reservation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reactions ADD CONSTRAINT FK_38737FB38B13D439 FOREIGN KEY (id_evenement) REFERENCES evenement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955D52B4B97 FOREIGN KEY (id_event) REFERENCES evenement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C8495550EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reactions DROP FOREIGN KEY FK_38737FB38B13D439
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955D52B4B97
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495550EAE44
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reactions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation
        SQL);
    }
}
