<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408123000_add_supplement_dosage_units extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default_dosage_unit to supplement_catalog and dosage_unit to trainee_supplement_assignments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplement_catalog ADD default_dosage_unit VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE trainee_supplement_assignments ADD dosage_unit VARCHAR(32) DEFAULT NULL');

        // Seed defaults by supplement type; can be adjusted manually later per item.
        $this->addSql("UPDATE supplement_catalog SET default_dosage_unit = 'capsule' WHERE type = 'vitamin'");
        $this->addSql("UPDATE supplement_catalog SET default_dosage_unit = 'milligram' WHERE type = 'mineral'");
        $this->addSql("UPDATE supplement_catalog SET default_dosage_unit = 'gram' WHERE type = 'sports_nutrition'");
        $this->addSql("UPDATE supplement_catalog SET default_dosage_unit = 'serving' WHERE type = 'other'");

        // Common override: Omega-3 usually measured in capsules.
        $this->addSql("UPDATE supplement_catalog SET default_dosage_unit = 'capsule' WHERE name ILIKE '%Омега-3%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trainee_supplement_assignments DROP dosage_unit');
        $this->addSql('ALTER TABLE supplement_catalog DROP default_dosage_unit');
    }
}

