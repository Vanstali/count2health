<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;

class RecipeType extends AbstractType
{

    private $recipe;

    public function __construct($recipe)
    {
        return $this->recipe = $recipe;
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
            ->add('name', 'text', array(
                        'label' => 'Entry Name',
                        ))
            ->add('units', 'number', array(
                        'label' => 'Serving',
                        ))
->add('servings', 'choice', array(
            'choice_list' => $this->loadChoiceList(),
            ))
->add('meal', 'choice', array(
            'placeholder' => '--- Select One ---',
            'choices' => array(
            'breakfast' => 'Breakfast',
            'lunch' => 'Lunch',
            'dinner' => 'Dinner',
            'other' => 'Other',
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
        return 'count2health_appbundle_recipe';
    }

    protected function loadChoiceList()
    {
        $choices = array();

        foreach ($this->recipe->serving_sizes->serving as $serving)
        {
            $choices[0] = "$serving->serving_size";
        }

    return new SimpleChoiceList($choices);
    }
}
