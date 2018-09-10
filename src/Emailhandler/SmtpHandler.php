<?php
/**
 * Created 09.09.18 18:52
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */

namespace PicoSymfonyForm\Emailhandler;


use Swift_Mailer;
use Swift_SmtpTransport;

class SmtpHandler extends EmailHandler
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;


    public function sendMail($recipients, $subject, $data, $template)
    {
        $this->createSender();
        // Todo: check if mailer is ready
        $message = $this->createMessage($recipients, $subject, $data, $template);

        return $this->mailer->send($message);
    }


    protected function createSender()
    {
        $transport = (new Swift_SmtpTransport($this->config['smtp']['host'], $this->config['smtp']['port']))
            ->setUsername($this->config['smtp']['username'])
            ->setPassword($this->config['smtp']['password'])
            ->setEncryption($this->config['smtp']['security']);

        $this->mailer = new Swift_Mailer($transport);

        return $this->mailer;
    }
}