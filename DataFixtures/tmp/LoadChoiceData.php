<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use UJM\ExoBundle\Entity\Choice;

class LoadChoiceData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $arr_data = array(
          array('LabelFIX ChoiceA', 1, 1, true),
          array('LabelFIX ChoiceB', 2, 0.75, false),
        );

        foreach($arr_data as $data)
        {
            $choice = new Choice();
            $choice->setLabel($data[0]);
            $choice->setOrdre($data[1]);
            $choice->setWeight($data[2]);
            $choice->setRightResponse($data[3]);
            $choice->setInteractionQCM($this->getReference('interactionqcm1'));
            $manager->persist($choice);
            $manager->flush();
        }

        foreach($arr_data as $data)
        {
            $choice = new Choice();
            $choice->setLabel($data[0]);
            $choice->setOrdre($data[1]);
            $choice->setWeight($data[2]);
            $choice->setRightResponse($data[3]);
            $choice->setInteractionQCM($this->getReference('interactionqcm2'));
            $manager->persist($choice);
            $manager->flush();
        }
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 7;
    }
}