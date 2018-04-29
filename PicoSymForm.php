<?php

include __DIR__ . "/vendor/autoload.php";

use PicoSymForm\FormHandler;

/**
 * Created by 4/16/18 6:02 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */
class PicoSymForm extends AbstractPicoPlugin
{

    const API_VERSION = 2;

    /**
     * @var FormHandler
     */
    private $formHandler;

    private $globalConfig;
    private $config;

    private function initForms()
    {
        try {
            $this->formHandler = new FormHandler($this->getPico(), $this->config);
        } catch (ReflectionException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    private function generateForm($formName)
    {
        $form = $this->formHandler->generateForm($formName);

        return $this->formHandler->generateView($form);
    }

    public function onConfigLoaded($config)
    {
        $this->globalConfig = $config;

        $conf = $this->globalConfig['PicoSymForm'];

        $conf += [
            'confDir' => $this->getPico()->getConfigDir() . 'forms/',
            'form_view_dir' => __DIR__ . '/forms/',
            'form_view' => 'form.twig.html',
            'form_theme' => 'form_div_layout.html.twig',
            'locale' => 'en',
            'translation_dir' => __DIR__ . '/translations/',
            'translations' => [
                'messages' => [
                    'file' => 'messages.en.yml',
                    'format' => 'yaml',
                    'locale' => 'en'
                ]
            ]
        ];
        $this->config =  $conf;
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
}
