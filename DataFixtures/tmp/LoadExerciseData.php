<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\DateTime;
use UJM\ExoBundle\Entity\Exercise;

class LoadExerciseData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $resourceType = $manager->getRepository('ClarolineCoreBundle:Resource\ResourceType')->findOneByName('ujm_exercise');
        $rtid = $resourceType->getId();

        //$event = $this->dispatcher->dispatch('create_' . $resourceType, 'CreateResource', array($parent, $resourceType));

        $exercise = new Exercise();
        $exercise->setTitle('ExoFIX Title 1');
        $exercise->setNbQuestion(0);
        $exercise->setDateCreate(new \Datetime());
        $exercise->setDuration(0);
        $exercise->setNbQuestionPage(1);
        $exercise->setMaxAttempts(0);
        $exercise->setCorrectionMode(4); //availability_of_correction: 1:at_the_end_of_assessment, 2: after_the_last_attempt, 3: from, 4: never
        $exercise->setMarkMode(1); //availability_of_score: 1:at_the_same_time_that_the_correction, 2: at_the_end_of_assessment
        $exercise->setStartDate(new \Datetime());
        $exercise->setPublished(true);

        $manager->persist($exercise);
        $manager->flush();
        $this->addReference('exercise1', $exercise);
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 1;
    }
}