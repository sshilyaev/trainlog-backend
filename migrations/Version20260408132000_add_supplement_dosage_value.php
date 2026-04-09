<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408132000_add_supplement_dosage_value extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dosage_value to trainee_supplement_assignments and support iu dosage unit in catalog defaults';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trainee_supplement_assignments ADD dosage_value VARCHAR(64) DEFAULT NULL');

        // For records where dosage was filled as plain number-like value, keep it in dedicated field.
        $this->addSql("
            UPDATE trainee_supplement_assignments
            SET dosage_value = dosage
            WHERE dosage IS NOT NULL
              AND trim(dosage) ~ '^[0-9]+([\\.,][0-9]+)?$'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trainee_supplement_assignments DROP dosage_value');
    }
}

