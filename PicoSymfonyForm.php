<?php

include __DIR__ . "/vendor/autoload.php";

use PicoSymForm\FormHandler;

/**
 * Created by 4/16/18 6:02 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */
class PicoSymfonyForm extends AbstractPicoPlugin
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

        $form->handleRequest();

        $alert = isset($_GET['send']) ? $_GET['send'] : null;

        if ($form->isSubmitted() && $form->isValid()) {
            $sended = $this->formHandler->sendData($formName, $form->getData());
            $alert = $sended == 0 ? 'error' : 'success';
            header('Location: ' . $this->getPico()->getRequestUrl() . '?send=' . $alert);
        }

        return $this->formHandler->generateView($form, $alert);
    }

    public function onConfigLoaded($config)
    {
        $this->globalConfig = $config;

        if (!array_key_exists('PicoSymfonyForm', $this->globalConfig)) {
            $this->globalConfig['PicoSymfonyForm'] = [];
        }

        $conf = $this->globalConfig['PicoSymfonyForm'];

        $this->config = array_merge_recursive_distinct([
            'confDir' => $this->getPico()->getConfigDir() . 'forms/',
            'view_dir' => __DIR__ . '/templates/',
            'form_view' => 'form.html.twig',
            'form_theme' => 'form_div_layout.html.twig',
            'locale' => 'en',
            'smtp' => [
                'host' => 'smtp.mailgun.org',
                'username' => '',
                'password' => ''
            ],
            'email' => [
                'sender' => 'sender@example.com',
                'view' => 'email_template'
            ],
            'translation_dir' => __DIR__ . '/translations/',
            'translations' => [
                'messages' => [
                    'file' => 'messages.en.yml',
                    'format' => 'yaml',
                    'locale' => 'en'
                ]
            ]
        ], $conf);
    }

    /**
     * @param $content
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
