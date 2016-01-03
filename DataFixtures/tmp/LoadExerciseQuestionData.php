<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use UJM\ExoBundle\Entity\ExerciseQuestion;

class LoadExerciseQuestionData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $exercisequestion = new ExerciseQuestion($this->getReference('exercise1'), $this->getReference('question1'));
        $exercisequestion->setOrdre(1);
        $manager->persist($exercisequestion);
        $manager->flush();

        $exercisequestion = new ExerciseQuestion($this->getReference('exercise1'), $this->getReference('question2'));
        $exercisequestion->setOrdre(2);
        $manager->persist($exercisequestion);
        $manager->flush();
    }

    public function getOrder()
    {
        return 4;
    }
}