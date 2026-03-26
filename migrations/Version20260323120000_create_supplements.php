<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000_create_supplements extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create supplement catalog and trainee supplement assignments + seed 50 catalog entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE supplement_catalog (
                id VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(32) NOT NULL,
                description TEXT NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                sort_order INT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql("CREATE INDEX idx_supplement_catalog_type ON supplement_catalog (type)");
        $this->addSql("CREATE INDEX idx_supplement_catalog_active ON supplement_catalog (is_active)");
        $this->addSql("CREATE INDEX idx_supplement_catalog_sort ON supplement_catalog (sort_order)");

        $this->addSql('
            CREATE TABLE trainee_supplement_assignments (
                id VARCHAR(36) NOT NULL,
                coach_profile_id VARCHAR(36) NOT NULL,
                trainee_profile_id VARCHAR(36) NOT NULL,
                supplement_id VARCHAR(36) NOT NULL,
                dosage VARCHAR(255) DEFAULT NULL,
                timing VARCHAR(255) DEFAULT NULL,
                frequency VARCHAR(255) DEFAULT NULL,
                note VARCHAR(1000) DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql("CREATE INDEX idx_trainee_supplements_coach ON trainee_supplement_assignments (coach_profile_id)");
        $this->addSql("CREATE INDEX idx_trainee_supplements_trainee ON trainee_supplement_assignments (trainee_profile_id)");
        $this->addSql("CREATE INDEX idx_trainee_supplements_supplement ON trainee_supplement_assignments (supplement_id)");
        $this->addSql("CREATE UNIQUE INDEX uq_trainee_supplements_coach_trainee_supplement ON trainee_supplement_assignments (coach_profile_id, trainee_profile_id, supplement_id)");

        $this->addSql('ALTER TABLE trainee_supplement_assignments ADD CONSTRAINT FK_trainee_supplements_coach FOREIGN KEY (coach_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE trainee_supplement_assignments ADD CONSTRAINT FK_trainee_supplements_trainee FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE trainee_supplement_assignments ADD CONSTRAINT FK_trainee_supplements_catalog FOREIGN KEY (supplement_id) REFERENCES supplement_catalog (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');

        $seed = $this->buildCatalogSeed();
        foreach ($seed as $item) {
            $id = $item['id'];
            $name = str_replace("'", "''", $item['name']);
            $type = $item['type'];
            $description = str_replace("'", "''", $item['description']);
            $sort = $item['sort'];
            $this->addSql("INSERT INTO supplement_catalog (id, name, type, description, is_active, sort_order, created_at, updated_at) VALUES ('{$id}', '{$name}', '{$type}', '{$description}', true, {$sort}, NOW(), NOW())");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trainee_supplement_assignments DROP CONSTRAINT FK_trainee_supplements_coach');
        $this->addSql('ALTER TABLE trainee_supplement_assignments DROP CONSTRAINT FK_trainee_supplements_trainee');
        $this->addSql('ALTER TABLE trainee_supplement_assignments DROP CONSTRAINT FK_trainee_supplements_catalog');
        $this->addSql('DROP TABLE trainee_supplement_assignments');
        $this->addSql('DROP TABLE supplement_catalog');
    }

    /**
     * @return list<array{id: string, name: string, type: string, description: string, sort: int}>
     */
    private function buildCatalogSeed(): array
    {
        $items = [
            ['Витамин D3', 'vitamin', 'Поддержка иммунитета и костной ткани.'],
            ['Витамин C', 'vitamin', 'Антиоксидант, поддержка иммунной системы.'],
            ['Витамин B12', 'vitamin', 'Поддержка нервной системы и кроветворения.'],
            ['Витамин B6', 'vitamin', 'Участвует в обмене аминокислот и белка.'],
            ['Витамин B1', 'vitamin', 'Поддержка углеводного обмена и нервной системы.'],
            ['Витамин B2', 'vitamin', 'Участвует в энергетическом обмене.'],
            ['Витамин B3 (ниацин)', 'vitamin', 'Поддержка энергетического обмена и кожи.'],
            ['Витамин B9 (фолат)', 'vitamin', 'Поддержка клеточного деления и кроветворения.'],
            ['Витамин A', 'vitamin', 'Поддержка зрения и эпителия.'],
            ['Витамин E', 'vitamin', 'Антиоксидантная защита клеток.'],
            ['Витамин K2', 'vitamin', 'Поддержка метаболизма кальция.'],
            ['Мультивитаминный комплекс', 'vitamin', 'Комплекс базовых витаминов для ежедневной поддержки.'],
            ['Магний цитрат', 'mineral', 'Поддержка нервно-мышечной функции и сна.'],
            ['Цинк', 'mineral', 'Поддержка иммунитета и восстановления.'],
            ['Железо', 'mineral', 'Поддержка переноса кислорода и энергии.'],
            ['Кальций', 'mineral', 'Поддержка костной ткани и мышц.'],
            ['Калий', 'mineral', 'Поддержка водно-электролитного баланса.'],
            ['Селен', 'mineral', 'Антиоксидантная поддержка и щитовидная железа.'],
            ['Йод', 'mineral', 'Поддержка функции щитовидной железы.'],
            ['Медь', 'mineral', 'Участие в ферментативных реакциях.'],
            ['Марганец', 'mineral', 'Поддержка метаболизма и костной ткани.'],
            ['Хром', 'mineral', 'Поддержка углеводного обмена.'],
            ['Фосфор', 'mineral', 'Поддержка энергетического обмена и костей.'],
            ['Натрий + электролиты', 'mineral', 'Поддержка гидратации при нагрузках.'],
            ['Сывороточный протеин', 'sports_nutrition', 'Быстрый белковый источник после тренировки.'],
            ['Казеин', 'sports_nutrition', 'Медленный белок для длительного насыщения аминокислотами.'],
            ['Изолят сывороточного белка', 'sports_nutrition', 'Высокая доля белка, минимум углеводов и жиров.'],
            ['Гейнер', 'sports_nutrition', 'Белково-углеводная смесь для набора массы.'],
            ['Креатин моногидрат', 'sports_nutrition', 'Поддержка силовых показателей и работоспособности.'],
            ['BCAA', 'sports_nutrition', 'Аминокислоты с разветвленной цепью.'],
            ['EAA', 'sports_nutrition', 'Незаменимые аминокислоты для восстановления.'],
            ['L-глютамин', 'sports_nutrition', 'Поддержка восстановления при высоких нагрузках.'],
            ['Бета-аланин', 'sports_nutrition', 'Поддержка буферной емкости при интенсивной работе.'],
            ['L-карнитин', 'sports_nutrition', 'Поддержка транспорта жирных кислот.'],
            ['Предтренировочный комплекс', 'sports_nutrition', 'Комбинация ингредиентов перед тренировкой.'],
            ['Омега-3', 'sports_nutrition', 'Поддержка сердечно-сосудистой системы и восстановления.'],
            ['Изотоник', 'sports_nutrition', 'Восполнение жидкости и электролитов во время нагрузки.'],
            ['Энергетический гель', 'sports_nutrition', 'Быстрый источник углеводов на тренировке.'],
            ['Коллаген', 'sports_nutrition', 'Поддержка соединительной ткани и суставов.'],
            ['Протеиновый батончик', 'sports_nutrition', 'Удобный перекус с повышенным содержанием белка.'],
            ['Пробиотики', 'other', 'Поддержка микробиоты кишечника.'],
            ['Пребиотики', 'other', 'Пищевые волокна для поддержки микробиоты.'],
            ['Коэнзим Q10', 'other', 'Поддержка энергетического обмена клеток.'],
            ['Ашваганда', 'other', 'Адаптоген для поддержки стрессоустойчивости.'],
            ['Мелатонин', 'other', 'Поддержка засыпания и циркадного ритма.'],
            ['Глицин', 'other', 'Поддержка расслабления и восстановления сна.'],
            ['Таурин', 'other', 'Поддержка работы нервной системы и выносливости.'],
            ['Куркумин', 'other', 'Компонент с антиоксидантной активностью.'],
            ['Экстракт зелёного чая', 'other', 'Источник полифенолов и антиоксидантов.'],
            ['Комплекс для суставов', 'other', 'Комбинация компонентов для поддержки суставов.'],
        ];

        $result = [];
        $i = 1;
        foreach ($items as [$name, $type, $description]) {
            $result[] = [
                'id' => $this->uuidFromInt($i),
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'sort' => $i,
            ];
            $i++;
        }

        return $result;
    }

    private function uuidFromInt(int $i): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $i);
    }
}

