<?php

namespace App\User\Action;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserMyPassword
 *
 * @package App\User\Action
 */
class UserMyPassword
{
    private $em;
    private $validator;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
    }

    /**
     * @param User $data
     *
     * @return User
     */
    public function __invoke(User $data): User
    {
        $violations = $this->validator->validate($data);

        if ($violations->count() === 0) {
            $this->em->persist($data);
            $this->em->flush();

            return $data;
        } else {
            throw new ValidationException($violations);
        }
    }
}