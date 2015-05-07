<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Count2Health\UserBundle\Entity\User;

class HealthPlanType extends AbstractType
{

    private $user;
    private $type;

    public function __construct(User $user, $type)
    {
        $this->user = $user;
        $this->type = $type;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'hidden')
            ->add('goalWeight', 'weight', array(
                        'units' => $this->user->getSetting()->getWeightUnits(),
                   ))
            ;

        if ($this->type != 'maintenance') {
            $builder
            ->add('targetCalorieDeficit', 'integer', array(
                        'label' => 'Target Calorie ' .
                        ($this->type == 'loss' ? 'Deficit' : 'Excess'),
                        ))
            ->add('goalDate', 'date', array(
                        'format' => 'MMMM d, yyyy',
                        'years' => range(date('Y'), date('Y')+10),
                        ))
        ;
        }
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Count2Health\AppBundle\Entity\HealthPlan',
            'validation_groups' => function(FormInterface $form)
            {
            $data = $form->getData();

            switch ($data->getType())
            {
            case 'loss':
            return array('HealthPlan', 'Loss');

            case 'maintenance':
            return array('HealthPlan', 'Maintenance');

            case 'gain':
            return array('HealthPlan', 'Gain');
            }
            },
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'count2health_appbundle_healthplan';
    }
}
