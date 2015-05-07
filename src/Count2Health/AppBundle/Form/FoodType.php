<?php

namespace Count2Health\AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Count2Health\UserBundle\Entity\User;

class FoodType extends AbstractType
{

private $user;
    private $food;
    private $type;

    public function __construct(User $user, $food, $type = 'food')
    {
$this->user = $user;
        $this->food = $food;
        $this->type = $type;
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
'model_timezone' => $this->user->getSetting()->getTimeZone(),
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
        return 'count2health_appbundle_food';
    }

    protected function loadChoiceList()
    {
        $choices = array();
        switch ($this->type)
        {
            case 'food':
                $servings = $this->food->servings->serving;
                break;

            case 'recipe':
                $servings = $this->food->serving_sizes->serving;
                break;
        }

        foreach ($servings as $serving)
        {
            if ($this->type == 'food') {
            $choices["$serving->serving_id"] = "$serving->serving_description";
            }
            else {
            $choices[0] = "$serving->serving_size";
            }
        }

    return new SimpleChoiceList($choices);
    }
}
