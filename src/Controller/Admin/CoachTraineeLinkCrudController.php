<?php

namespace App\Controller\Admin;

use App\Entity\CoachTraineeLink;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CoachTraineeLinkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CoachTraineeLink::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Связь тренер–подопечный')
            ->setEntityLabelInPlural('Связи тренер–подопечный');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('coachProfile', 'Тренер');
        yield AssociationField::new('traineeProfile', 'Подопечный');
        yield TextField::new('displayName', 'Отображаемое имя')->hideOnIndex();
        yield TextareaField::new('note', 'Заметка')->hideOnIndex();
        yield BooleanField::new('archived', 'В архиве');
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
