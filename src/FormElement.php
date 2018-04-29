<?php
/**
 * Created by 4/29/18 8:42 PM.
 * @author Mediengstalt Heimbach - Johannes Heimbach
 */

namespace PicoSymForm;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class FormElement
{
    private $type;
    private $name;
    private $options;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * FormElement constructor.
     * @param $name
     * @param $options
     */
    public function __construct($name, $options)
    {
        $this->name = $name;
        $this->options = array_filter($options, function ($key) {
            return $key !== 'type';
        }, ARRAY_FILTER_USE_KEY);

        if (array_key_exists('type', $options)) {
            $this->type = $this->figureOutType($options['type']);
        } else {
            $this->type = TextType::class;
        }
    }

    private function figureOutType($type)
    {
        $name = ucfirst(strtolower($type)) . 'Type';
        $namespace = 'Symfony\Component\Form\Extension\Core\Type';
        $className = $namespace . '\\' . $name;

        if (class_exists($className)) {
            return $className;
        } else {
            return TextType::class;
        }
    }
}
