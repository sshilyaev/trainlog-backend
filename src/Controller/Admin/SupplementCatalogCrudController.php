<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SupplementCatalog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class SupplementCatalogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SupplementCatalog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Добавка')
            ->setEntityLabelInPlural('Каталог добавок');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Название')->setRequired(true);
        yield ChoiceField::new('type', 'Тип')
            ->setChoices([
                'Витамин' => SupplementCatalog::TYPE_VITAMIN,
                'Минерал' => SupplementCatalog::TYPE_MINERAL,
                'Спортпит' => SupplementCatalog::TYPE_SPORTS_NUTRITION,
                'Другое' => SupplementCatalog::TYPE_OTHER,
            ])
            ->setRequired(true);
        yield TextareaField::new('description', 'Описание')->setRequired(true);
        yield BooleanField::new('isActive', 'Активна');
        yield IntegerField::new('sortOrder', 'Порядок');
        yield DateTimeField::new('createdAt', 'Создана')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Обновлена')->hideOnForm();
    }
}

