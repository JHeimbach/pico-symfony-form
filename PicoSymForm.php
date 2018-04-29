<?php

include __DIR__ . "/vendor/autoload.php";

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
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

/**
 * Created by 4/16/18 6:02 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */
class PicoSymForm extends AbstractPicoPlugin
{

    const API_VERSION = 2;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var FormFactoryInterface
     */
    private $formBuilder;

    private $defaultConfig = [
        'form_theme' => 'form_div_layout.html.twig',
        'translations' => [
            'messages' => ['file' => 'translations/messages.en.yml',
                'format' => 'yaml',
                'locale' => 'en'
            ]
        ]
    ];

    /**
     * @throws ReflectionException
     */
    private function initForms()
    {
        $csrfManager = new CsrfTokenManager();
        // the Twig file that holds all the default markup for rendering forms
        // this file comes with TwigBridge
        $defaultFormTheme = 'form_div_layout.html.twig';

        $this->twig = $this->getTwig($defaultFormTheme, $csrfManager);

        // adds the FormExtension to Twig
        $this->twig->addExtension(new FormExtension());

        // adds the TranslationExtension (gives us trans and transChoice filters)
        $this->twig->addExtension(new TranslationExtension($this->getTranslator()));

        // Set up the Form component
        $this->formBuilder = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($csrfManager))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }

    /**
     * @param $content
     * @throws ReflectionException
     */
    public function onContentParsed(&$content)
    {
        $includeFormPattern = '/<p>%include_form\((.*)\)%<\/p>/';
        preg_match($includeFormPattern, $content, $matches);

        if (count($matches) < 1) {
            return;
        }

        $this->initForms();

        $content = preg_replace($includeFormPattern, $this->generateForm($matches[1]), $content);
    }

    private function generateForm($formName)
    {
        // Todo: create form from config file
        $form = $this->formBuilder->createBuilder()
            ->add('firstName', TextType::class)->getForm();

        return $this->twig->render("$formName.twig.html", [
            'form' => $form->createView(),
            'form_name' => $formName,
        ]);
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

        //Todo: load from Config
        // the path to your other templates
        $viewsDirectory = realpath(__DIR__ . '/forms');

        $twig = new Environment(new FilesystemLoader(array(
            $viewsDirectory,
            $vendorTwigBridgeDirectory . '/Resources/views/Form',
        )));

        $formEngine = new TwigRendererEngine(array($defaultFormTheme), $twig);
        $twig->addRuntimeLoader(new FactoryRuntimeLoader(array(
            FormRenderer::class => function () use ($formEngine, $csrfManager) {
                return new FormRenderer($formEngine, $csrfManager);
            },
        )));

        return $twig;
    }

    /**
     * @return Translator
     */
    private function getTranslator(): Translator
    {
        //Todo: load locale from config
        // creates the Translator
        $translator = new Translator('en');
        // somehow load some translations into it
        $translator->addLoader('yml', new YamlFileLoader());
        $translator->addLoader('xlf', new XliffFileLoader());

        $vendorDirectory = realpath(__DIR__ . '/vendor');
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

        // Todo: load from Config
        $translator->addResource(
            'yml',
            __DIR__ . '/translations/messages.en.yml',
            'en'
        );

        return $translator;
    }
}
