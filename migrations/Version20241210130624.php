<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210130624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE promotion ADD name VARCHAR(50) NOT NULL, ADD description VARCHAR(255) NOT NULL, ADD discount_type VARCHAR(10) NOT NULL, ADD start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD is_active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE promotion DROP name, DROP description, DROP discount_type, DROP start_date, DROP end_date, DROP is_active');
    }
}
