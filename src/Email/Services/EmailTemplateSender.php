<?php

namespace App\Email\Services;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class EmailTemplateSender
 *
 * @package App\Email\Services
 */
class EmailTemplateSender
{
    private $container;

    private $em;
    private $mailer;
    private $router;

    /**
     * EmailTemplateSender constructor.
     *
     * @param ContainerInterface $container
     * @param \Swift_Mailer      $mailer
     * @param RouterInterface    $router
     */
    public function __construct(ContainerInterface $container, \Swift_Mailer $mailer, RouterInterface $router)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->mailer = $mailer;
        $this->router = $router;
    }

    /**
     * @param       $templateSlug
     * @param       $data
     * @param array $options
     *
     * @return bool
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendEmail($templateSlug, $data, $options = [])
    {
        $subject = $this->getSubject($templateSlug, $data);
        $from = $this->getFrom($templateSlug);
        $to = $this->getTo($templateSlug, $data);
        $body = $this->getBody($templateSlug, $data);

        if (!$to) {
            return false;
        }

        $message = $this->mailer->createMessage()
            ->setSubject($subject)
            ->setFrom([$from['email'] => $from['name']])
            ->setTo($to)
            ->setBody($body)
            ->setContentType('text/html');

        if (isset($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                $message->attach(\Swift_Attachment::fromPath($attachment));
            }
        }

        if ($this->mailer->send($message)) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    private function getConfig()
    {
        $ymlPath = $this->container->get('kernel')->getRootDir();
        $ymlPath .= '/Email/config/emails.yaml';

        $config = Yaml::parse(file_get_contents($ymlPath));

        return $config;
    }

    /**
     * @param $templateSlug
     *
     * @return bool
     */
    private function getTemplate($templateSlug)
    {
        $config = $this->getConfig();

        if (!isset($config['templates'][$templateSlug])) {
            throw new UnexpectedValueException(
                sprintf(
                    "Le template d'email \"%s\" n'existe pas.",
                    $templateSlug
                )
            );
        }

        return $config['templates'][$templateSlug];
    }

    /**
     * @param $templateSlug
     * @param $data
     *
     * @return mixed
     */
    private function getSubject($templateSlug, $data)
    {
        $template = $this->getTemplate($templateSlug);

        if (!isset($template['subject'])) {
            throw new UnexpectedValueException(
                sprintf(
                    "Le sujet du template d'email \"%s\" n'est pas défini.",
                    $templateSlug
                )
            );
        }

        $subject = $template['subject'];

        $subject = $this->replaceVariables($templateSlug, $data, $subject);

        return $subject;
    }

    /**
     * @param $templateSlug
     * @param $data
     *
     * @return mixed|string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getBody($templateSlug, $data)
    {
        $template = $this->getTemplate($templateSlug);

        if (!isset($template['view'])) {
            throw new UnexpectedValueException(
                sprintf(
                    "La vue du template d'email \"%s\" n'est pas défini.",
                    $templateSlug
                )
            );
        }

        $emailBody = $this->container->get('twig')->render($template['view'], ['data' => $data]);
        $emailBody = $this->replaceVariables($templateSlug, $data, $emailBody);

        return $emailBody;
    }

    /**
     * @param $templateSlug
     *
     * @return mixed
     */
    private function getFrom($templateSlug)
    {
        $from = $this->getDefaultFrom();

        $template = $this->getTemplate($templateSlug);

        if (isset($template['from']['name'])) {
            $from['name'] = $template['from']['name'];
        }

        if (isset($template['from']['email'])) {
            $from['email'] = $template['from']['email'];
        }

        return $from;
    }

    /**
     * @return mixed
     */
    private function getDefaultFrom()
    {
        $config = $this->getConfig();

        return $config['default']['from'];
    }

    /**
     * @param $templateSlug
     * @param $data
     *
     * @return string
     */
    private function getTo($templateSlug, $data)
    {
        $template = $this->getTemplate($templateSlug);

        if (!isset($template['to'])) {
            throw new UnexpectedValueException(
                sprintf(
                    "Le \"to\" du template d'email \"%s\" n'est pas défini.",
                    $templateSlug
                )
            );
        }

        $to = $this->getVariableValue($templateSlug, $template['to'], $data);

        return $to;
    }

    /**
     * @param $templateSlug
     * @param $data
     * @param $content
     *
     * @return mixed
     */
    private function replaceVariables($templateSlug, $data, $content)
    {
        $template = $this->getTemplate($templateSlug);

        if (isset($template['variables'])) {
            $variables = $template['variables'];

            foreach ($variables as $replaceSlug => $variable) {
                $value = '';

                switch ($variable['type']) {
                    case 'date':
                        $value = new \DateTime();

                        $value = $value->format($variable['dateFormat']);

                        break;

                    case 'getter':
                        $value = $this->getVariableValue($templateSlug, $variable['getter'], $data);

                        if (isset($variable['dateFormat'])) {
                            $value = $value->format($variable['dateFormat']);
                        }

                        break;

                    case 'route':
                        $value = $this->getVariableRoute($templateSlug, $variable, $data);
                        break;

                    case 'value':
                        $value = $variable['value'];
                        break;
                }

                $content = str_replace('{'.$replaceSlug.'}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * @param $templateSlug
     * @param $variable
     * @param $data
     *
     * @return mixed|string
     */
    private function getVariableValue($templateSlug, $variable, $data)
    {
        $template = $this->getTemplate($templateSlug);

        if (isset($template['object']) && !$data instanceof $template['object']) {
            $type = gettype($data);

            if ($type == 'object') {
                $type = get_class($data);
            }

            throw new UnexpectedValueException(
                sprintf(
                    "Le type de données du du template d'email \"%s\" envoyé est \"%s\", alors qu'il devrait être \"%s\".",
                    $templateSlug,
                    $type,
                    $template['object']
                )
            );
        }

        $value = '';

        $variableFields = explode('.', $variable);

        foreach ($variableFields as $variableField) {
            if ($value === null) {
                return '';
            }

            if ($value == '') {
                if (is_array($data)) {
                    $value = $data[$variableField];
                } else {
                    $value = $data->{'get'.ucfirst($variableField)}();
                }
            } else {
                if (preg_match('#^[0-9]*$#', $variableField)) {
                    $value = $value[$variableField];
                } else {
                    $value = $value->{'get'.ucfirst($variableField)}();
                }
            }
        }

        return $value;
    }

    /**
     * @param $templateSlug
     * @param $variable
     * @param $data
     *
     * @return string
     */
    private function getVariableRoute($templateSlug, $variable, $data)
    {
        $route = $variable['route'];

        $routeParameters = [];

        if (isset($variable['routeParameters'])) {
            foreach ($variable['routeParameters'] as $routeParameterKey => $routeParameterField) {
                $routeParameterValue = $this->getVariableValue($templateSlug, $routeParameterField, $data);

                $routeParameters[$routeParameterKey] = $routeParameterValue;
            }
        }

        return $this->router->generate(
            $route,
            $routeParameters,
            UrlGeneratorInterface::ABS_URL
        );
    }
}