<?php
namespace CPASimUSante\ExoverrideBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\DateTime;
use UJM\ExoBundle\Entity\Question;

class LoadQuestionData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /*     //get user
             $em = $this->container->get('doctrine')->getEntityManager();
             $user = $em->getRepository('ClarolineCoreBundle:User')->findOneById(1);
     */
        $user = $manager->getRepository('ClarolineCoreBundle:User')->findOneById(1);

        $arr_data = array(
            array('QuestFIX title1', $this->getReference('category1'), $user),
            array('QuestFIX title2', $this->getReference('category1'), $user),
        );

        $inc = 1;
        foreach($arr_data as $data)
        {
            $question = new Question();
            $question->setTitle($data[0]);
            $question->setCategory($data[1]);
            $question->setUser($data[2]);
            $question->setDateCreate(new \DateTime());
            $manager->persist($question);
            $manager->flush();
            $this->addReference('question'.$inc, $question);
            $inc++;
        }
    }

    public function getOrder()
    {
        return 3;
    }
}