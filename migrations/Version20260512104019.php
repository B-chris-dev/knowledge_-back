<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512104019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cursus (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, price NUMERIC(5, 2) NOT NULL, updated_by_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_255A0C3896DBBDE (updated_by_id), INDEX IDX_255A0C3B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cursus ADD CONSTRAINT FK_255A0C3896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cursus ADD CONSTRAINT FK_255A0C3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cursus DROP FOREIGN KEY FK_255A0C3896DBBDE');
        $this->addSql('ALTER TABLE cursus DROP FOREIGN KEY FK_255A0C3B03A8386');
        $this->addSql('DROP TABLE cursus');
    }
}
