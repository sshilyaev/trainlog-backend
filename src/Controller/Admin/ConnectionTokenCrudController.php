<?php

namespace App\Controller\Admin;

use App\Entity\ConnectionToken;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConnectionTokenCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConnectionToken::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Код привязки')
            ->setEntityLabelInPlural('Коды привязки');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('traineeProfile', 'Подопечный');
        yield TextField::new('token', 'Код');
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
        yield DateTimeField::new('expiresAt', 'Истекает');
        yield BooleanField::new('used', 'Использован');
    }
}
