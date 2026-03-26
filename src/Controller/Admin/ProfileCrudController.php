<?php

namespace App\Controller\Admin;

use App\Entity\Profile;
use App\Repository\CoachTraineeLinkRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

class ProfileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CoachTraineeLinkRepository $coachTraineeLinkRepository,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Profile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Профиль')
            ->setEntityLabelInPlural('Профили');
    }

    public function configureActions(Actions $actions): Actions
    {
        $linksAction = Action::new('links', 'Все связи', 'fa fa-link')
            ->linkToCrudAction('links');
        return $actions
            ->add(Crud::PAGE_DETAIL, $linksAction);
    }

    #[AdminRoute(path: '{entityId}/links', name: 'links')]
    public function links(AdminContext $context): Response
    {
        $profile = $context->getEntity()->getInstance();
        if (!$profile instanceof Profile) {
            throw new \RuntimeException('Ожидается сущность Profile');
        }
        $links = $this->coachTraineeLinkRepository->findByProfileId($profile->getId());
        $backUrl = $this->adminUrlGenerator->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($profile->getId())
            ->generateUrl();
        $linkDetailUrls = [];
        $urlGen = $this->adminUrlGenerator->setController(CoachTraineeLinkCrudController::class)->setAction(Action::DETAIL);
        foreach ($links as $link) {
            $linkDetailUrls[$link->getId()] = $urlGen->setEntityId($link->getId())->generateUrl();
        }
        return $this->render('admin/profile_links.html.twig', [
            'profile' => $profile,
            'links' => $links,
            'linkDetailUrls' => $linkDetailUrls,
            'backUrl' => $backUrl,
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('userId', 'ID пользователя');
        yield ChoiceField::new('type', 'Тип')->setChoices([
            'Тренер' => Profile::TYPE_COACH,
            'Подопечный' => Profile::TYPE_TRAINEE,
        ]);
        yield BooleanField::new('developerMode', 'Dev-режим')->hideOnIndex();
        yield TextField::new('name', 'Имя');
        yield TextField::new('gymName', 'Зал')->hideOnIndex();
        yield ChoiceField::new('gender', 'Пол')->setChoices([
            'М' => Profile::GENDER_MALE,
            'Ж' => Profile::GENDER_FEMALE,
        ])->hideOnIndex();
        yield TextField::new('iconEmoji', 'Иконка (emoji)')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
    }
}
