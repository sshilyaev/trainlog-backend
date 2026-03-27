<?php

namespace App\Controller\Admin;

use App\Entity\CoachTraineeLink;
use App\Entity\Calculator;
use App\Entity\CalculatorDefinition;
use App\Entity\ConnectionToken;
use App\Entity\Event;
use App\Entity\Goal;
use App\Entity\Measurement;
use App\Entity\Membership;
use App\Entity\Profile;
use App\Entity\RecordActivityCatalog;
use App\Entity\SupplementCatalog;
use App\Entity\Visit;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function index(): Response
    {
        $urlGenerator = $this->urlGenerator->setDashboard(self::class);

        $sections = [
            'Пользователи' => [
                ['label' => 'Профили', 'count' => $this->em->getRepository(Profile::class)->count([]), 'url' => $urlGenerator->setController(ProfileCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-user'],
            ],
            'Связи и абонементы' => [
                ['label' => 'Связи тренер–подопечный', 'count' => $this->em->getRepository(CoachTraineeLink::class)->count([]), 'url' => $urlGenerator->setController(CoachTraineeLinkCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-link'],
                ['label' => 'Абонементы', 'count' => $this->em->getRepository(Membership::class)->count([]), 'url' => $urlGenerator->setController(MembershipCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-id-card'],
                ['label' => 'Визиты', 'count' => $this->em->getRepository(Visit::class)->count([]), 'url' => $urlGenerator->setController(VisitCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-calendar-check'],
                ['label' => 'События', 'count' => $this->em->getRepository(Event::class)->count([]), 'url' => $urlGenerator->setController(EventCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-calendar-day'],
            ],
            'Данные' => [
                ['label' => 'Замеры', 'count' => $this->em->getRepository(Measurement::class)->count([]), 'url' => $urlGenerator->setController(MeasurementCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-ruler'],
                ['label' => 'Цели', 'count' => $this->em->getRepository(Goal::class)->count([]), 'url' => $urlGenerator->setController(GoalCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-bullseye'],
            ],
            'Калькуляторы' => [
                ['label' => 'Калькуляторы', 'count' => $this->em->getRepository(Calculator::class)->count([]), 'url' => $urlGenerator->setController(CalculatorCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-calculator'],
                ['label' => 'Определения', 'count' => $this->em->getRepository(CalculatorDefinition::class)->count([]), 'url' => $urlGenerator->setController(CalculatorDefinitionCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-code'],
            ],
            'Питание' => [
                ['label' => 'Каталог добавок', 'count' => $this->em->getRepository(SupplementCatalog::class)->count([]), 'url' => $urlGenerator->setController(SupplementCatalogCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-capsules'],
            ],
            'Достижения' => [
                ['label' => 'Каталог достижений', 'count' => $this->em->getRepository(RecordActivityCatalog::class)->count([]), 'url' => $urlGenerator->setController(RecordActivityCatalogCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-trophy'],
            ],
            'Привязка' => [
                ['label' => 'Коды привязки', 'count' => $this->em->getRepository(ConnectionToken::class)->count([]), 'url' => $urlGenerator->setController(ConnectionTokenCrudController::class)->setAction(Action::INDEX)->generateUrl(), 'icon' => 'fa-key'],
            ],
        ];

        return $this->render('admin/dashboard.html.twig', [
            'sections' => $sections,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('TrainLog — Панель управления');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Главная', 'fa fa-home');
        yield MenuItem::section('Пользователи');
        yield MenuItem::linkTo(ProfileCrudController::class, 'Профили', 'fa fa-user');
        yield MenuItem::section('Связи и абонементы');
        yield MenuItem::linkTo(CoachTraineeLinkCrudController::class, 'Связи тренер–подопечный', 'fa fa-link');
        yield MenuItem::linkTo(MembershipCrudController::class, 'Абонементы', 'fa fa-id-card');
        yield MenuItem::linkTo(VisitCrudController::class, 'Визиты', 'fa fa-calendar-check');
        yield MenuItem::linkTo(EventCrudController::class, 'События', 'fa fa-calendar-day');
        yield MenuItem::section('Данные');
        yield MenuItem::linkTo(MeasurementCrudController::class, 'Замеры', 'fa fa-ruler');
        yield MenuItem::linkTo(GoalCrudController::class, 'Цели', 'fa fa-bullseye');
        yield MenuItem::section('Калькуляторы');
        yield MenuItem::linkTo(CalculatorCrudController::class, 'Калькуляторы', 'fa fa-calculator');
        yield MenuItem::linkTo(CalculatorDefinitionCrudController::class, 'Определения', 'fa fa-code');
        yield MenuItem::section('Питание');
        yield MenuItem::linkTo(SupplementCatalogCrudController::class, 'Каталог добавок', 'fa fa-capsules');
        yield MenuItem::section('Достижения');
        yield MenuItem::linkTo(RecordActivityCatalogCrudController::class, 'Каталог достижений', 'fa fa-trophy');
        yield MenuItem::section('Привязка');
        yield MenuItem::linkTo(ConnectionTokenCrudController::class, 'Коды привязки', 'fa fa-key');
    }
}
