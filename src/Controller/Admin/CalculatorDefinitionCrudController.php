<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CalculatorDefinition;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CalculatorDefinitionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CalculatorDefinition::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Определение калькулятора')
            ->setEntityLabelInPlural('Определения калькуляторов');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('calculatorId', 'Calculator ID')
            ->hideOnForm()
            ->hideWhenCreating();
        yield TextField::new('calculatorId', 'Calculator ID')
            ->onlyWhenCreating()
            ->setRequired(true)
            ->setHelp('Должен совпадать с существующим calculators.id, например: bmi, water_balance');
        yield NumberField::new('version', 'Версия');
        yield TextareaField::new('definitionJson', 'JSON definition')
            ->setRequired(true)
            ->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Обновлён')->hideOnForm();
    }
}

