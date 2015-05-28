<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Count2Health\UserBundle\Entity\User;

class WeightDiaryEntryType extends AbstractType
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
                        ))
            ->add('weight', 'weight', array(
                        'units' => $this->user->getPersonalDetails()->getWeightUnits(),
                        ))
            ->add('comment', 'text', array(
                        'required' => false,
                        ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Count2Health\AppBundle\Entity\WeightDiaryEntry'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'count2health_appbundle_weightlog';
    }
}
