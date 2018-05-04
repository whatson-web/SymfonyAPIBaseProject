<?php

declare(strict_types=1);

namespace App\User\Event;

use App\User\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ManuelResetPasswordEvent
 *
 * @package App\User\Event
 */
class ManuelResetPasswordEvent extends Event
{
    const NAME = 'user.manual_reset_password';

    private $user;
    private $newPlainPassword;

    /**
     * ManuelResetPasswordEvent constructor.
     *
     * @param User   $user
     * @param string $newPlainPassword
     */
    public function __construct(User $user, string $newPlainPassword)
    {
        $this->user = $user;
        $this->newPlainPassword = $newPlainPassword;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getNewPlainPassword()
    {
        return $this->newPlainPassword;
    }
}