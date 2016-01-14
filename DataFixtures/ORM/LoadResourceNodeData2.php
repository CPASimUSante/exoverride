<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;

class LoadResourceNodeData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository('ClarolineCoreBundle:User')->findOneById(1);
        $resourceType = $manager->getRepository('ClarolineCoreBundle:Resource\ResourceType')->findOneByName('ujm_exercise');
        $ws = $manager->getRepository('ClarolineCoreBundle:Workspace\Workspace')->findOneById(2);
        $guid = $this->container->get('claroline.utilities.misc')->generateGuid();

        $node = new ResourceNode();
        $node->setResourceType($resourceType);
        $node->setCreator($user);
        $node->setCreationDate(new \Datetime());
        $node->setName('ExoFIX Title 1');
        $node->setWorkspace($ws);
        $node->setMimeType('custom/ujm_exercise');
        $node->setPublished(true);
        $node->setActive(true);
        $node->setClass('UJM\ExoBundle\Entity\Exercise');
        $node->setGuid($guid);
/*
        $rn->setParent();
*/
        $manager->persist($node);
        $manager->flush();
        $this->addReference('rn1', $node);
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 100;
    }
}