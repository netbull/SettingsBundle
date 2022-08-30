<?php

namespace NetBull\SettingsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NetBull\SettingsBundle\Exception\SettingsException;

class SettingsType extends AbstractType
{
    /**
     * @var array
     */
    protected $settingsConfiguration;

    /**
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
        if ($options['group']) {
            $this->addFields($builder, $this->settingsConfiguration[$options['group']], $options, $options['group']);
        } else {
            foreach ($this->settingsConfiguration as $group => $settings) {
                $this->addFields($builder, $settings, $options, $group);
            }
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $settings
     * @param array $options
     * @param string $group
     * @throws SettingsException
     */
    private function addFields(FormBuilderInterface $builder, array $settings, array $options, string $group)
    {
        foreach ($settings as $name => $configuration) {
            $type = sprintf('%s_%s', $group, $name);
            // If setting's value exists in data and setting isn't disabled
            if (array_key_exists($type, $options['data']) && !in_array($name, $options['disabled_settings'])) {
                $fieldType = $configuration['type'];
                $fieldOptions = $configuration['options'];
                $fieldOptions['constraints'] = $configuration['constraints'];

                // Validator constraints
                if (!empty($fieldOptions['constraints']) && is_array($fieldOptions['constraints'])) {
                    $constraints = [];
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

                $builder->add($type, $fieldType, $fieldOptions);
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'disabled_settings' => [],
            'group' => null,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'netbull_settings_management';
    }
}
