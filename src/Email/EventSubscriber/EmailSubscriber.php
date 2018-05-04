<?php

namespace App\Email\EventSubscriber;

use App\Email\Services\EmailTemplateSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\User\Event\ManuelResetPasswordEvent;

/**
 * Class EmailSubscriber
 *
 * @package App\Email\EventSubscriber
 */
class EmailSubscriber implements EventSubscriberInterface
{
    private $emailTemplateSender;

    /**
     * EmailSubscriber constructor.
     *
     * @param EmailTemplateSender $emailTemplateSender
     */
    public function __construct(EmailTemplateSender $emailTemplateSender)
    {
        $this->emailTemplateSender = $emailTemplateSender;
    }

    public static function getSubscribedEvents()
    {
        return [
            ManuelResetPasswordEvent::NAME => 'onManuelResetPassword',
        ];
    }

    /**
     * @param ManuelResetPasswordEvent $event
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function onManuelResetPassword(ManuelResetPasswordEvent $event)
    {
        $this->emailTemplateSender->sendEmail('manuel_reset_password', $event);
    }
}