<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;

class SettingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('heightUnits', 'choice', array(
                        'expanded' => true,
                        'choices' => array(
                            'inch' => 'Feet/Inches',
                            'cm' => 'Centimetres',
                            ),
                        ))
            ->add('weightUnits', 'choice', array(
                        'expanded' => true,
                        'choices' => array(
                            'lb' => 'Pounds',
                            'kg' => 'Kilograms',
                            ),
                        ))
            ->add('gender', 'choice', array(
                        'expanded' => true,
                        'choices' => array(
                            'male' => 'Male',
                            'female' => 'Female',
                            ),
                        ))
        ->add('height', 'height', array(
                    'hidden' => true,
                    ))
        ->add('startWeight', 'weight', array(
                    'hidden' => true,
                    ))
            ->add('birthDate', 'birthday', array(
                        'format' => 'MMMM d, yyyy',
                        ))
            ->add('activityLevel', 'choice', array(
                        'expanded' => true,
                        'choices' => array(
                            's' => 'Sedentary',
                            'l' => 'Lightly Active',
                            'm' => 'Moderately Active',
                            'v' => 'Very Active',
                            'e' => 'Extremely Active',
                            ),
                        ))
            ->add('timeZone', 'timezone', array(
                        'placeholder' => '',
                        ))
        ;

        // Dynamic modification of height field based on height units
        $heightModifier = function (FormInterface $form, $units = null)
        {
            if ($units == 'inch') {
                $form->add('height', 'height', array(
                            'units' => 'imperial',
                            ));
            }
            elseif ($units == 'cm') {
                $form->add('height', 'height', array(
                            'units' => 'metric',
                            ));
            }
            else {
                $form->add('height', 'height', array(
                            'hidden' => true,
                            ));
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use($heightModifier)
                {
                $data = $event->getData();
                $heightModifier($event->getForm(), $data->getHeightUnits());
                });

        $builder->get('heightUnits')->addEventListener(FormEvents::POST_SUBMIT,
                function (FormEvent $event) use($heightModifier)
                {
$heightUnits = $event->getForm()->getData();
$heightModifier($event->getForm()->getParent(), $heightUnits);
                });

        // Dynamic modification of weight field based on weight units
        $weightModifier = function (FormInterface $form, $units = null)
        {
            if ($units == 'lb') {
                $form->add('startWeight', 'weight', array(
                            'units' => 'lb',
                            ));
            }
            elseif ($units == 'kg') {
                $form->add('startWeight', 'weight', array(
                            'units' => 'kg',
                            ));
            }
            else {
                $form->add('startWeight', 'weight', array(
                            'hidden' => true,
                            ));
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use($weightModifier)
                {
                $data = $event->getData();
                $weightModifier($event->getForm(), $data->getWeightUnits());
                });

        $builder->get('weightUnits')->addEventListener(FormEvents::POST_SUBMIT,
                function (FormEvent $event) use($weightModifier)
                {
$weightUnits = $event->getForm()->getData();
$weightModifier($event->getForm()->getParent(), $weightUnits);
                });

    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Count2Health\AppBundle\Entity\Setting'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'count2health_appbundle_setting';
    }
}
