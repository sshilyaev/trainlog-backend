<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Событие')
            ->setEntityLabelInPlural('События');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('coachProfile', 'Тренер');
        yield AssociationField::new('traineeProfile', 'Подопечный');
        yield TextField::new('title', 'Название');
        yield DateTimeField::new('date', 'Дата')->setFormat('yyyy-MM-dd');
        yield TextField::new('description', 'Описание')->hideOnIndex();
        yield BooleanField::new('remind', 'Напоминание');
        yield TextField::new('colorHex', 'Цвет (hex)')->hideOnIndex();
        yield BooleanField::new('isCancelled', 'Отменено');
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
