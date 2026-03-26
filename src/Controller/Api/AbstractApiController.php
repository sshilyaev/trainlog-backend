<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\EventSubscriber\FirebaseAuthSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractApiController extends AbstractController
{
    protected function getUserId(Request $request): string
    {
        $uid = $request->attributes->get(FirebaseAuthSubscriber::FIREBASE_UID_ATTRIBUTE);
        if (!\is_string($uid)) {
            throw new \RuntimeException('Firebase UID not set');
        }
        return $uid;
    }
}
