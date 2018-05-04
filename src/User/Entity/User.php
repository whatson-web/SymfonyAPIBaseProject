<?php

namespace App\User\Entity;

use App\User\Action\UserSendNewPassword;
use App\User\Action\UserMyPassword;
use App\User\Action\UserMyInformations;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 *
 * @ApiResource(
 *      itemOperations={
 *          "get",
 *          "put",
 *          "delete",
 *          "send-new-password"={
 *              "method"="GET",
 *              "path"="/users/{id}/send-new-password",
 *              "controller"=UserSendNewPassword::class
 *          },
 *          "my-informations"={
 *              "method"="POST",
 *              "path"="/users/{id}/my-informations",
 *              "controller"=UserMyInformations::class
 *          },
 *          "my-password"={
 *              "method"="POST",
 *              "path"="/users/{id}/my-password",
 *              "controller"=UserMyPassword::class
 *          }
 *      },
 *      attributes={
 *          "normalization_context"={"groups"={"user-read"}},
 *          "denormalization_context"={"groups"={"user-write"}}
 *      }
 * )
 *
 * @UniqueEntity(fields={"email"}, errorPath="email", message="Ce email est déjà utilisé")
 * @UniqueEntity(fields={"username"}, errorPath="username", message="Ce login est déjà utilisé")
 */
class User extends BaseUser
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->plainPassword = uniqid();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"user-read"})
     */
    protected $id;

    /**
     * @Groups({"user-read", "user-write"})
     *
     * @Assert\NotNull(message="Veuillez saisir un email")
     * @Assert\Email(message="Veuillez saisir un email valide")
     */
    protected $email;

    /**
     * @Groups({"user-write"})
     */
    protected $plainPassword;

    /**
     * @Groups({"user-read", "user-write"})
     *
     * @Assert\NotNull(message="Veuillez saisir un login")
     */
    protected $username;

    /**
     * @Groups({"user-read", "user-write"})
     */
    protected $enabled;

    /**
     * @Groups({"user-read", "user-write"})
     */
    protected $roles;

    public function isUser(?UserInterface $user = null): bool
    {
        return $user instanceof self && $user->id === $this->id;
    }
}
