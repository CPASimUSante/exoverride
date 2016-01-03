<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use UJM\ExoBundle\Entity\Interaction;

class LoadInteractionData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $arr_data = array(
            array($this->getReference('question1'), 'Une invite pour l\'interaction1 question1 FIX'),
            array($this->getReference('question1'), 'Une invite pour l\'interaction2 question1 FIX'),
            array($this->getReference('question2'), 'Une invite pour l\'interaction1 question2 FIX'),
            array($this->getReference('question2'), 'Une invite pour l\'interaction2 question2 FIX'),
        );

        $inc = 1;
        foreach($arr_data as $data)
        {
            $interaction = new Interaction();
            $interaction->setQuestion($data[0]);
            $interaction->setType('InteractionQCM');
            $interaction->setInvite($data[1]);
            $manager->persist($interaction);
            $manager->flush();
            $this->addReference('interaction'.$inc, $interaction);
            $inc++;
        }

    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 5;
    }
}