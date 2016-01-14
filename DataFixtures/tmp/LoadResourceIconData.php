<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Claroline\CoreBundle\Entity\Resource\ResourceIcon;

class LoadResourceIconData implements FixtureInterface, ContainerAwareInterface
{
    private $container;
    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $icon = new ResourceIcon();
        $icon->setMimeType('custom/ujm_exercise');
        $icon->setRelativeUrl('bundles/ujmexo/images/icons/res_exo.png');
        $icon->setShortcut(false);

        $manager->persist($icon);
        $manager->flush();

        $this->container->get('claroline.manager.icon_manager')
            ->createShortcutIcon($icon);

        $this->addReference('icon1', $icon);
    }
}