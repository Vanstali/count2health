<?php

namespace Count2Health\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("count2health_user.profile.form.type")
 * @DI\Tag("form.type",
 *     attributes={"alias": "count2health_userbundle_user_profile"})
 */
class ProfileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('timeZone', 'timezone', array(
                        'placeholder' => '',
                        ))
        ;
    }

    public function getParent()
    {
        return 'fos_user_profile';
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'count2health_userbundle_user_profile';
    }
}
