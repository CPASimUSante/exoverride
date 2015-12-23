<?php

namespace CPASimUSante\ExoverrideBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use CPASimUSante\ExoverrideBundle\Form\MainConfigItemType;

class MainConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'items', 'collection', array(
                    'type'          => new MainConfigItemType(),
                    'by_reference'  => false,
                    'prototype'     => true,
                    'allow_add'     => true,
                    'allow_delete'  => true,
                )
            )
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CPASimUSante\ExoverrideBundle\Entity\MainConfig'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cpasimusante_itemselectorbundle_mainconfig';
    }
}
