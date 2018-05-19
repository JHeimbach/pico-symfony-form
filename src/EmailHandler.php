<?php
/**
 * Created by 5/17/18 1:53 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */

namespace PicoSymForm;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Twig\Environment;

class EmailHandler
{
    /**
     * @var array;
     */
    private $config;
    /**
     * @var Environment;
     */
    private $twig;
    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * EmailHandler constructor.
     * @param array $config
     */
    public function __construct(array $config, $twig)
    {
        $this->config = $config;
        $this->twig = $twig;

        $transport = (new Swift_SmtpTransport($config['smtp']['host']))
            ->setUsername($config['smtp']['username'])
            ->setPassword($config['smtp']['password']);

        $this->mailer = new Swift_Mailer($transport);
    }

    public function sendMail($recipients, $subject, $data, $template)
    {
        // Todo: check if mailer is ready
        $message = $this->createMessage($recipients, $subject, $data, $template);

        return $this->mailer->send($message);
    }

    /**
     * @param $recipients
     * @param $subject
     * @param $data
     * @param $template
     * @return Swift_Message
     */
    private function createMessage($recipients, $subject, $data, $template)
    {
        $message = (new Swift_Message($subject))
            ->setFrom($this->config['email']['sender'])
            ->setTo($recipients)
            ->setBody($this->twig->render($template . '.html.twig', ['data' => $data]), 'text/html')
            ->addPart($this->twig->render($template . '.txt.twig', ['data' => $data]), 'text/plain');

        return $message;
    }
}
