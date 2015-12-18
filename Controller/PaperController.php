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
        $res = $this->getDoctrine()
            ->getManager()
            ->getRepository('CPASimUSanteExoverrideBundle:Response')
            ->getExerciseAllResponsesForAllUsersQuery($exerciseId, 'id');
var_dump($res[0]->getMark());
        die();
*/
        //list of labels for Choice
        $choicetmp = array();

        //if user is creator of the exercise
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
            //mean for user for the exercise
            $mean       = array();
            $tmpmean    = array();
            //general mean for the exercise
            $galmean    = 0;

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
                //user id
                $uid = $row1->getUser()->getId();

                $rowCSV[] = $uid;
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

                //get Responses for this paper
                $row2 = $row[0];

                //get the result for responses for an exercise

                //can't get the ujm_choice directly in the first query (string with ;)
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
                //Create an array for each response from a user
                $arr_tmp = array(
                    //$row2->getResponse(),     //don't want to display choices ids : get labels instead
                    'choice'    => $choice,
                    'marks'     => $row2->getMark(),
                    'tries'     => $row2->getNbTries(),
                    'title'     => $row2->getInteraction()->getQuestion()->getTitle(),
                );
                if (!isset($tmpmean[$uid]))
                {
                    $tmpmean[$uid]['sum'] = $row2->getMark();
                    $tmpmean[$uid]['count'] = 1;
                }
                else
                {
                    $tmpmean[$uid]['sum'] += $row2->getMark();
                    $tmpmean[$uid]['count'] += 1;
                }

                $results2[$paper][] = $arr_tmp;

                //get the result for an exercise
                $results[$paper] = $rowCSV;

//                fputcsv($handle, $rowCSV);
                $em->detach($row[0]);
            }

            foreach ($tmpmean as $uid => $m)
            {
                if (isset($m['count']))
                {
                    $mean[$uid] = $m['sum']/$m['count'];
                }
                else
                {
                    $mean[$uid] = 0;
                }
                $galmean += $mean[$uid];
            }

            if ($tmpmean != array())
                $galmean = $galmean / count($tmpmean);
            else
                $galmean = 0;
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
                    'exercise'  => $exercise->getTitle(),
                    'mean'      => $mean,
                    'galmean'   => $galmean,
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

    private function getMean($tmpmean, $uid, $mark, $mean, $galmean)
    {
        if (!isset($tmpmean[$uid]))
        {
            $tmpmean[$uid]['sum'] = $mark;
            $tmpmean[$uid]['count'] = 1;
        }
        else
        {
            $tmpmean[$uid]['sum'] += $mark;
            $tmpmean[$uid]['count'] += 1;
        }

        foreach ($tmpmean as $uid => $m)
        {
            if (isset($m['count']))
            {
                $mean[$uid] = $m['sum']/$m['count'];
            }
            else
            {
                $mean[$uid] = 0;
            }
            $galmean += $mean[$uid];
        }

        if ($tmpmean != array())
            $galmean = $galmean / count($tmpmean);
        else
            $galmean = 0;

        return array( 'mean' => $mean, 'galmean' => $galmean );
    }

    /**
     * List of exercices results
     * Data to be formated as JSON
     *
     * @param array $exolist
     * @param array $userlist
     * @return Response
     */
    public function exportResCompleteAllExerciseCSVAction($exolist=array())
    {
        $em = $this->getDoctrine()->getManager();
        $data = array();

        $exolist = array(26, 27, 28);
        //get the Exercises entities
        $exercises = $em->getRepository('UJMExoBundle:Exercise')->findById($exolist);

        $row = array();

        //list of labels for Choice
        $choicetmp = array();

        $tmpmean    = array();

        foreach($exercises as $exercise) {
            $data['label'][] = $exercise->getTitle();
            $exerciseId = $exercise->getId();

            if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise)) {
                //exercicse title
                $row[$exerciseId]['exercise'] = $exercise->getTitle();

                //has to be all users : to compute the general mean
                $exerciseResult = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getExerciseAllResponsesForAllUsersQuery($exerciseId, 'id');

                //mean for user for the exercise
                $mean       = array();
                //general mean for the exercise
                $galmean    = 0;

                foreach ($exerciseResult as $results)
                {
                    $row1 = $results->getPaper();
                    //paper_id
                    $paper = $row1->getId();

                    $uid = $row1->getUser()->getId();
                    $uname = $row1->getUser()->getLastName() . '-' . $row1->getUser()->getFirstName();

                    $row[$exerciseId]['user'][$uid]['id'] = $uname;
                    $row[$exerciseId]['user'][$uid]['mark'][] = $results->getMark();
                    $row[$exerciseId]['user'][$uid]['nbTries'] = $results->getNbTries();

                    //get the result for responses for an exercise

                    //can't get the ujm_choice directly in the first query (string with ;)
                    $choice = array();
                    $choiceIds = array_filter(explode(";",$results->getResponse()), 'strlen'); //to avoid empty value
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
                    //Create an array for each response from a user
                    $arr_tmp = array(
                        //$row2->getResponse(),     //don't want to display choices ids : get labels instead
                        'choice'    => $choice,
                        'marks'     => $results->getMark(),
                        'tries'     => $results->getNbTries(),
                        'title'     => $results->getInteraction()->getQuestion()->getTitle(),
                    );

                    $results2[$paper][] = $arr_tmp;

                    $means = $this->getMean($tmpmean, $uid, $results->getMark(), $mean, $galmean);
                    $row[$exerciseId]['galmean'] = $means['galmean'];
                    $row[$exerciseId]['user'][$uid]['mean'] = $means['mean'];
                }
            }
        }

        return $this->render(
            'UJMExoBundle:Paper:showStatsForExercises.html.twig', array(
                'datas'      => $data,
                'row'    => $row,
            )
        );







        /*
        //get the Exercise entity
        $exercises = $em->getRepository('UJMExoBundle:Exercise')->findById($exolist);

        foreach($exercises as $exercise)
        {
            $rowCSV = array();
            $data['label'][] = $exercise->getTitle();

            //get paper for this exercise
            $row1 = $exercise[0]->getPaper();
            //paper_id
            $paper = $row1->getId();

            $uid = $row1->getUser()->getId();

            $data['datasets']['label'][] = $row1->getUser()->getLastName() . ' ' . $row1->getUser()->getFirstName();
            $data['datasets']['data'][] = '';

        }
*/
/*
        $responseResults = $this->getDoctrine()
            ->getManager()
            ->getRepository('CPASimUSanteExoverrideBundle:Response')
            ->getSomeExerciseAllResponsesForAllUsers($exolist);

        foreach ($responseResults as $response)
        {
            $rowCSV = array();

            //get paper for this exercise
            $row1 = $response->getPaper();
            //paper_id
            $paper = $row1->getId();
            $uid = $row1->getUser()->getId();

            $rowCSV[] = $uid;
            $rowCSV[] = $row1->getUser()->getLastName() . '-' . $row1->getUser()->getFirstName();

        }
        return $this->render(
            'UJMExoBundle:Paper:showStatsForExercises.html.twig', array(
                'exores'      => $responseResults,
                'rows'      => $rowCSV,
            )
        );
*/


    }
}