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
            ->add(
                'resourceNode', 'entity', [
                    'label'         => 'Ressource',
                    'class'         => 'ClarolineCoreBundle:Resource\ResourceNode',
                    'choice_label'  => 'name',
                    'empty_value'   => 'Choisissez les ressources',
                    'query_builder' => function(\Claroline\CoreBundle\Repository\ResourceNodeRepository $er) use ($namePattern, $orderedBy) {
                        $qb = $er->createQueryBuilder('rn')
                            ->where('rn.resourceType = :resourcetype')
                            ->setParameter('resourcetype', 'ujm_exercise');
                        if ($namePattern != '')
                        {
                            $qb->andWhere('rn.name LIKE :namePattern')
                                ->setParameter('namePattern', $namePattern);
                        }
                        $qb->orderBy('rn.'.$orderedBy, 'ASC');
                        return $qb;
                    }
                ]
            );
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
