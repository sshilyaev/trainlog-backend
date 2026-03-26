<?php

declare(strict_types=1);

namespace App\Enum;

enum ApiError: string
{
    case MissingOrInvalidAuthHeader = 'missing_or_invalid_auth_header';
    case InvalidOrExpiredToken = 'invalid_or_expired_token';

    case ProfileNotFound = 'profile_not_found';
    case CoachProfileNotFound = 'coach_profile_not_found';
    case TraineeProfileNotFound = 'trainee_profile_not_found';
    case ProfileMustBeCoach = 'profile_must_be_coach';
    case ProfileMustBeTrainee = 'profile_must_be_trainee';

    case ProfileIdQueryRequired = 'profile_id_query_required';
    case ProfileIdRequired = 'profile_id_required';
    case TraineeProfileIdRequired = 'trainee_profile_id_required';
    case CoachOrTraineeProfileIdRequired = 'coach_or_trainee_profile_id_required';
    case CoachAndTraineeProfileIdRequired = 'coach_and_trainee_profile_id_required';
    case CalendarFromToRequired = 'calendar_from_to_required';
    case AsMustBeCoachOrTrainee = 'as_must_be_coach_or_trainee';

    case ValidationFailed = 'validation_failed';
    case InvalidDateFormat = 'invalid_date_format';
    case InvalidDateFormatShort = 'invalid_date_format_short';
    case InvalidTargetDateFormat = 'invalid_target_date_format';
    case InvalidTargetDateFormatCreate = 'invalid_target_date_format_create';

    case MeasurementNotFound = 'measurement_not_found';
    case PersonalRecordNotFound = 'personal_record_not_found';
    case RecordActivityNotFound = 'record_activity_not_found';
    case GoalNotFound = 'goal_not_found';
    case LinkNotFound = 'link_not_found';
    case MembershipNotFound = 'membership_not_found';
    case NutritionPlanNotFound = 'nutrition_plan_not_found';
    case SupplementNotFound = 'supplement_not_found';
    case SupplementAssignmentNotFound = 'supplement_assignment_not_found';
    case SupplementAssignmentForbidden = 'supplement_assignment_forbidden';
    case SupplementAssignmentDuplicate = 'supplement_assignment_duplicate';
    case VisitNotFound = 'visit_not_found';
    case EventNotFound = 'event_not_found';

    case TokenQueryRequired = 'token_query_required';
    case TokenAndCoachProfileIdRequired = 'token_and_coach_profile_id_required';
    case ConnectionTokenInvalidOrUsed = 'connection_token_invalid_or_used';
    case ConnectionTokenInvalidExpired = 'connection_token_invalid_expired';
    case TraineeAlreadyLinked = 'trainee_already_linked';

    case OnlyCoachCanUpdateLink = 'only_coach_can_update_link';
    case OnlyCoachCanUpdateMembership = 'only_coach_can_update_membership';
    case OnlyCoachCanUpdateNutritionPlan = 'only_coach_can_update_nutrition_plan';
    case OnlyCoachCanUpdateVisit = 'only_coach_can_update_visit';

    case TraineeCanUpdateOnlyWeightKg = 'trainee_can_update_only_weight_kg';
    case OnlyOwnerCanManageRecords = 'only_owner_can_manage_records';

    case CoachAndTraineeMustBeLinked = 'coach_and_trainee_must_be_linked';
    case TotalSessionsAtLeast1 = 'total_sessions_at_least_1';
    case NutritionPlanAlreadyExists = 'nutrition_plan_already_exists';
    case WeightRequired = 'weight_required';
    case InvalidFreezeDays = 'invalid_freeze_days';

    // --- Calculators ---
    case CalculatorNotFound = 'calculator_not_found';
    case CalculatorRequiredFieldMissing = 'required_field_missing';
    case CalculatorInvalidRange = 'invalid_range';
    case CalculatorInvalidType = 'invalid_type';
    case CalculatorInvalidDefinition = 'calculator_invalid_definition';

    case VisitsQueryParamsRequired = 'visits_query_params_required';
    case VisitsCreateParamsRequired = 'visits_create_params_required';
    case MembershipDoesNotMatchVisit = 'membership_does_not_match_visit';
    case MembershipNoRemainingSessions = 'membership_no_remaining_sessions';
    case VisitUpdateNoValidAction = 'visit_update_no_valid_action';
    case MergeProfileParamsRequired = 'merge_profile_params_required';
    case MergeProfileSameIds = 'merge_profile_same_ids';
    case MergeProfileManagedNotFound = 'merge_profile_managed_not_found';
    case MergeProfileRealNotFound = 'merge_profile_real_not_found';
    case MergeProfileOnlyCoach = 'merge_profile_only_coach';
    case MergeProfileAlreadyMerged = 'merge_profile_already_merged';

    public function message(): string
    {
        return match ($this) {
            self::MissingOrInvalidAuthHeader => 'Отсутствует или неверный заголовок Authorization',
            self::InvalidOrExpiredToken => 'Недействительный или просроченный токен',

            self::ProfileNotFound => 'Профиль не найден',
            self::CoachProfileNotFound => 'Профиль тренера не найден',
            self::TraineeProfileNotFound => 'Профиль подопечного не найден',
            self::ProfileMustBeCoach => 'Профиль должен быть типа «тренер»',
            self::ProfileMustBeTrainee => 'Профиль должен быть типа «подопечный»',

            self::ProfileIdQueryRequired => 'Укажите параметр запроса profileId',
            self::ProfileIdRequired => 'Укажите profileId',
            self::TraineeProfileIdRequired => 'Укажите traineeProfileId',
            self::CoachOrTraineeProfileIdRequired => 'Укажите coachProfileId или traineeProfileId',
            self::CoachAndTraineeProfileIdRequired => 'Укажите coachProfileId и traineeProfileId',
            self::CalendarFromToRequired => 'Укажите from и to (YYYY-MM-DD)',
            self::AsMustBeCoachOrTrainee => 'Параметр as должен быть «coach» или «trainee»',

            self::ValidationFailed => 'Ошибка валидации',
            self::InvalidDateFormat => 'Неверный формат даты (используйте ГГГГ-ММ-ДД)',
            self::InvalidDateFormatShort => 'Неверный формат даты',
            self::InvalidTargetDateFormat => 'Неверный формат даты цели',
            self::InvalidTargetDateFormatCreate => 'Неверный формат targetDate (используйте ГГГГ-ММ-ДД)',

            self::MeasurementNotFound => 'Замер не найден',
            self::PersonalRecordNotFound => 'Рекорд не найден',
            self::RecordActivityNotFound => 'Упражнение не найдено',
            self::GoalNotFound => 'Цель не найдена',
            self::LinkNotFound => 'Связь не найдена',
            self::MembershipNotFound => 'Абонемент не найден',
            self::NutritionPlanNotFound => 'План питания не найден',
            self::SupplementNotFound => 'Добавка не найдена или недоступна',
            self::SupplementAssignmentNotFound => 'Назначение добавки не найдено',
            self::SupplementAssignmentForbidden => 'Недостаточно прав для работы с назначением добавки',
            self::SupplementAssignmentDuplicate => 'Такая добавка уже назначена этому подопечному',
            self::VisitNotFound => 'Визит не найден',
            self::EventNotFound => 'Событие не найдено',

            self::TokenQueryRequired => 'Укажите параметр запроса token',
            self::TokenAndCoachProfileIdRequired => 'Укажите token и coachProfileId',
            self::ConnectionTokenInvalidOrUsed => 'Недействительный, просроченный или уже использованный код',
            self::ConnectionTokenInvalidExpired => 'Недействительный или просроченный код',
            self::TraineeAlreadyLinked => 'Этот подопечный уже привязан к вашему профилю тренера',

            self::OnlyCoachCanUpdateLink => 'Изменять связь может только тренер',
            self::OnlyCoachCanUpdateMembership => 'Изменять абонемент может только тренер',
            self::OnlyCoachCanUpdateNutritionPlan => 'Изменять план питания может только тренер, который его создал',
            self::OnlyCoachCanUpdateVisit => 'Изменять визит может только тренер',
            self::TraineeCanUpdateOnlyWeightKg => 'Подопечный может менять только weightKg в nutrition plan',
            self::OnlyOwnerCanManageRecords => 'Только владелец профиля может изменять рекорды',

            self::CoachAndTraineeMustBeLinked => 'Сначала привяжите тренера и подопечного',
            self::TotalSessionsAtLeast1 => 'totalSessions должен быть не меньше 1',
            self::NutritionPlanAlreadyExists => 'План питания для этой пары тренер-подопечный уже существует',
            self::WeightRequired => 'Для расчёта питания нужно указать вес подопечного.',
            self::InvalidFreezeDays => 'freezeDays должен быть больше или равен 0',

            self::CalculatorNotFound => 'Калькулятор не найден',
            self::CalculatorRequiredFieldMissing => 'Обязательные поля заполнены не полностью',
            self::CalculatorInvalidRange => 'Некорректное значение',
            self::CalculatorInvalidType => 'Некорректный тип значения',
            self::CalculatorInvalidDefinition => 'Некорректная конфигурация калькулятора',

            self::VisitsQueryParamsRequired => 'Укажите coachProfileId и traineeProfileId или только traineeProfileId',
            self::VisitsCreateParamsRequired => 'Укажите coachProfileId, traineeProfileId и date',
            self::MembershipDoesNotMatchVisit => 'Абонемент не относится к этому визиту',
            self::MembershipNoRemainingSessions => 'В абонементе не осталось занятий',
            self::VisitUpdateNoValidAction => 'Допустимые изменения: status=cancelled или membershipId для погашения долга',
            self::MergeProfileParamsRequired => 'Укажите coachProfileId, managedTraineeProfileId и realTraineeProfileId',
            self::MergeProfileSameIds => 'managedTraineeProfileId и realTraineeProfileId не должны совпадать',
            self::MergeProfileManagedNotFound => 'Управляемый профиль не найден',
            self::MergeProfileRealNotFound => 'Целевой профиль не найден',
            self::MergeProfileOnlyCoach => 'Слияние может выполнить только владелец профиля тренера',
            self::MergeProfileAlreadyMerged => 'Этот профиль уже объединён с другим',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::MissingOrInvalidAuthHeader,
            self::InvalidOrExpiredToken => 401,

            self::ProfileNotFound,
            self::CoachProfileNotFound,
            self::TraineeProfileNotFound,
            self::MeasurementNotFound,
            self::PersonalRecordNotFound,
            self::RecordActivityNotFound,
            self::GoalNotFound,
            self::LinkNotFound,
            self::MembershipNotFound,
            self::NutritionPlanNotFound,
            self::SupplementNotFound,
            self::SupplementAssignmentNotFound,
            self::VisitNotFound,
            self::EventNotFound,
            self::ConnectionTokenInvalidExpired => 404,

            self::OnlyCoachCanUpdateLink,
            self::OnlyCoachCanUpdateMembership,
            self::OnlyCoachCanUpdateNutritionPlan,
            self::OnlyCoachCanUpdateVisit,
            self::TraineeCanUpdateOnlyWeightKg,
            self::OnlyOwnerCanManageRecords,
            self::SupplementAssignmentForbidden,
            self::MergeProfileOnlyCoach => 403,

            self::TraineeAlreadyLinked => 409,
            self::NutritionPlanAlreadyExists => 409,
            self::SupplementAssignmentDuplicate => 409,

            self::WeightRequired,
            self::InvalidFreezeDays => 422,

            self::CalculatorRequiredFieldMissing,
            self::CalculatorInvalidRange,
            self::CalculatorInvalidType => 422,

            self::CalculatorNotFound => 404,

            self::CalculatorInvalidDefinition => 500,

            default => 400,
        };
    }
}
