<?php

namespace CPASimUSante\ExoverrideBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ExoverrideStatConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userlist', 'text', array(
                'required' => true,
                'label' => 'userlist_for_graph'
            ))
            ->add(
                'datas', 'collection', array(
                    'type'          => new ExoverrideStatConfigDataType(),
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
            'data_class' => 'CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfig',
            'translation_domain' => 'resource',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cpasimusante_exoverridebundle_exoverridestatconfig';
    }
}
