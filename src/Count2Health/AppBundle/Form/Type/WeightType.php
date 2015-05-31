<?php

namespace Count2Health\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\Form\DataTransformer\ImperialWeightTransformer;
use Count2Health\AppBundle\Form\DataTransformer\MetricWeightTransformer;
use Count2Health\AppBundle\Form\DataTransformer\NullWeightTransformer;

/**
 * @DI\Service
 * @DI\Tag("form.type", attributes = {"alias" = "weight"})
 */
class WeightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['hidden'] == true) {
            $builder
                ->add('weight', 'hidden')
                ->addViewTransformer(new NullWeightTransformer());
        } else {
            if ($options['units'] == 'lb') {
                $builder
            ->add('weight', 'number', array(
                        'precision' => 1,
                        'label' => 'lb',
                        ))
            ->addViewTransformer(new ImperialWeightTransformer())
            ;
            } else {
                $builder
            ->add('weight', 'number', array(
                        'label' => 'kg',
                        'precision' => 1,
                        ))
            ->addViewTransformer(new MetricWeightTransformer())
                ;
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['units'] = $options['units'];
    }

    public function getName()
    {
        return 'weight';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                    'units' => '',
                    'hidden' => false,
                    'error_bubbling' => false,
                    ));
    }
}
