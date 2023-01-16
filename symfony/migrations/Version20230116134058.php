<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230116134058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, body LONGTEXT NOT NULL, creation_date DATETIME NOT NULL, categories_id INT NOT NULL, INDEX IDX_B6BD307FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), INDEX IDX_75EA56E0FB7336F0 (queue_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B10DAF24A');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B5278319C');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B10DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id)');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5E5278319C');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5EF92F3E70');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5E5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5EF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE episode CHANGE season_id season_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE external_rating CHANGE series_id series_id INT DEFAULT NULL, CHANGE source_id source_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D5278319C');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D4296D31F');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D4296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('ALTER TABLE season CHANGE series_id series_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP suspend, CHANGE admin admin TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0362B62A0');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0A76ED395');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id)');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A105278319C');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A10A76ED395');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A105278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A10A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA76ED395');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE external_rating CHANGE source_id source_id INT NOT NULL, CHANGE series_id series_id INT NOT NULL');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5EF92F3E70');
        $this->addSql('ALTER TABLE country_series DROP FOREIGN KEY FK_7A68EA5E5278319C');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5EF92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE country_series ADD CONSTRAINT FK_7A68EA5E5278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A10A76ED395');
        $this->addSql('ALTER TABLE user_series DROP FOREIGN KEY FK_5F421A105278319C');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A10A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_series ADD CONSTRAINT FK_5F421A105278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD suspend TINYINT(1) DEFAULT 0 NOT NULL, CHANGE admin admin TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0A76ED395');
        $this->addSql('ALTER TABLE user_episode DROP FOREIGN KEY FK_85A702D0362B62A0');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_episode ADD CONSTRAINT FK_85A702D0362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode CHANGE season_id season_id INT NOT NULL');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B10DAF24A');
        $this->addSql('ALTER TABLE actor_series DROP FOREIGN KEY FK_CD56D29B5278319C');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B10DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE actor_series ADD CONSTRAINT FK_CD56D29B5278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE season CHANGE series_id series_id INT NOT NULL');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D4296D31F');
        $this->addSql('ALTER TABLE genre_series DROP FOREIGN KEY FK_D1A3310D5278319C');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D4296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE genre_series ADD CONSTRAINT FK_D1A3310D5278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
    }
}
