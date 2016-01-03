<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use UJM\ExoBundle\Entity\Category;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCategoryData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
   /*     //get user
        $em = $this->container->get('doctrine')->getEntityManager();
        $user = $em->getRepository('ClarolineCoreBundle:User')->findOneById(1);
*/
        $user = $manager->getRepository('ClarolineCoreBundle:User')->findOneById(1);

        $category = new Category();
        $category->setLocker(0);
        $category->setUser($user);
        $category->setValue('CatFIX value1');
        $manager->persist($category);
        $manager->flush();
        $this->addReference('category1', $category);
    }

    public function getOrder()
    {
        return 2;
    }
}