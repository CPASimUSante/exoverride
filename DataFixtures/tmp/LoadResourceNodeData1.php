<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;

class LoadResourceNodeData implements FixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $data = array(
            'user'      => 1,
            'ws'        => 2,
            'parentrn'  => 14,
            'title' => 'ExoFIX Title 1',
        );

        $user = $manager->getRepository('ClarolineCoreBundle:User')->findOneById($data['user']);
        $resourceType = $manager->getRepository('ClarolineCoreBundle:Resource\ResourceType')->findOneByName('ujm_exercise');
        $ws = $manager->getRepository('ClarolineCoreBundle:Workspace\Workspace')->findOneById($data['ws']);
        $icon = $manager->getRepository('ClarolineCoreBundle:Resource\ResourceIcon')->findOneByMimeType('custom/ujm_exercise');
        $parent = $manager->getRepository('ClarolineCoreBundle:Resource\ResourceNode')->findOneById($data['parentrn']);
        //$guid = $this->container->get('claroline.utilities.misc')->generateGuid();

        $node = $manager->factory('Claroline\CoreBundle\Entity\Resource\ResourceNode');

       // $node = new ResourceNode();
        $node->setResourceType($resourceType);
        $node->setCreator($user);
        $node->setCreationDate(new \Datetime());
        $node->setName($data['title']);
        $node->setWorkspace($ws);
        $node->setMimeType('custom/ujm_exercise');
        $node->setPublished(true);
        $node->setActive(true);
        $node->setClass('UJM\ExoBundle\Entity\Exercise');
        $node->setIcon($icon);
       /*
       $parentPath = '';
        if ($parent) {
            $parentPath .= $parent->getPathForDisplay() . ' / ';
        }
       $node->setPathForCreationLog($parentPath . 'ExoFIX Title 1');
       */
        $node->setGuid('abcd');
/*
        $rn->setParent();
*/
        $manager->persist($node);
        $manager->flush();
       // $this->addReference('rn1', $node);
    }
}