<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915194512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_article ADD image_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_article ADD image_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_article ADD image_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_article ADD image_original_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE blog_article DROP image_name');
        $this->addSql('ALTER TABLE blog_article DROP image_size');
        $this->addSql('ALTER TABLE blog_article DROP image_mime_type');
        $this->addSql('ALTER TABLE blog_article DROP image_original_name');
    }
}
