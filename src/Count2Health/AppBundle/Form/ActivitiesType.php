<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

class ActivitiesType extends AbstractType
{
    private $exercises;

    public function __construct($exercises)
    {
        $this->exercises = $exercises;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('time', 'time', array(
                        'label' => 'Add Time',
                        'input' => 'array',
                        ))
->add('from', 'choice', array(
            'choice_list' => $this->loadChoiceList(),
            'label' => 'Taking Time From',
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
        return 'count2health_appbundle_activities';
    }

    protected function loadChoiceList()
    {
        $choices = array();
        foreach ($this->exercises as $exercise) {
            $minutes = intval($exercise['minutes']);
            $duration = array();

            if ($minutes > 60) {
                $hours = floor($minutes / 60);
                $duration[] = "{$hours}h";

                $minutes = fmod($minutes, 60);
            }

            if ($minutes > 0) {
                $duration[] = "{$minutes}m";
            }

            $choices[$exercise['id']] = $exercise['name'].' ('.
                implode(' ', $duration).')';
        }

        return new SimpleChoiceList($choices);
    }
}
