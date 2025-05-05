<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505102016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE commande_produit DROP FOREIGN KEY fk_commande_produit_commande
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande_produit DROP FOREIGN KEY fk_commande_produit_produit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier DROP FOREIGN KEY fk_panier_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier_produit DROP FOREIGN KEY fk_panier_produit_panier
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier_produit DROP FOREIGN KEY fk_panier_produit_produit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commande_produit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE panier
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE panier_produit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY fk_commande_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY fk_commande_produit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY fk_commande_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande CHANGE produit_id produit_id INT NOT NULL, CHANGE montant montant NUMERIC(10, 2) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_commande_utilisateur ON commande
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6EEAA67DFB88E14F ON commande (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_commande_produit ON commande
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6EEAA67DF347EFB ON commande (produit_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT fk_commande_produit FOREIGN KEY (produit_id) REFERENCES produit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit DROP FOREIGN KEY FK_produit_categorie
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit CHANGE description description LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_produit_categorie ON produit
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit ADD CONSTRAINT FK_produit_categorie FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD utilisateur_id INT NOT NULL, DROP utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE606404FB88E14F ON reclamation (utilisateur_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE commande_produit (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, INDEX fk_commande_produit_commande (commande_id), INDEX fk_commande_produit_produit (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, date_creation DATETIME NOT NULL, INDEX fk_panier_utilisateur (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE panier_produit (id INT AUTO_INCREMENT NOT NULL, panier_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, INDEX fk_panier_produit_panier (panier_id), INDEX fk_panier_produit_produit (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande_produit ADD CONSTRAINT fk_commande_produit_commande FOREIGN KEY (commande_id) REFERENCES commande (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande_produit ADD CONSTRAINT fk_commande_produit_produit FOREIGN KEY (produit_id) REFERENCES produit (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier ADD CONSTRAINT fk_panier_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier_produit ADD CONSTRAINT fk_panier_produit_panier FOREIGN KEY (panier_id) REFERENCES panier (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE panier_produit ADD CONSTRAINT fk_panier_produit_produit FOREIGN KEY (produit_id) REFERENCES produit (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande CHANGE produit_id produit_id INT DEFAULT NULL, CHANGE montant montant NUMERIC(10, 0) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_6eeaa67dfb88e14f ON commande
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_commande_utilisateur ON commande (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_6eeaa67df347efb ON commande
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_commande_produit ON commande (produit_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit CHANGE description description TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_29a5ec27bcf5e72d ON produit
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX FK_produit_categorie ON produit (categorie_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CE606404FB88E14F ON reclamation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD utilisateur VARCHAR(255) DEFAULT NULL, DROP utilisateur_id
        SQL);
    }
}
