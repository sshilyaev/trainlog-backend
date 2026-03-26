<?php

declare(strict_types=1);

namespace App\Http\Request\Supplement;

use App\Entity\SupplementCatalog;
use Symfony\Component\Validator\Constraints as Assert;

final class ListSupplementCatalogRequest
{
    public function __construct(
        #[Assert\Choice(
            choices: [
                SupplementCatalog::TYPE_VITAMIN,
                SupplementCatalog::TYPE_MINERAL,
                SupplementCatalog::TYPE_SPORTS_NUTRITION,
                SupplementCatalog::TYPE_OTHER,
            ],
            message: 'type должен быть vitamin, mineral, sports_nutrition или other'
        )]
        public ?string $type = null,
    ) {
    }
}

