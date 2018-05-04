<?php

namespace App\User\Services;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\User\Event\ManuelResetPasswordEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UserManager
 *
 * @package App\User\Services
 */
class UserManager
{
    private $em;
    private $eventDispatcher;

    /**
     * UserManager constructor.
     *
     * @param EntityManagerInterface   $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param User $user
     */
    public function manuallyResetPassword(User $user)
    {
        $newPlainPassword = $this->generatePassword(8);

        $user->setPlainPassword($newPlainPassword);

        $this->em->persist($user);
        $this->em->flush();

        $event = new ManuelResetPasswordEvent($user, $newPlainPassword);

        $this->eventDispatcher->dispatch($event::NAME, $event);
    }

    /**
     * @param $size
     *
     * @return string
     */
    public function generatePassword($size)
    {
        $src = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'J',
            'K',
            'L',
            'M',
            'N',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'X',
            'Y',
            'Z',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
        ];

        $password = '';

        for ($i = 0; $i < $size; $i++) {
            $password .= $src[rand(0, 30)];
        }

        return $password;
    }
}