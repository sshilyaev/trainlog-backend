<?php

declare(strict_types=1);

namespace App\Enum;

enum ValidationMessage: string
{
    case TypeRequired = 'Укажите тип';
    case TypeCoachOrTrainee = 'Тип должен быть coach или trainee';
    case NameRequired = 'Укажите имя';
    case ProfileIdRequired = 'Укажите profileId';
    case ProfileIdUuid = 'profileId должен быть валидным UUID';
    case DateRequired = 'Укажите дату';
    case DateFormat = 'Дата в формате ГГГГ-ММ-ДД';
    case MeasurementTypeRequired = 'Укажите тип замера';
    case TargetValueNumeric = 'targetValue должен быть числом';
    case TargetDateRequired = 'Укажите целевую дату';
    case TargetDateFormat = 'Целевая дата в формате ГГГГ-ММ-ДД';
    case TokenRequired = 'Укажите токен';
    case TokenQueryRequired = 'Укажите параметр запроса token';
    case CoachProfileIdRequired = 'Укажите coachProfileId';
    case CoachProfileIdUuid = 'coachProfileId должен быть валидным UUID';
    case TraineeProfileIdRequired = 'Укажите traineeProfileId';
    case TraineeProfileIdUuid = 'traineeProfileId должен быть валидным UUID';
    case TotalSessionsRequired = 'Укажите totalSessions';
    case TotalSessionsAtLeast1 = 'totalSessions должен быть не меньше 1';
    case StatusActiveFinishedCancelled = 'Статус должен быть active, finished или cancelled';
    case StatusCancelledForVisit = 'Для отмены визита укажите status=cancelled';
    case MembershipIdUuid = 'membershipId должен быть валидным UUID';
    case AsRequired = 'Укажите as';
    case AsCoachOrTrainee = 'as должен быть coach или trainee';
    case MonthFormat = 'month в формате ГГГГ-ММ';

    case ProfileTypeRequired = 'Укажите тип профиля (coach или trainee)';
    case ProfileTypeCoachOrTrainee = 'Тип профиля должен быть coach или trainee';
    case GenderMaleOrFemale = 'Укажите пол: male или female';
    case TargetValueRequired = 'Укажите целевое значение';
}
