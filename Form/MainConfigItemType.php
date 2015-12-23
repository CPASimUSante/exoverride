<?php

namespace CPASimUSante\ExoverrideBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MainConfigItemType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('workspace', 'entity', array(
                'label'         => 'Workspace',
                'class'         => 'Claroline\CoreBundle\Entity\Workspace\Workspace',
                'choice_label'  =>'name',
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CPASimUSante\ExoverrideBundle\Entity\MainConfigItem'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cpasimusante_itemselectorbundle_mainconfigitem';
    }
}
