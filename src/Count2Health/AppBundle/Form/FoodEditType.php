<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

class FoodEditType extends AbstractType
{

    private $food;

    public function __construct($food)
    {
        $this->food = $food;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
->add('entryId', 'hidden')
            ->add('name', 'text', array(
                        'label' => 'Entry Name',
                        ))
            ->add('numberOfUnits', 'number', array(
                        'label' => 'Serving',
                        ))
;

if (null != $this->food) {
$builder
->add('servings', 'choice', array(
            'choice_list' => $this->loadChoiceList(),
            ))
;
}

$builder
->add('meal', 'choice', array(
            'placeholder' => '--- Select One ---',
            'choices' => array(
            'Breakfast' => 'Breakfast',
            'Lunch' => 'Lunch',
            'Dinner' => 'Dinner',
            'Other' => 'Other',
            ),
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
        return 'count2health_appbundle_foodedit';
    }

    protected function loadChoiceList()
    {
        $choices = array();
                $servings = $this->food->servings->serving;

        foreach ($servings as $serving)
        {
            $choices["$serving->serving_id"] = "$serving->serving_description";
        }

    return new SimpleChoiceList($choices);
    }
}
