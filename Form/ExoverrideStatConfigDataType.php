<?php

namespace CPASimUSante\ExoverrideBundle\Form;

use Claroline\CoreBundle\ClarolineCoreBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ExoverrideStatConfigDataType extends AbstractType
{
    /**
     * @var string the pattern to filter the resource
     */
    private $namePattern;

    public function __construct($namePattern = '')
    {
        $this->namePattern = $namePattern;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $namePattern    = $this->namePattern;
        $orderedBy      = 'name';

        $builder
            ->add('resourcelist', 'text', array(
                'required' => true,
                'label' => 'resourcelist_for_graph'
            ));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfigData',
            'translation_domain' => 'resource',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cpasimusante_exoverridebundle_exoverridestatconfigdata';
    }
}
