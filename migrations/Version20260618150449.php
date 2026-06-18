<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618150449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE app_user (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              email VARCHAR(180) NOT NULL,
              name VARCHAR(255) NOT NULL,
              roles CLOB NOT NULL,
              password VARCHAR(255) NOT NULL
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON app_user (email)');
        $this->addSql(<<<'SQL'
            CREATE TABLE oauth_access_token (
              identifier VARCHAR(80) NOT NULL,
              user_identifier VARCHAR(255) DEFAULT NULL,
              scopes CLOB NOT NULL,
              expiry_date_time DATETIME NOT NULL,
              revoked BOOLEAN NOT NULL,
              client_identifier VARCHAR(80) NOT NULL,
              PRIMARY KEY (identifier),
              CONSTRAINT FK_F7FA86A4E77ABE2B FOREIGN KEY (client_identifier) REFERENCES oauth_client (identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_F7FA86A4E77ABE2B ON oauth_access_token (client_identifier)');
        $this->addSql(<<<'SQL'
            CREATE TABLE oauth_authorization_code (
              identifier VARCHAR(80) NOT NULL,
              user_identifier VARCHAR(255) DEFAULT NULL,
              scopes CLOB NOT NULL,
              expiry_date_time DATETIME NOT NULL,
              redirect_uri VARCHAR(2000) DEFAULT NULL,
              revoked BOOLEAN NOT NULL,
              client_identifier VARCHAR(80) NOT NULL,
              PRIMARY KEY (identifier),
              CONSTRAINT FK_793B0817E77ABE2B FOREIGN KEY (client_identifier) REFERENCES oauth_client (identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_793B0817E77ABE2B ON oauth_authorization_code (client_identifier)');
        $this->addSql(<<<'SQL'
            CREATE TABLE oauth_client (
              identifier VARCHAR(80) NOT NULL,
              name VARCHAR(255) NOT NULL,
              secret VARCHAR(255) DEFAULT NULL,
              redirect_uris CLOB NOT NULL,
              grants CLOB NOT NULL,
              confidential BOOLEAN NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (identifier)
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE oauth_refresh_token (
              identifier VARCHAR(80) NOT NULL,
              expiry_date_time DATETIME NOT NULL,
              revoked BOOLEAN NOT NULL,
              access_token_identifier VARCHAR(80) NOT NULL,
              PRIMARY KEY (identifier),
              CONSTRAINT FK_55DCF7558E5675DC FOREIGN KEY (access_token_identifier) REFERENCES oauth_access_token (identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_55DCF7558E5675DC ON oauth_refresh_token (access_token_identifier)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE oauth_access_token');
        $this->addSql('DROP TABLE oauth_authorization_code');
        $this->addSql('DROP TABLE oauth_client');
        $this->addSql('DROP TABLE oauth_refresh_token');
    }
}
