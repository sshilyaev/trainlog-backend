<?php

declare(strict_types=1);

namespace App\Http\Request\Membership;

use Symfony\Component\Validator\Constraints as Assert;

final class ListMembershipsRequest
{
    public function __construct(
        public ?string $coachProfileId = null,
        public ?string $traineeProfileId = null,
    ) {
    }
}
