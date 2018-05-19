<?php
/**
 * Created by 4/29/18 8:09 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */

namespace PicoSymfonyForm;

use Exception;
use ReflectionException;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class FormHandler
{
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var FormFactoryInterface
     */
    private $formBuilder;
    /**
     * @var array;
     */
    private $config;
    /**
     * @var \Pico
     */
    private $pico;

    /**
     * @var EmailHandler
     */
    private $emailHandler;


    /**
     * @throws ReflectionException
     */
    public function __construct($pico, $config)
    {
        $this->pico = $pico;
        $this->config = $config;

        $csrfManager = new CsrfTokenManager();
        // the Twig file that holds all the default markup for rendering forms
        // this file comes with TwigBridge
        $this->twig = $this->getTwig($this->config['form_theme'], $csrfManager);

        // adds the TranslationExtension (gives us trans and transChoice filters)
        $this->twig->addExtension(new TranslationExtension($this->getTranslator()));

        // Set up the Form component
        $this->formBuilder = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($csrfManager))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        $this->emailHandler = new EmailHandler($this->config, $this->twig);
    }

    /**
     * @param $defaultFormTheme
     * @param $csrfManager
     * @return Environment
     * @throws ReflectionException
     */
    private function getTwig($defaultFormTheme, $csrfManager)
    {
        // the path to TwigBridge library so Twig can locate the
        // form_div_layout.html.twig file
        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $vendorTwigBridgeDirectory = dirname($appVariableReflection->getFileName());

        $twig = new Environment(new FilesystemLoader(array(
            $this->config['view_dir'],
            $vendorTwigBridgeDirectory . '/Resources/views/Form',
        )));

        $formEngine = new TwigRendererEngine(array($defaultFormTheme));
        $formEngine->setEnvironment($twig);

        $twig->addExtension(
            new FormExtension(new TwigRenderer($formEngine, $csrfManager))
        );

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

        $vendorDirectory = realpath(__DIR__ . '/../vendor');
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

    /**
     * @param $formName
     * @return \Symfony\Component\Form\FormInterface
     */
    public function generateForm($formName)
    {
        $form = $this->createForm($formName);

        $formBuilder = $this->formBuilder->createBuilder();

        foreach ($form->getFormElements() as $formElement) {
            $formBuilder->add($formElement->getName(), $formElement->getType(), $formElement->getOptions());
        }
        $formBuilder->add('submit', SubmitType::class);

        return $formBuilder->getForm();
    }

    /**
     * @param FormInterface $form
     * @param null|string $alert
     * @return string
     */
    public function generateView($form, $alert = null)
    {
        return $this->twig->render($this->config['form_view'], ['form' => $form->createView(), 'alert' => $alert]);
    }

    public function sendData($formName, $data)
    {
        $form = $this->createForm($formName);
        return $this->emailHandler->sendMail($form->getRecipients(), $form->getSubject(), $data, $form->getTemplate());
    }

    /**
     * @param $formName
     * @return Form
     */
    private function createForm($formName)
    {
        return new Form($this->pico, $formName, $this->config['confDir']);
    }
}
