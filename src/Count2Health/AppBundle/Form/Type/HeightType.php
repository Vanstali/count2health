<?php

namespace Count2Health\AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use JMS\DiExtraBundle\Annotation as DI;
use Count2Health\AppBundle\Form\DataTransformer\ImperialHeightTransformer;
use Count2Health\AppBundle\Form\DataTransformer\MetricHeightTransformer;
use Count2Health\AppBundle\Form\DataTransformer\NullHeightTransformer;

/**
 * @DI\Service
 * @DI\Tag("form.type", attributes = {"alias" = "height"})
 */
class HeightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['hidden']) {
            $builder
                ->add('height', 'hidden')
                ->addViewTransformer(new NullHeightTransformer());
        }
            else {
        if ($options['units'] == 'imperial') {
        $builder
            ->add('feet', 'number', array(
                        'precision' => 0,
                        'error_bubbling' => true,
                        ))
            ->add('inches', 'number', array(
                        'precision' => 1,
                        'error_bubbling' => true,
                        ))
            ->addViewTransformer(new ImperialHeightTransformer())
            ;
        }
        else {
            $builder
                ->add('height', 'number', array(
                            'label' => 'CM',
                            'precision' => 0,
                        'error_bubbling' => true,
                            ))
            ->addViewTransformer(new MetricHeightTransformer())
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
        return 'height';
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
