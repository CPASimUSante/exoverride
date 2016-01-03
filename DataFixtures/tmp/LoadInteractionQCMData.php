<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use UJM\ExoBundle\Entity\InteractionQCM;
use UJM\ExoBundle\Entity\TypeQCM;

class LoadInteractionQCMData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $typeQCM = $manager->getRepository('UJMExoBundle:TypeQCM')->findOneById(2); //QCU

        $interactionqcm = new InteractionQCM();
        $interactionqcm->setTypeQCM($typeQCM);
        $interactionqcm->setInteraction($this->getReference('interaction1'));
        $manager->persist($interactionqcm);
        $manager->flush();
        $this->addReference('interactionqcm1', $interactionqcm);

        $interactionqcm = new InteractionQCM();
        $interactionqcm->setTypeQCM($typeQCM);
        $interactionqcm->setInteraction($this->getReference('interaction2'));
        $manager->persist($interactionqcm);
        $manager->flush();
        $this->addReference('interactionqcm2', $interactionqcm);
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 6;
    }
}