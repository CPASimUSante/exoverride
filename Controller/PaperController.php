<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Library\Resource\ResourceCollection;

//TMP fixtures
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use UJM\ExoBundle\Controller\PaperController as BaseController;

/**
 * Override of UJM/ExoBundle Paper controller.
 *
 */
class PaperController extends BaseController
{
    public function loadFixturesAction()
    {
        $em = $this->getDoctrine()->getManager();
        //Load the fixtures
        $loader = new Loader();
        $loader->loadFromDirectory('/home/olivier/www/claroline/claroline6/claro6-samu/Claroline/vendor/cpasimusante/exoverride-bundle/CPASimUSante/ExoverrideBundle/DataFixtures/ORM');
        //Execute the fixtures
        $purger = new ORMPurger();
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures(), true);   //true to append
    }

    /**
     * Data to be sent to Chart.js
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @param string $resourcedata list of resources
     * @param string $userdata list of users
     * @return JsonResponse
     */
    public function getResultExercisesJsonAction($resourcedata='', $userdata='')
    {
        $exolist = ($resourcedata == '') ? array() : explode(',', $resourcedata);
        $userlist = ($userdata == '') ? array() : explode(',', $userdata);

        //list of hexa colors for graph
        $rgbcolors = array("#000000", "#FFFF00", "#1CE6FF", "#FF34FF", "#FF4A46", "#008941", "#006FA6", "#A30059",
            "#FFDBE5", "#7A4900", "#0000A6", "#63FFAC", "#B79762", "#004D43", "#8FB0FF", "#997D87",
            "#5A0007", "#809693", "#FEFFE6", "#1B4400", "#4FC601", "#3B5DFF", "#4A3B53", "#FF2F80",
            "#61615A", "#BA0900", "#6B7900", "#00C2A0", "#FFAA92", "#FF90C9", "#B903AA", "#D16100",
            "#DDEFFF", "#000035", "#7B4F4B", "#A1C299", "#300018", "#0AA6D8", "#013349", "#00846F",
            "#372101", "#FFB500", "#C2FFED", "#A079BF", "#CC0744", "#C0B9B2", "#C2FF99", "#001E09",
            "#00489C", "#6F0062", "#0CBD66", "#EEC3FF", "#456D75", "#B77B68", "#7A87A1", "#788D66",
            "#885578", "#FAD09F", "#FF8A9A", "#D157A0", "#BEC459", "#456648", "#0086ED", "#886F4C");
        //to rgb
        $colors = array_map(array($this, 'rgb2hex'), $rgbcolors);

        $datas = $this->resultsAndStatsForExercises($exolist, $userlist);

        $json = array();
        $json['datasets'] = array();
        $user = array();
        $galmean = array();
        foreach($datas['row'] as $e => $exercice)
        {
//            if (in_array($e, $exolist))
//            {
            $json['labels'][] = $exercice['exercise'];
            $galmean[] = number_format(($exercice['galmean'])*100, 2);
            foreach($exercice['user'] as $k => $userdata)
            {
                $user[$k]['name'] = $exercice['user'][$k]['uname'];
                $user[$k]['mean'][] = number_format(($exercice['user'][$k]['mean'])*100, 2);
            }
//            }
        }
        $inc = 0;
        //dataset for group
        $json['datasets'][] = $this->setObjectForRadarDataset('group', $galmean, $this->rgbacolor($colors[$inc]));
        $inc++;
        //datasets for users
        foreach($user as $k => $u)
        {
            //display only selected users
            if (in_array($k, $userlist))
            {
                $json['datasets'][] = $this->setObjectForRadarDataset($u['name'], $u['mean'], $this->rgbacolor($colors[$inc]));
                $inc++;
            }
        }

        return new JsonResponse($json);
    }

    /**
     * Prepare complete statistics to be displayed
     *
     * @param array $resourcedata
     * @return Response
     */
    public function getResultExercisesHtmlAction($resourcedata='')
    {
        $exolist = ($resourcedata == '') ? array() : explode(',', $resourcedata);

        $datas = $this->resultsAndStatsForExercises($exolist);

        $html = '';
        foreach($datas['row'] as $e => $exercise)
        {
            $html .= '<table class="table table-responsive">';
            $html .= '<tr><th colspan="2"><b>'.$exercise['exercise'].'</b></th>';
            $html .= '<th>Moyenne Générale : '.number_format(($exercise['galmean'])*100, 2).'%</th></tr>';

            $html .= '<tr><td colspan="3">Questions : <ul>';
            foreach($exercise['question'] as $question)
            {
                $html .= '<li>'.$question['name'].'</li>';
            }
            $html .= '</ul></td></tr>';

            foreach($exercise['user'] as $u => $userdata)
            {
                $html .= '<tr><td><u>'.$userdata['uname'].'</u></td>';
                $html .= ' <td>Moyenne tous essais :  '.number_format(($exercise['user'][$u]['mean'])*100, 2).'%</td>
                <td>Moyenne dernier essai :  '.number_format(($exercise['avg_last'][$u])*100, 2).'%</td>
                </tr>';

                $html .= '<tr><td colspan="3">Réponse : <br>';
                $inc = 1;
                foreach($userdata['mark'] as $p => $papermark)
                {
                    $html .= 'Essai '.$inc.' ('.$userdata['start'][$p].' - '.$userdata['end'][$p].') => ';
                    foreach($papermark as $m => $mark)
                    {
                        $html .= $userdata['question'][$p][$m] .' : '. number_format(($mark)*100, 2) .'%  - ';
                    }
                    $html .= '<br>';
                    $inc++;
                }
                $html .= '</td></tr>';
            }
            $html .= '</table>';
        }

        return new JsonResponse($html);
    }

    /**
     * Set data for csv export
     *
     * @param string $resourcedata
     * @return Response
     */
    public function getResultExercisesCsvAction($resourcedata='')
    {
        $exolist = ($resourcedata == '') ? array() : explode(',', $resourcedata);

        $date = new \DateTime();
        $now = $date->format('Ymd-His');

        //TODO : repasser les $csv dans un array general et mettre dans exportResCompleteCSVAction
        //TODO : pour n'avoir qu'une boucle
        $handle = fopen('php://memory', 'r+');
        $row = $this->resultsAndStatsForExercises($exolist);
        foreach($row['row'] as $exercise)
        {
            $csv = array();
            //exercise name
            $csv[] = $exercise['exercise'];
            //general mean
            $csv[] = '';
            //questions name
            foreach($exercise['question'] as $question)
            {
                $csv[] = $question['name'];
            }

            /*  $infosPaper = $this->container->get('ujm.exercise_services')->getInfosPaper($row1);
              $score = $infosPaper['scorePaper'] / $infosPaper['maxExoScore'];
              $score = $score * 20;*/
            //user id
            fputcsv($handle, $csv);

            $csv = array();
            $csv[] = 'Moyenne Générale';
            $csv[] = number_format(($exercise['galmean'])*100, 2);
            fputcsv($handle, $csv);

            foreach($exercise['user'] as $u => $userdata)
            {
                $csv = array();
                $csv[] = $exercise['user'][$u]['uname'];
                fputcsv($handle, $csv);

                $csv = array();
                $csv[] = 'Moyenne tous essais';
                $csv[] = number_format(($exercise['user'][$u]['mean'])*100, 2);
                fputcsv($handle, $csv);

                $csv = array();
                $csv[] = 'Moyenne dernier essai';
                $csv[] = number_format(($exercise['avg_last'][$u])*100, 2);
                fputcsv($handle, $csv);

                //responses
                foreach($userdata['question'] as $pid => $paperresponse)
                {
                    $csv = array();
                    $csv[] = '';
                    $csv[] = '';
                    foreach($paperresponse as $response)
                    {
                        $csv[] = $response;
                    }
                    fputcsv($handle, $csv);
                }

            }
            /*
                        $rowCSV[] = $row1->getUser()->getLastName() . ' ' . $row1->getUser()->getFirstName();
                        $rowCSV[] = $row1->getNumPaper();
                        $rowCSV[] = $row1->getStart()->format('Y-m-d H:i:s');
                        if ($row1->getEnd()) {
                            $rowCSV[] = $row1->getEnd()->format('Y-m-d H:i:s');
                        } else {
                            $rowCSV[] = $this->get('translator')->trans('no_finish');
                        }
                        $rowCSV[] = $row1->getInterupt();
                        $rowCSV[] = $this->container->get('ujm.exercise_services')->roundUpDown($score);
            */

            $csv = array();
            fputcsv($handle, $csv);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return new Response($content, 200, array(
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'attachment; filename="exportall-'.$now.'.csv"'
        ));
    }

    /**
     * List of exercices results
     * Data to be used in various ways : json, csv, html
     *
     * @param array $exolist
     * @param array $userlist   not used yet here
     * @return Response
     */
    public function resultsAndStatsForExercises($exolist=array(), $userlist=array())
    {
        $em = $this->getDoctrine()->getManager();

        //get the Exercises entities
        $exercises = $em->getRepository('UJMExoBundle:Exercise')->findById($exolist);

        $row = array();

        //list of labels for Choice
        $choicetmp = array();

        foreach($exercises as $exercise)
        {
            $exerciseId = $exercise->getId();

            if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise))
            {
                $averages = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getAverageForExerciseLastTryByUser($exerciseId);
                foreach($averages as $average)
                {
                    $row[$exerciseId]['avg_last'][$average['user']] = $average['average_mark'];
                }

                //exercicse title
                $row[$exerciseId]['exercise'] = $exercise->getTitle();

                //Query has to be for all users : to compute the general mean
                $exerciseResponses = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getExerciseAllResponsesForAllUsersQuery($exerciseId, 'id');

                $tmpmean    = array();
                //mean for user for the exercise
                $mean       = array();
                //general mean for the exercise
                $row[$exerciseId]['galmean'] = 0;
                $gmean = array('m'=>0, 'c'=>0);
                foreach ($exerciseResponses as $responses)
                {
                    $paper = $responses->getPaper();
                    //paper_id
                    $paperId = $paper->getId();

                    $uid = $paper->getUser()->getId();
                    $uname = $paper->getUser()->getLastName() . '-' . $paper->getUser()->getFirstName();

                    //mark
                    $mark = $responses->getMark();

                    $row[$exerciseId]['user'][$uid]['uname'] = $uname;
                    $row[$exerciseId]['user'][$uid]['mark'][$paperId][] = $mark;
                    $row[$exerciseId]['user'][$uid]['nbTries'] = $responses->getNbTries();
                    $row[$exerciseId]['user'][$uid]['start'][$paperId] = $paper->getStart()->format('Y-m-d H:i:s');
                    $row[$exerciseId]['user'][$uid]['end'][$paperId] = $paper->getEnd()->format('Y-m-d H:i:s');

                    //get the result for responses for an exercise

                    //can't get the ujm_choice directly in the first query (string with ;)
                    $choice = array();
                    $choiceIds = array_filter(explode(";", $responses->getResponse()), 'strlen'); //to avoid empty value
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
                    $question = $responses->getInteraction()->getQuestion();
                    $questionId = $question->getId();
                    $row[$exerciseId]['question'][$questionId]['name'] = $question->getTitle();
                    $row[$exerciseId]['user'][$uid]['question'][$paperId][] = implode(';', $choice);

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

                    $gmean['m'] += $mark;
                    $gmean['c'] += 1;

                    foreach ($tmpmean as $uid => $m)
                    {
                        //compute mean for each user
                        if (isset($m['count']))
                        {
                            $row[$exerciseId]['user'][$uid]['mean'] = $m['sum']/$m['count'];
                        }
                        else
                        {
                            $row[$exerciseId]['user'][$uid]['mean'] = 0;
                        }
                    }
                }
                if ($gmean['c'] != 0)
                {
                    $row[$exerciseId]['galmean'] = $gmean['m']/$gmean['c'];
                }
                else
                {
                    $row[$exerciseId]['galmean'] = 0;
                }
            }
        }

        return array(
            'row'   => $row,
        );
    }

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

    private function getMean($tmpmean, $uid, $exerciseId, $mark, $mean, $galmean)
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
/*
    public function openRadarAction()
    {
        return $this->render(
            'UJMExoBundle:Widget:iframe.html.twig', array(
            )
        );
    }
*/
    public function getUsersInWorkspaceAction($wslist = '')
    {
        $ids = [];
        if ($wslist !== '')
        {
            $ws = explode(',', $wslist);
            $em = $this->getDoctrine()->getManager();
            $listofuser = $em->getRepository('ClarolineCoreBundle:User')
                ->findUsersByWorkspaces($ws);
            foreach($listofuser as $user)
            {
                $ids[] = $user->getId();
            }
        }
        return new JsonResponse($ids);
    }

    private function setObjectForRadarDataset($label, $data, $color, $fill=false)
    {
        $class = new \stdClass();
        $class->label = $label;
        $class->data = $data;
        $class->pointStrokeColor = "#fff";
        $class->pointHighlightFill = "#fff";
        $class->fillColor = "rgba(0,0,0,0)";
        $class->strokeColor = $color;
        $class->pointHighlightFill = $color;;
        return $class;
    }

    /**
     * transforms rgb color into hexa color
     * @param $color
     * @return array
     */
    private function rgb2hex($color)
    {
        $color = str_replace("#", "", $color);
        $r = hexdec(substr($color,0,2));
        $g = hexdec(substr($color,2,2));
        $b = hexdec(substr($color,4,2));
        return array($r, $g, $b);
    }

    /**
     * Create rgba color with opacity
     * @param $color
     * @param int $opacity
     * @return string
     */
    private function rgbacolor($color, $opacity=1)
    {
        return 'rgba('.join(',',$color).','.$opacity.')';
    }


    /**
     * Prepare complete statistics to be displayed
     *
     * @param string $resourcedata
     * @return Response
     */
    public function getResultExercisesHtmltestAction($resourcedata='')
    {
        $exolist = ($resourcedata == '') ? array() : explode(',', $resourcedata);

        $datas = $this->resultsAndStatsForExercisestest($exolist);

        $html = '';
        foreach($datas['row'] as $e => $exercise)
        {
            $html .= '<table class="table table-responsive">';
            $html .= '<tr><th colspan="2"><b>'.$exercise['exercise'].'</b></th>';
            $html .= '<th>Moyenne Générale : '.number_format(($exercise['galmean'])*100, 2).'%</th></tr>';

            $html .= '<tr><td colspan="3">Questions : <ul>';
            foreach($exercise['question'] as $question)
            {
                $html .= '<li>'.$question['name'].'</li>';
            }
            $html .= '</ul></td></tr>';

            foreach($exercise['user'] as $u => $userdata)
            {
                $html .= '<tr><td><u>'.$userdata['uname'].'</u></td>';
                $html .= ' <td>Moyenne tous essais :  '.number_format(($exercise['user'][$u]['mean'])*100, 2).'%</td>
                <td>Moyenne dernier essai :  '.number_format(($exercise['avg_last'][$u])*100, 2).'%</td>
                </tr>';

                $inc = 1;
                $html .= '<tr><td colspan="3">Réponse : <br>';
                foreach($userdata['mark'] as $p => $papermark)
                {
                    $html .= 'Essai '.$inc.' ('.$userdata['start'][$p].' - '.$userdata['end'][$p].') => ';
                    foreach($papermark as $m => $mark)
                    {
                        $html .= $userdata['question'][$p][$m] .' : '. number_format(($mark)*100, 2) .'%  - ';
                    }
                    $html .= '<br>';
                    $inc++;
                }
                $html .= '</td></tr>';
            }
            $html .= '</table>';
        }

        return $this->render(
            'UJMExoBundle:Paper:testshow.html.twig', array(
                'datas'   => $datas,
                'html'   => $html,
            )
        );
    }

    public function getResultExercisesCsvtestAction($resourcedata='')
    {
        $exolist = ($resourcedata == '') ? array() : explode(',', $resourcedata);

        $date = new \DateTime();
        $now = $date->format('Ymd-His');

        //TODO : repasser les csv dans un array general et mettre dans exportResCompleteCSVAction
        //TODO : pour n'avoir qu'une boucle
//        $handle = fopen('php://memory', 'r+');
        $row = $this->resultsAndStatsForExercisestest($exolist);
$tmp =array();
        foreach($row['row'] as $eid => $exercice)
        {
$tmp[$eid]['exercise'] = $exercice['exercise'];
$tmp[$eid]['galmean'] = number_format(($exercice['galmean'])*100, 2);

            $csv = array();
            //exercise name
            $csv[] = $exercice['exercise'];
            //general mean
            $csv[] = number_format(($exercice['galmean'])*100, 2);
            $csv[] = 'Essai';
            //questions name
            foreach($exercice['question'] as $question)
            {
$tmp[$eid]['questionname'][] = $question['name'];
                $csv[] = $question['name'];
            }

            /*  $infosPaper = $this->container->get('ujm.exercise_services')->getInfosPaper($row1);
              $score = $infosPaper['scorePaper'] / $infosPaper['maxExoScore'];
              $score = $score * 20;*/
            //user id
//            fputcsv($handle, $csv);
            foreach($exercice['user'] as $k => $userdata)
            {
$tmp[$eid]['user'][$k]['uname'] = $exercice['user'][$k]['uname'];
$tmp[$eid]['user'][$k]['mean'] = number_format(($exercice['user'][$k]['mean'])*100, 2);
                $csv = array();
                $csv[] = $exercice['user'][$k]['uname'];
                $csv[] = number_format(($exercice['user'][$k]['mean'])*100, 2);
                $csv[] = $userdata['nbTries'];
//                fputcsv($handle, $csv);
                //responses
                foreach($userdata['question'] as $pid => $paperresponse)
                {
$tmp[$eid]['user'][$k]['question'][$pid] = $paperresponse;
                    $csv = array();
                    $csv[] = '';
                    $csv[] = '';
                    $csv[] = '';
                    foreach($paperresponse as $response)
                    {
                        $csv[] = $response;
                    }
                }
//                fputcsv($handle, $csv);
            }
            /*
                        $rowCSV[] = $row1->getUser()->getLastName() . ' ' . $row1->getUser()->getFirstName();
                        $rowCSV[] = $row1->getNumPaper();
                        $rowCSV[] = $row1->getStart()->format('Y-m-d H:i:s');
                        if ($row1->getEnd()) {
                            $rowCSV[] = $row1->getEnd()->format('Y-m-d H:i:s');
                        } else {
                            $rowCSV[] = $this->get('translator')->trans('no_finish');
                        }
                        $rowCSV[] = $row1->getInterupt();
                        $rowCSV[] = $this->container->get('ujm.exercise_services')->roundUpDown($score);
            */

//             $csv = array();
//             fputcsv($handle, $csv);
        }
/*
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return new Response($content, 200, array(
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'attachment; filename="exportall-'.$now.'.csv"'
        ));
*/

        return $this->render(
            'UJMExoBundle:Paper:testCsv.html.twig', array(
            //'csv'    => $content,
                'csv'    => $row['row'],
                'tmp'    => $tmp,
            )
        );
    }

    public function resultsAndStatsForExercisestest($exolist=array(), $userlist=array())
    {
        $em = $this->getDoctrine()->getManager();

        //get the Exercises entities
        $exercises = $em->getRepository('UJMExoBundle:Exercise')->findById($exolist);

        $row = array();

        //list of labels for Choice
        $choicetmp = array();

        foreach($exercises as $exercise)
        {
            $exerciseId = $exercise->getId();

            //user is creator of exercice
            if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise))
            {
                $averages = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getAverageForExerciseLastTryByUser($exerciseId);

                foreach($averages as $average)
                {
                    $row[$exerciseId]['avg_last'][$average['user']] = $average['average_mark'];
                }

                //exercicse title
                $row[$exerciseId]['exercise'] = $exercise->getTitle();

                //Query has to be for all users : to compute the general mean
                $exerciseResponses = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getExerciseAllResponsesForAllUsersQuery($exerciseId, 'id');

                $tmpmean    = array();
                //mean for user for the exercise
                $mean       = array();
                //general mean for the exercise
                $row[$exerciseId]['galmean'] = 0;
                $gmean = array('m'=>0, 'c'=>0);
                foreach ($exerciseResponses as $responses)
                {
                    $paper = $responses->getPaper();
                    //paper_id
                    $paperId = $paper->getId();

                    $uid = $paper->getUser()->getId();
                    $uname = $paper->getUser()->getLastName() . '-' . $paper->getUser()->getFirstName();

                    //mark
                    $mark = $responses->getMark();

                    $row[$exerciseId]['user'][$uid]['uname'] = $uname;
                    $row[$exerciseId]['user'][$uid]['mark'][$paperId][] = $mark;
                    $row[$exerciseId]['user'][$uid]['nbTries'] = $responses->getNbTries();
$row[$exerciseId]['user'][$uid]['start'][$paperId] = $paper->getStart()->format('Y-m-d H:i:s');
$row[$exerciseId]['user'][$uid]['end'][$paperId] = $paper->getEnd()->format('Y-m-d H:i:s');

                    //get the result for responses for an exercise

                    //can't get the ujm_choice directly in the first query (string with ;)
                    $choice = array();
                    $choiceIds = array_filter(explode(";", $responses->getResponse()), 'strlen'); //to avoid empty value
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
                    $question = $responses->getInteraction()->getQuestion();
                    $questionId = $question->getId();
                    $row[$exerciseId]['question'][$questionId]['name'] = $question->getTitle();
                    $row[$exerciseId]['user'][$uid]['question'][$paperId][] = implode(';', $choice);

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

                    $gmean['m'] += $mark;
                    $gmean['c'] += 1;

                    foreach ($tmpmean as $uid => $m)
                    {
                        //compute mean for each user
                        if (isset($m['count']))
                        {
                            $row[$exerciseId]['user'][$uid]['mean'] = $m['sum']/$m['count'];
                        }
                        else
                        {
                            $row[$exerciseId]['user'][$uid]['mean'] = 0;
                        }
//echo 'Exo'.$exerciseId.', User'.$uid.' : '.'mean : '.$row[$exerciseId]['user'][$uid]['mean'].'<br>';
                    }
//                    echo '-out-<br>';
                }
                if ($gmean['c'] != 0)
                {
                    $row[$exerciseId]['galmean'] = $gmean['m']/$gmean['c'];
                }
                else
                {
                    $row[$exerciseId]['galmean'] = 0;
                }
            }
        }

        return array(
            'row'   => $row,
        );
//die();
    }
}