<?php

namespace App\User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 *
 * @package App\User\Controller
 */
class UserController extends Controller
{
    /**
     * @Route(name="api_user_me", path="/api/me")
     *
     * @return mixed
     */
    public function meAction()
    {
        $user = $this->getUser();

        return new JsonResponse($this->get('serializer')->serialize($user, 'jsonld'), 200, [], true);
    }
}