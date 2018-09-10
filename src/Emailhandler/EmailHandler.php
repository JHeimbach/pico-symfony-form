<?php
/**
 * Created by 5/17/18 1:53 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */

namespace PicoSymfonyForm\Emailhandler;

use ReflectionClass;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class EmailHandler
{
    /**
     * @var array;
     */
    protected $config;
    /**
     * @var Environment;
     */
    private $twig;

    /**
     * EmailHandler constructor.
     * @param array $config
     * @throws \ReflectionException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $view_dir = $this->config['view_dir'];

        if(array_key_exists('view_dir',$this->config['email'])) {
            $view_dir = $this->config['email']['view_dir'];
        }

        $this->twig = $this->getTwig($view_dir);
        $this->twig->addExtension(new TranslationExtension($this->getTranslator()));
    }

    /**
     * @param $recipients
     * @param $subject
     * @param $data
     * @param $template
     * @return mixed
     */
    abstract public function sendMail($recipients, $subject, $data, $template);

    /**
     * @param $recipients
     * @param $subject
     * @param $data
     * @param $template
     * @return Swift_Message
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function createMessage($recipients, $subject, $data, $template)
    {
        $sender = $this->config['email']['sender'];
        if(is_array($sender)) {
            $from = $sender['address'];
            $fromName = $sender['name'];
        } else {
            $from = $sender;
            $fromName = '';
        }

        $message = (new Swift_Message($subject))
            ->setFrom($from, $fromName)
            ->setTo($recipients)
            ->setBody($this->twig->render($template . '.html.twig', ['data' => $data]), 'text/html')
            ->addPart($this->twig->render($template . '.txt.twig', ['data' => $data]), 'text/plain');

        return $message;
    }

    abstract protected function createSender();



    /**
     * @param $view_dir
     * @return Environment
     * @throws \ReflectionException
     */
    private function getTwig($view_dir)
    {
        // the path to TwigBridge library so Twig can locate the
        // form_div_layout.html.twig file
        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $vendorTwigBridgeDirectory = dirname($appVariableReflection->getFileName());

        $twig = new Environment(new FilesystemLoader(array(
            $view_dir,
            $vendorTwigBridgeDirectory . '/Resources/views/Form',
        )));

        return $twig;
    }



    /**
     * @return Translator
     */
    private function getTranslator(): Translator
    {
        // creates the Translator
        $translator = new Translator($this->config['locale']);
        // somehow load some translations into it
        $translator->addLoader('yml', new YamlFileLoader());
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addLoader('xlf', new XliffFileLoader());

        $vendorDirectory = realpath(__DIR__ . '/../../vendor');
        $vendorFormDirectory = $vendorDirectory . '/symfony/form';
        $vendorValidatorDirectory = $vendorDirectory . '/symfony/validator';

        $translator->addResource(
            'xlf',
            $vendorFormDirectory . '/Resources/translations/validators.en.xlf',
            'en',
            'validators'
        );
        $translator->addResource(
            'xlf',
            $vendorValidatorDirectory . '/Resources/translations/validators.en.xlf',
            'en',
            'validators'
        );

        foreach ($this->config['translations'] as $translation) {
            $translator->addResource(
                $translation['format'],
                $this->config['translation_dir'] . $translation['file'],
                $translation['locale']
            );
        }

        return $translator;
    }


}
