<?php

namespace App\Controller\Admin;

use App\Entity\Goal;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GoalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Goal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Цель')
            ->setEntityLabelInPlural('Цели');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('profile', 'Профиль');
        yield TextField::new('measurementType', 'Тип замера');
        yield NumberField::new('targetValue', 'Целевое значение');
        yield DateTimeField::new('targetDate', 'Целевая дата')->setFormat('yyyy-MM-dd');
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
