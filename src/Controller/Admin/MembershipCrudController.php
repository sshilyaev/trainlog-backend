<?php

namespace App\Controller\Admin;

use App\Entity\Membership;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MembershipCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Membership::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Абонемент')
            ->setEntityLabelInPlural('Абонементы');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('coachProfile', 'Тренер');
        yield AssociationField::new('traineeProfile', 'Подопечный');
        yield IntegerField::new('totalSessions', 'Всего занятий');
        yield IntegerField::new('usedSessions', 'Использовано')->hideOnForm();
        yield IntegerField::new('priceRub', 'Цена (₽)')->hideOnIndex();
        yield ChoiceField::new('status', 'Статус')->setChoices([
            'Активный' => Membership::STATUS_ACTIVE,
            'Завершён' => Membership::STATUS_FINISHED,
            'Отменён' => Membership::STATUS_CANCELLED,
        ]);
        yield TextField::new('displayCode', 'Код')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
