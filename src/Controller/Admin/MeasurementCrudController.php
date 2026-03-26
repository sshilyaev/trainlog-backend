<?php

namespace App\Controller\Admin;

use App\Entity\Measurement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class MeasurementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Measurement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Замер')
            ->setEntityLabelInPlural('Замеры');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('profile', 'Профиль');
        yield DateTimeField::new('date', 'Дата')->setFormat('yyyy-MM-dd');
        yield NumberField::new('weight', 'Вес')->hideOnIndex();
        yield NumberField::new('height', 'Рост')->hideOnIndex();
        yield NumberField::new('neck', 'Шея')->hideOnIndex();
        yield NumberField::new('shoulders', 'Плечи')->hideOnIndex();
        yield NumberField::new('leftBiceps', 'Бицепс л.')->hideOnIndex();
        yield NumberField::new('rightBiceps', 'Бицепс пр.')->hideOnIndex();
        yield NumberField::new('waist', 'Талия')->hideOnIndex();
        yield NumberField::new('belly', 'Живот')->hideOnIndex();
        yield NumberField::new('leftThigh', 'Бедро л.')->hideOnIndex();
        yield NumberField::new('rightThigh', 'Бедро пр.')->hideOnIndex();
        yield NumberField::new('hips', 'Бёдра')->hideOnIndex();
        yield NumberField::new('buttocks', 'Ягодицы')->hideOnIndex();
        yield NumberField::new('leftCalf', 'Икра л.')->hideOnIndex();
        yield NumberField::new('rightCalf', 'Икра пр.')->hideOnIndex();
        yield TextareaField::new('note', 'Заметка')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
