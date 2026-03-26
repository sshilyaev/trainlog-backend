<?php

namespace App\Controller\Admin;

use App\Entity\Visit;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VisitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Visit::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Визит')
            ->setEntityLabelInPlural('Визиты');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('coachProfile', 'Тренер');
        yield AssociationField::new('traineeProfile', 'Подопечный');
        yield DateTimeField::new('date', 'Дата')->setFormat('yyyy-MM-dd');
        yield ChoiceField::new('status', 'Статус')->setChoices([
            'Запланирован' => Visit::STATUS_PLANNED,
            'Проведён' => Visit::STATUS_DONE,
            'Отменён' => Visit::STATUS_CANCELLED,
            'Неявка' => Visit::STATUS_NO_SHOW,
        ]);
        yield ChoiceField::new('paymentStatus', 'Оплата')->setChoices([
            'Не оплачен' => Visit::PAYMENT_UNPAID,
            'Оплачен' => Visit::PAYMENT_PAID,
            'Долг' => Visit::PAYMENT_DEBT,
        ]);
        yield AssociationField::new('membership', 'Абонемент')->hideOnIndex();
        yield TextField::new('membershipDisplayCode', 'Код абонемента')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
