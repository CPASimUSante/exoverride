<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;

use UJM\ExoBundle\Controller\PaperController as BaseController;

/**
 * Override of UJM/ExoBundle Paper controller.
 *
 */
class PaperController extends BaseController
{
    /**
     * Get the results as an array
     * @param $exerciseId
     * @return array
     */
    public function getResCompleteAction($exerciseId)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);
        $results = array();

       /* if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise)) {
           /* $iterableResult = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Paper')
                ->getExerciseAllResponsesForAllUsersQ($exerciseId, 'paper');*/
           /* while (false !== ($row = $iterableResult->next())) {
                $infosPaper = $this->container->get('ujm.exercise_services')->getInfosPaper($row[0]);
                $score = $infosPaper['scorePaper'] / $infosPaper['maxExoScore'];
                $score = $score * 20;

                $results[] = $row[0]->getUser()->getLastName() . '-' . $row[0]->getUser()->getFirstName();
                $results[] = $row[0]->getNumPaper();
                $results[] = $row[0]->getStart()->format('Y-m-d H:i:s') . 'XYZ';
                if ($row[0]->getEnd()) {
                    $results[] = $row[0]->getEnd()->format('Y-m-d H:i:s');
                } else {
                    $results[] = $this->get('translator')->trans('no_finish');
                }
                $results[] = $row[0]->getInterupt();
                $results[] = $this->container->get('ujm.exercise_services')->roundUpDown($score);

                $em->detach($row[0]);
            }*/
       /* } else {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }*/
        return $results;
    }

    public function exportResCompleteCSVAction($exerciseId)
    {
        $em = $this->getDoctrine()->getManager();
        //get the Exercise entity
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);
/*
SELECT u0_.id AS id0, u0_.ip AS ip1, u0_.mark AS mark2, u0_.nb_tries AS nb_tries3, u0_.response AS response4,
u0_.paper_id AS paper_id5, u0_.interaction_id AS interaction_id6
FROM ujm_response u0_
INNER JOIN ujm_paper u1_ ON u0_.paper_id = u1_.id
INNER JOIN ujm_exercise u2_ ON u1_.exercise_id = u2_.id
WHERE u2_.id = ? AND u1_.interupt = ? GROUP BY u1_.id
*/
$choice = array();
$choicetmp = array();
        if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise)) {
            $iterableResult = $this->getDoctrine()
                ->getManager()
                ->getRepository('CPASimUSanteExoverrideBundle:Response')
                ->getExerciseAllResponsesForAllUsersIterator($exerciseId, 'id');
//            $handle = fopen('php://memory', 'r+');

            //result for paper
            $results    = array();
            //results for responses for paper
            $results2   = array();

            while (false !== ($row = $iterableResult->next())) {
                $rowCSV = array();
                //row of response
                $row2CSV = array();

                //get paper for this exercise
                $row1 = $row[0]->getPaper();
                //paper_id
                $paper = $row1->getId();

                $infosPaper = $this->container->get('ujm.exercise_services')->getInfosPaper($row1);
                $score = $infosPaper['scorePaper'] / $infosPaper['maxExoScore'];
                $score = $score * 20;

                $rowCSV[] = $row1->getUser()->getLastName() . '-' . $row1->getUser()->getFirstName();
                $rowCSV[] = $row1->getNumPaper();
                $rowCSV[] = $row1->getStart()->format('Y-m-d H:i:s');
                if ($row1->getEnd()) {
                    $rowCSV[] = $row1->getEnd()->format('Y-m-d H:i:s');
                } else {
                    $rowCSV[] = $this->get('translator')->trans('no_finish');
                }
                $rowCSV[] = $row1->getInterupt();
                $rowCSV[] = $this->container->get('ujm.exercise_services')->roundUpDown($score);

                //get the result for an exercise
                $results[$paper] = $rowCSV;

                //get Responses for this paper
                $row2 = $row[0];

                //get the result for responses for an exercise

                //can't get the ujm_choice directlty in the first query (string with ;)
                $choice = array();
                $choiceIds = array_filter(explode(";",$row2->getResponse()), 'strlen'); //to avoid empty value
                foreach ($choiceIds as $cid)
                {
                    if (!in_array($cid, $choicetmp))//to avoid duplicate queries
                    {
                        $label = $em->getRepository('UJMExoBundle:Choice')->find($cid)->getLabel();
                        $choicetmp[$cid] = $label;
                        $choice[] = $label;
                    }
                    else
                    {
                        $choice[] = $choicetmp[$cid];
                    }
                }

                $arr_tmp = array(
                    //$row2->getResponse(),     //dont't want to display choices ids : get labels instead
                    $choice,
                    $row2->getMark(),
                    $row2->getNbTries(),

                );
                $results2[$paper][] = $arr_tmp;

//                fputcsv($handle, $rowCSV);
                $em->detach($row[0]);
            }
/*
            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);
*/
            $date = new \DateTime();
            $now = $date->format('Ymd-His');

            return $this->render(
                'UJMExoBundle:Paper:showStats.html.twig', array(
                    '_resource' => $exercise,
                    'results'   => $results,
                    'results2'  => $results2,
                    'choice'    => $choice,
                )
            );
            /*
            return new Response($content, 200, array(
                'Content-Type' => 'application/force-download',
                'Content-Disposition' => 'attachment; filename="exportall-'.$now.'.csv"'
            ));
            */
        } else {

            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }
    }
}