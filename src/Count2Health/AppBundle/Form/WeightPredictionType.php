<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Count2Health\UserBundle\Entity\User;

class WeightPredictionType extends AbstractType
{

    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', 'date', array(
                        'format' => 'MMMM d, yyyy',
                        'years' => range(date('Y'), date('Y')+4),
                        'required' => false,
                        ))
            ->add('weight', 'weight', array(
                        'required' => false,
                        'units' => $this->user->getSetting()->getWeightUnits(),
                        ))
            ->add('bmi', 'number', array(
                        'precision' => 1,
                        'required' => false,
                        'label' => 'BMI',
                        ))
            ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                    ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'count2health_appbundle_weightprediction';
    }
}
