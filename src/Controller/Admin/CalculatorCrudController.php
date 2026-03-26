<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Calculator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CalculatorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Calculator::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Калькулятор')
            ->setEntityLabelInPlural('Калькуляторы');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('slug', 'Slug')->setRequired(true);
        yield TextField::new('title', 'Название')->setRequired(true);
        yield TextareaField::new('description', 'Описание');

        // Order in catalog (entity property is `order` mapped to DB column sort_order)
        yield NumberField::new('order', 'Порядок')->setRequired(true);
        yield BooleanField::new('isEnabled', 'Включен');
        yield NumberField::new('version', 'Версия');

        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Обновлён')->hideOnForm();
    }
}

