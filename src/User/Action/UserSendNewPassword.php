<?php

namespace App\User\Action;

use App\User\Entity\User;
use App\User\Services\UserManager;

/**
 * Class UserSendNewPassword
 *
 * @package App\User\Action
 */
class UserSendNewPassword
{
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param User $data
     *
     * @return User
     */
    public function __invoke(User $data): User
    {
        $this->userManager->manuallyResetPassword($data);

        return $data;
    }
}