<?php

namespace NetBull\SettingsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use NetBull\SettingsBundle\Exception\SettingsException;

/**
 * Class SettingsType
 * @package NetBull\SettingsBundle\Form\Type
 */
class SettingsType extends AbstractType
{
    protected $settingsConfiguration;

    /**
     * SettingsType constructor.
     * @param array $settingsConfiguration
     */
    public function __construct(array $settingsConfiguration)
    {
        $this->settingsConfiguration = $settingsConfiguration;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws SettingsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->settingsConfiguration as $name => $configuration) {
            // If setting's value exists in data and setting isn't disabled
            if (array_key_exists($name, $options['data']) && !in_array($name, $options['disabled_settings'])) {
                $fieldType = $configuration['type'];
                $fieldOptions = $configuration['options'];
                $fieldOptions['constraints'] = $configuration['constraints'];

                // Validator constraints
                if (!empty($fieldOptions['constraints']) && is_array($fieldOptions['constraints'])) {
                    $constraints = array();
                    foreach ($fieldOptions['constraints'] as $class => $constraintOptions) {
                        if (class_exists($class)) {
                            $constraints[] = new $class($constraintOptions);
                        } else {
                            throw new SettingsException(sprintf('Constraint class "%s" not found', $class));
                        }
                    }

                    $fieldOptions['constraints'] = $constraints;
                }

                // Label I18n
                $fieldOptions['label'] = 'labels.'.$name;
                $fieldOptions['translation_domain'] = 'settings';

                // Choices I18n
                if (!empty($fieldOptions['choices'])) {
                    $fieldOptions['choices'] = array_map(
                        function ($label) use ($fieldOptions) {
                            return $fieldOptions['label'].'_choices.'.$label;
                        },
                        array_combine($fieldOptions['choices'], $fieldOptions['choices'])
                    );
                }
                $builder->add($name, $fieldType, $fieldOptions);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'disabled_settings' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'settings_management';
    }
}
