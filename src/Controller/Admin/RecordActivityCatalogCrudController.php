<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\RecordActivityCatalog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RecordActivityCatalogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RecordActivityCatalog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Активность достижения')
            ->setEntityLabelInPlural('Каталог достижений');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('slug', 'Slug')->setRequired(true);
        yield TextField::new('name', 'Название')->setRequired(true);
        yield ChoiceField::new('activityType', 'Подкатегория')
            ->setChoices([
                'Силовые' => 'силовые',
                'Гимнастика' => 'гимнастика',
                'Кардио' => 'кардио',
                'Выносливость' => 'выносливость',
                'Функциональные' => 'функциональные',
                'Мобильность' => 'мобильность',
                'Баланс' => 'баланс',
                'Взрывная сила' => 'взрывная_сила',
            ])
            ->setRequired(false);
        yield ChoiceField::new('defaultMetrics', 'Метрики по умолчанию')
            ->setChoices([
                'Вес' => 'weight',
                'Повторения' => 'reps',
                'Длительность' => 'duration',
                'Скорость' => 'speed',
                'Дистанция' => 'distance',
                'Другое' => 'other',
            ])
            ->allowMultipleChoices()
            ->renderExpanded(false);
        yield IntegerField::new('displayOrder', 'Порядок');
        yield BooleanField::new('isActive', 'Активна');
        yield DateTimeField::new('createdAt', 'Создана')->hideOnForm();
    }
}
