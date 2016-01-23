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
    //list of hexa colors for graph
    private static $RGBCOLORS = array("#000000", "#FFFF00", "#1CE6FF", "#FF34FF", "#FF4A46", "#008941", "#006FA6", "#A30059",
        "#FFDBE5", "#7A4900", "#0000A6", "#63FFAC", "#B79762", "#004D43", "#8FB0FF", "#997D87",
        "#5A0007", "#809693", "#FEFFE6", "#1B4400", "#4FC601", "#3B5DFF", "#4A3B53", "#FF2F80",
        "#61615A", "#BA0900", "#6B7900", "#00C2A0", "#FFAA92", "#FF90C9", "#B903AA", "#D16100",
        "#DDEFFF", "#000035", "#7B4F4B", "#A1C299", "#300018", "#0AA6D8", "#013349", "#00846F",
        "#372101", "#FFB500", "#C2FFED", "#A079BF", "#CC0744", "#C0B9B2", "#C2FF99", "#001E09",
        "#00489C", "#6F0062", "#0CBD66", "#EEC3FF", "#456D75", "#B77B68", "#7A87A1", "#788D66",
        "#885578", "#FAD09F", "#FF8A9A", "#D157A0", "#BEC459", "#456648", "#0086ED", "#886F4C");


    private function getListexolist($resourcedata)
    {
        $listexolist = array();
        if ($resourcedata != '')
        {
            //for each group of exercise
            $list = explode(';', $resourcedata);
            //get array of exercise
            foreach ($list as $item)
            {
                if ($item != '')
                {
                    $listexolist[] = explode(',', $item);
                }
            }
        }
        return $listexolist;
    }

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
                //Exercise Directory (to be used as radar label)
                $row[$exerciseId]['directory'] = $exercise->getResourceNode()->getParent()->getName();

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
     * V2 - Prepare complete statistics to be displayed
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

    /**
     * Prepare complete statistics to be displayed
     *
     * @param string $resourcedata
     * @return Response
     */
    public function getResultExercisesHtmltest2Action($resourcedata='')
    {
        $listexolist = array();
        if ($resourcedata != ''){
            //for each group of exercise
            $list = explode(';', $resourcedata);
            //get array of exercise
            foreach ($list as $item){
                if ($item != ''){
                    $listexolist[] = explode(',', $item);
                }
            }
        }

        $html = '';
        $dataall = array();

        foreach($listexolist as $exolist)
        {
            $datas = $this->resultsAndStatsForExercisestest($exolist);
            $dataall[] = $datas;
            $htmltmp = '';
            foreach($datas['row'] as $e => $exercise)
            {
                $htmltmp .= '<table class="table table-responsive">';
                $htmltmp .= '<tr><th><b>'.$exercise['exercise'].'</b></th>';
                $htmltmp .= '<th>Moyenne générale : '.number_format(($exercise['galmean'])*100, 2).'%<br> = Moyenne tous essais pour tous utilisateurs</th>';
                $htmltmp .= '<th>Moyenne dernier essai : '.number_format(($exercise['galmeanlast'])*100, 2).'%</th></tr>';

                $htmltmp .= '<tr><td colspan="3">Questions : <ul>';
                foreach($exercise['question'] as $question)
                {
                    $htmltmp .= '<li>'.$question['name'].'</li>';
                }
                $htmltmp .= '</ul></td></tr>';

                foreach($exercise['user'] as $u => $userdata)
                {
                    $htmltmp .= '<tr><td><u>'.$userdata['uname'].'</u></td>';
                    $htmltmp .= ' <td>Moyenne tous essais :  '.number_format(($exercise['user'][$u]['mean'])*100, 2).'%</td>
                    <td>Moyenne dernier essai :  '.number_format(($exercise['avg_last'][$u])*100, 2).'%</td>
                    </tr>';

                    $inc = 1;
                    $htmltmp .= '<tr><td colspan="3">Réponse : <br>';
                    foreach($userdata['mark'] as $p => $papermark)
                    {
                        $htmltmp .= 'Essai '.$inc.' ('.$userdata['start'][$p].' - '.$userdata['end'][$p].') => ';
                        foreach($papermark as $m => $mark)
                        {
                            $htmltmp .= $userdata['question'][$p][$m] .' : '. number_format(($mark)*100, 2) .'%  - ';
                        }
                        $htmltmp .= '<br>';
                        $inc++;
                    }
                    $htmltmp .= '</td></tr>';
                }
                $htmltmp .= '</table>';

                $html .= $htmltmp;
            }
        }

        return $this->render(
            'UJMExoBundle:Paper:testshow2.html.twig', array(
                'dataall'   => $dataall,
                'html'      => $html,
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

    //V2
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

                $row[$exerciseId]['galmeanlast'] = 0;
                foreach($averages as $average)
                {
                    $row[$exerciseId]['avg_last'][$average['user']] = $average['average_mark'];
                    $row[$exerciseId]['galmeanlast'] += $average['average_mark'];
                }
                if (count($averages)> 0)
                    $row[$exerciseId]['galmeanlast'] = $row[$exerciseId]['galmeanlast']/count($averages);

                //exercicse title
                $row[$exerciseId]['exercise'] = $exercise->getTitle();
                //Exercise Directory (to be used as radar label)
                $row[$exerciseId]['directory'] = $exercise->getResourceNode()->getParent()->getName();

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

    public function getResultExercisesJsontestAction($resourcedata='', $userdata='')
    {
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

        $listexolist = array();
        if ($resourcedata != ''){
            //for each group of exercise
            $list = explode(';', $resourcedata);
            //get array of exercise
            foreach ($list as $item){
                if ($item != ''){
                    $listexolist[] = explode(',', $item);
                }
            }
        }

        $jsonall = array();
        $ii = 1;
        foreach($listexolist as $exolist) {
            $datas = $this->resultsAndStatsForExercises($exolist, $userlist);

            // name of group
            $jsonall['label'] = 'Groupe '.$ii; //to be modified : change into directory name (containing exos) or category name (for questions : not interesting) or specific name (into widget setting) ?





            $json = array();
            $json['datasets'] = array();
            $user = array();
            $galmean = array();
            $galmeanraw = array();
            foreach($datas['row'] as $e => $exercice)
            {
//            if (in_array($e, $exolist))
//            {
                //name of exercise
//                $json['labels'][] = $exercice['exercise'];
                $galmean[] = number_format(($exercice['galmean'])*100, 2);
                $galmeanraw[] = $exercice['galmean'];
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
            /*
            foreach($user as $k => $u)
            {
                //display only selected users
                if (in_array($k, $userlist))
                {
                    $json['datasets'][] = $this->setObjectForRadarDataset($u['name'], $u['mean'], $this->rgbacolor($colors[$inc]));
                    $inc++;
                }
            }
            */

            //for each user
            foreach($userlist as $u)
            {
                //is the user in this exolist ?
                if (in_array($u, $user)) {

                }
                //if not, set a null value
                else{

                }
            }

            $ii++;
        }

        return new JsonResponse($json);
    }

    //V3
    /**
     * mean = mean for a user for all tries for all exercises
     * mean_last = mean for a user for last try for all exercises
     * galmean = general mean for all users for all tries for an exercise
     * galmeanlast = general mean for last try for all users for an exercise
     * allgalmean = general mean for all users for all tries for all exercises
     * allgalmeanlast = general mean for last try for all users for all exercises
     *
     * @param array $exolist
     * @param array $userlist
     * @return array
     */
    public function resultsAndStatsForExercisesV3($exolist=array(), $userlist=array())
    {
        $em = $this->getDoctrine()->getManager();

        //get the Exercises entities
        $exercises = $em->getRepository('UJMExoBundle:Exercise')->findById($exolist);
        $nbres = count($exercises);

        $row = array();
        $user = array();
        $directory = array();

        //list of labels for Choice
        $choicetmp = array();

        //for each exercise
        foreach($exercises as $exercise)
        {
            $exerciseId = $exercise->getId();
            //id of directory containing exercise
            $dirId = $exercise->getResourceNode()->getParent()->getId();
            $dirname = $exercise->getResourceNode()->getParent()->getName();

            if (!in_array($dirname, $directory))
            {
                $directory[$dirId] = $dirname;
            }

            //Exercise directory (to be used as radar label)
            $row[$dirId]['dir'][$exerciseId]['directory'] = $dirname;

            //if user is creator of exercice
            if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise))
            {
                //query to get the mean for last try for the exercise
                $averages = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getAverageForExerciseLastTryByUser($exerciseId);

                $row[$dirId]['dir'][$exerciseId]['galmeanlast'] = 0;
                foreach($averages as $average)
                {
                    //mean for last try for a user for an exercise
                    $row[$dirId]['dir'][$exerciseId]['avg_last'][$average['user']] = $average['average_mark'];
                    //mean for last try for a user for all exercises
                    if (!isset($row[$dirId]['mean_last'][$average['user']]))
                    {
                        $row[$dirId]['mean_last'][$average['user']] = $average['average_mark'];
                        $row[$dirId]['mean_lastcount'][$average['user']] = 1;
                    }
                    else
                    {
                        $row[$dirId]['mean_last'][$average['user']] += $average['average_mark'];
                        $row[$dirId]['mean_lastcount'][$average['user']] += 1;
                    }
                    $row[$dirId]['dir'][$exerciseId]['galmeanlast'] += $average['average_mark'];
                }
                if (count($averages)> 0)
                    $row[$dirId]['dir'][$exerciseId]['galmeanlast'] = $row[$dirId]['dir'][$exerciseId]['galmeanlast'] / count($averages);

                //exercise title
                $row[$dirId]['dir'][$exerciseId]['exercise'] = $exercise->getTitle();

                //Query has to be for all users : to compute the general mean
                $exerciseResponses = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('CPASimUSanteExoverrideBundle:Response')
                    ->getExerciseAllResponsesForAllUsersQuery($exerciseId, 'id');

                $tmpmean    = array();
                //mean for user for the exercise
                $mean       = array();
                //general mean for the exercise
                $row[$dirId]['dir'][$exerciseId]['galmean'] = 0;
                $gmean = array('m'=>0, 'c'=>0);
                //responses for an exercise
                foreach ($exerciseResponses as $responses)
                {
                    $paper = $responses->getPaper();
                    //paper_id
                    $paperId = $paper->getId();
                    //user id
                    $uid = $paper->getUser()->getId();
                    //user name
                    $uname = $paper->getUser()->getLastName() . '-' . $paper->getUser()->getFirstName();
                    $user[$uid] = $uname;

                    //mark
                    $mark = $responses->getMark();

                    $row[$dirId]['dir'][$exerciseId]['user'][$uid]['uname'] = $uname;
                    $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mark'][$paperId][] = $mark;
                    $row[$dirId]['dir'][$exerciseId]['user'][$uid]['nbTries'] = $responses->getNbTries(); //not used here
                    $row[$dirId]['dir'][$exerciseId]['user'][$uid]['start'][$paperId] = $paper->getStart()->format('Y-m-d H:i:s');
                    $row[$dirId]['dir'][$exerciseId]['user'][$uid]['end'][$paperId] = $paper->getEnd()->format('Y-m-d H:i:s');

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
                    //question title
                    $row[$dirId]['dir'][$exerciseId]['question'][$questionId]['name'] = $question->getTitle();
                    //list of choices
                    $row[$dirId]['dir'][$exerciseId]['user'][$uid]['question'][$paperId][] = implode(';', $choice);

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
/*
                    foreach ($tmpmean as $uid => $m)
                    {
                        //compute mean for each user
                        if (isset($m['count']))
                        {
                            $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'] = $m['sum']/$m['count'];
                        }
                        else
                        {
                            $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'] = 0;
                        }
                        //general mean for user
                        if (isset($row[$dirId]['mean'][$uid]))
                        {
                            $row[$dirId]['mean'][$uid] += $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'];
                            $row[$dirId]['mean_count'][$uid] += 1;
                        }
                        else
                        {
                            $row[$dirId]['mean'][$uid] = $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'];
                            $row[$dirId]['mean_count'][$uid] = 1;
                        }
                    }
*/
                }

                foreach ($tmpmean as $uid => $m)
                {
                    //compute mean for each user
                    if (isset($m['count']))
                    {
                        $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'] = $m['sum']/$m['count'];
                    }
                    else
                    {
                        $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'] = 0;
                    }
                    //general mean for user
                    if (isset($row[$dirId]['mean'][$uid]))
                    {
                        $row[$dirId]['mean'][$uid] += $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'];
                        $row[$dirId]['mean_count'][$uid] += 1;
                    }
                    else
                    {
                        $row[$dirId]['mean'][$uid] = $row[$dirId]['dir'][$exerciseId]['user'][$uid]['mean'];
                        $row[$dirId]['mean_count'][$uid] = 1;
                    }
                }

                if ($gmean['c'] != 0)
                {
                    $row[$dirId]['dir'][$exerciseId]['galmean'] = $gmean['m']/$gmean['c'];
                }
                else
                {
                    $row[$dirId]['dir'][$exerciseId]['galmean'] = 0;
                }
                //mean for all exercises
                if (!isset($row[$dirId]['allgalmean']))
                {
                    $row[$dirId]['allgalmean']      = $row[$dirId]['dir'][$exerciseId]['galmean'];
                    $row[$dirId]['allgalmeanlast']  = $row[$dirId]['dir'][$exerciseId]['galmeanlast'];
                    $row[$dirId]['galmeancount']    = 1;
                }
                else
                {
                    $row[$dirId]['allgalmean']      += $row[$dirId]['dir'][$exerciseId]['galmean'];
                    $row[$dirId]['allgalmeanlast']  += $row[$dirId]['dir'][$exerciseId]['galmeanlast'];
                    $row[$dirId]['galmeancount']    += 1;
                }
            }
        }

        //Compute means
        if ($nbres > 0)
        {
            $row[$dirId]['allgalmean']      = $row[$dirId]['allgalmean'] / $row[$dirId]['galmeancount'];
            $row[$dirId]['allgalmeanlast']  = $row[$dirId]['allgalmeanlast'] / $row[$dirId]['galmeancount'];
            foreach($row[$dirId]['mean_last'] as $u => $val)
            {
                $row[$dirId]['mean_last'][$u] = $val / $row[$dirId]['mean_lastcount'][$u];
            }
            foreach($row[$dirId]['mean'] as $u => $val)
            {
                $row[$dirId]['mean'][$u] = $val / $row[$dirId]['mean_count'][$u];
            }
        }
       /* else
        {
            $row[$dirId]['allgalmean']      = 0;
            $row[$dirId]['allgalmeanlast']  = 0;
            foreach($row[$dirId]['mean_last'] as $u => $val)
            {
                $row[$dirId]['mean_last'][$u] = 0;
            }
            foreach($row[$dirId]['mean'] as $u => $val)
            {
                $row[$dirId]['mean'][$u]    = 0;
            }
        }*/

        return array(
            'row'       => $row,
            'user'      => $user,
            'directory' => $directory,
        );
    }

    /**
     * V3 - Prepare complete statistics to be displayed
     *
     * @param string $resourcedata
     * @return Response
     */
    public function getResultExercisesHtmlV3Action($resourcedata='')
    {
        $listexolist = $this->getListexolist($resourcedata);

        $html = '';
        $dataall = array();

        //to associate the names
        $user = array();

        foreach($listexolist as $exolist)
        {
            $datas = $this->resultsAndStatsForExercisesV3($exolist);
            $dataall[] = $datas;
            $htmltmp = '';

            //the exercises, grouped by directory
            foreach($datas['row'] as $dirid => $exerciselist)
            {
                //each exercise in a directory
                foreach($exerciselist['dir'] as $e => $exercise)
                {
                    $directoryname =  $exercise['directory'];
                    $htmltmp .= '<tr><th><b>'.$exercise['exercise'].'</b></th>';
                    $htmltmp .= '<th>Moyenne générale : '.number_format(($exercise['galmean'])*100, 2).'%<br> = Moyenne tous essais pour tous utilisateurs</th>';
                    $htmltmp .= '<th>Moyenne dernier essai : '.number_format(($exercise['galmeanlast'])*100, 2).'%</th></tr>';

                    $htmltmp .= '<tr><td colspan="3">Questions : <ul>';
                    foreach($exercise['question'] as $question)
                    {
                        $htmltmp .= '<li>'.$question['name'].'</li>';
                    }
                    $htmltmp .= '</ul></td></tr>';

                    foreach($exercise['user'] as $u => $userdata)
                    {
                        $user[$u] = $userdata['uname'];
                        $htmltmp .= '<tr><td><u>'.$userdata['uname'].'</u></td>';
                        $htmltmp .= ' <td>Moyenne tous essais :  '.number_format(($exercise['user'][$u]['mean'])*100, 2).'%</td>
                    <td>Moyenne dernier essai :  '.number_format(($exercise['avg_last'][$u])*100, 2).'%</td>
                    </tr>';

                        $inc = 1;
                        $htmltmp .= '<tr><td colspan="3">Réponse : <br>';
                        foreach($userdata['mark'] as $p => $papermark)
                        {
                            $htmltmp .= 'Essai '.$inc.' ('.$userdata['start'][$p].' - '.$userdata['end'][$p].') => ';
                            foreach($papermark as $m => $mark)
                            {
                                $htmltmp .= $userdata['question'][$p][$m] .' : '. number_format(($mark)*100, 2) .'%  - ';
                            }
                            $htmltmp .= '<br>';
                            $inc++;
                        }
                        $htmltmp .= '</td></tr>';
                    }
                }
                //Display mean
                $mean = '';
                foreach($exerciselist['mean'] as $u => $val)
                {
                    $mean .= '<tr><td><u>'.$user[$u] . '</u></td><td>' . number_format(($val)*100, 2).'%</td>'.
                    '<td>' . number_format(($exerciselist['mean_last'][$u])*100, 2).'%</td></tr>';
                }
                $meanlast='';
                /*
                //Display mean
                $mean = '<td></td><td><b>Moyenne tous essais</b> : <br>';
                foreach($exerciselist['mean'] as $u => $val)
                {
                    $mean .= '<u>'.$user[$u] . '</u> : ' . number_format(($val)*100, 2).'%<br>';
                }
                $mean .= '</td>';

                //Display meanlast
                $meanlast = '<td><b>Moyenne dernier essai</b> : <br>';
                foreach($exerciselist['mean_last'] as $u => $val)
                {
                    $meanlast .= '<u>'.$user[$u] . '</u> : ' . number_format(($val)*100, 2) . '%<br>';
                }
                $meanlast .= '</td>';
/*
                $htmltmp = '<table class="table table-responsive"><tr><th><b>'.$directoryname.'</b></th>'.
                    '<th><b>Moyenne générale tous essais</b>: '.number_format(($datas['row'][$dirid]['allgalmean'])*100, 2).'%</th>'.
                    '<th><b>Moyenne générale dernier essais</b>: '.number_format(($datas['row'][$dirid]['allgalmeanlast'])*100, 2).'%</th>'.
                    '</tr>'.$mean.$meanlast.
                    $htmltmp.
                    '</table>';
*/
                $htmltmp = '<table class="table table-responsive"><tr><th colspan="3"><b>'.$directoryname.'</b></th></tr>'.
                    '<tr><th></th><th><b>Moyenne générale tous essais</b></th><th><b>Moyenne générale dernier essais</b></th></tr>'.
                    '<tr><td>Groupe</td><td>'.number_format(($datas['row'][$dirid]['allgalmean'])*100, 2).'%</td><td>'.number_format(($datas['row'][$dirid]['allgalmeanlast'])*100, 2).'%</td><tr>'.
                    $mean.$meanlast.
                    $htmltmp.
                    '</table>';

                $html .= $htmltmp;
            }
        }
        return new JsonResponse($html);
    }

    /**
     * Export CSV 3
     */
    public function getResultExercisesCsvV3Action($resourcedata='')
    {
        $listexolist = $this->getListexolist($resourcedata);

        $date = new \DateTime();
        $now = $date->format('Y-m-d-His');
        //TODO : repasser les csv dans un array general et mettre dans exportResCompleteCSVAction
        //TODO : pour n'avoir qu'une boucle

        $handle = fopen('php://memory', 'r+');

        foreach($listexolist as $exolist)
        {
            $datas = $this->resultsAndStatsForExercisesV3($exolist);

            foreach($datas['row'] as $dirid => $exerciselist)
            {
                //Row 1 : directory name
                $csv = array();
                $csv[] =  'Nom';
                $csv[] =  $datas['directory'][$dirid];
                fputcsv($handle, $csv);

                $csv = array();
                $csv[] =  '';
                $csv[] =  'Moyenne tous essais';
                $csv[] =  'Moyenne dernier essai';
                fputcsv($handle, $csv);

                $csv = array();
                $csv[] =  'Groupe';
                $csv[] =  number_format(($datas['row'][$dirid]['allgalmean'])*100, 2);
                $csv[] =  number_format(($datas['row'][$dirid]['allgalmeanlast'])*100, 2);
                fputcsv($handle, $csv);

                foreach($exerciselist['mean'] as $u => $val)
                {
                    $csv = array();
                    $csv[] =  $datas['user'][$u];
                    $csv[] =  number_format(($val)*100, 2);
                    $csv[] =  number_format(($exerciselist['mean_last'][$u])*100, 2);
                    fputcsv($handle, $csv);
                }
/*
                //mean for all tries for group
                $csv = array();
                $csv[] =  'Moyenne tous essais';
                $csv[] =  'Groupe';
                $csv[] =  number_format(($datas['row'][$dirid]['allgalmean'])*100, 2);
                fputcsv($handle, $csv);

                //mean for all tries for users
                foreach($exerciselist['mean'] as $u => $val)
                {
                    $csv = array();
                    $csv[] =  '';
                    $csv[] =  $datas['user'][$u];
                    $csv[] =  number_format(($val)*100, 2);
                    fputcsv($handle, $csv);
                }
                fputcsv($handle, $csv);

                //mean for last try for group
                $csv = array();
                $csv[] =  'Moyenne dernier essai';
                $csv[] =  'Groupe';
                $csv[] =  number_format(($datas['row'][$dirid]['allgalmeanlast'])*100, 2);
                fputcsv($handle, $csv);

                //mean for last try for users
                foreach($exerciselist['mean_last'] as $u => $val)
                {
                    $csv = array();
                    $csv[] =  '';
                    $csv[] =  $datas['user'][$u];
                    $csv[] =  number_format(($val)*100, 2);
                    fputcsv($handle, $csv);
                }
*/
                foreach($exerciselist['dir'] as $eid => $exercise)
                {
/*                    //Row 1 : directory name
                    $csv = array();
                    $csv[] =  '';
                    $csv[] =  'Nom';
                    $csv[] =  $exercise['directory'];
                    fputcsv($handle, $csv);
*/
                    //Row 2 :
                    $csv = array();
                    $csv[] =  '';
                    $csv[] =  '';
                    $csv[] =  '';
                    //exercise name
                    $csv[] = $exercise['exercise'];
                    $csv[] = '';
                    $csv[] = '';

                    //questions name
                    foreach($exercise['question'] as $question)
                    {
                        $csv[] = $question['name'];
                    }
                    fputcsv($handle, $csv);

                    //Row 3 :
                    $csv = array();
                    $csv[] =  '';
                    $csv[] =  '';
                    $csv[] =  '';
                    //general mean
                    $csv[] = 'Moyenne Générale';
                    $csv[] = number_format(($exercise['galmean'])*100, 2);
                    fputcsv($handle, $csv);

                    //Row 4 + : all users
                    foreach($exercise['user'] as $u => $userdata)
                    {
                        //row 4n :
                        $csv = array();
                        $csv[] = '';
                        $csv[] = '';
                        $csv[] = '';
                        //username
                        $csv[] = $exercise['user'][$u]['uname'];
                        fputcsv($handle, $csv);

                        //row 4n+1 :
                        $csv = array();
                        $csv[] = '';
                        $csv[] = '';
                        $csv[] = '';
                        $csv[] = 'Moyenne tous essais';
                        $csv[] = number_format(($exercise['user'][$u]['mean'])*100, 2);
                        fputcsv($handle, $csv);

                        //row 4n+2 :
                        $csv = array();
                        $csv[] = '';
                        $csv[] = '';
                        $csv[] = '';
                        $csv[] = 'Moyenne dernier essai';
                        $csv[] = number_format(($exercise['avg_last'][$u])*100, 2);
                        fputcsv($handle, $csv);

                        //row 4n+3 + : responses
                        $incr = 1;
                        foreach($userdata['question'] as $pid => $paperresponse)
                        {
                            $csv = array();
                            $csv[] = '';
                            $csv[] = '';
                            $csv[] = '';
                            $csv[] = '';
                            $csv[] = '';
                            $csv[] = 'Essai '.$incr. ' ('.$userdata['start'][$pid].' - '.$userdata['end'][$pid]. ')';
                            foreach($paperresponse as $response)
                            {
                                $csv[] = $response;
                            }
                            fputcsv($handle, $csv);
                            $incr++;
                        }
                    }
                    $csv = array();
                    fputcsv($handle, $csv);
                }
                $csv = array();
                fputcsv($handle, $csv);
            }
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
     * V3 JSON
     */
    public function callJsonV3Action()
    {
        return $this->render('UJMExoBundle:Paper:testshowradar_v3.html.twig', array() );
    }

    public function displayWindowAction($wid = 0, $resourcedata='', $userdata='')
    {
        return $this->render('UJMExoBundle:Paper:displayRadarData.html.twig', array(
            'wid'           => $wid,
            'resourcelist'  => $resourcedata,
            'userlist'      => $userdata
        ));
    }

    public function getResultExercisesJsonV3Action($resourcedata='', $userdata='')
    {
        $userlist = ($userdata == '') ? array() : explode(',', $userdata);
/*
        if ($userdata != '')
        {
            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $qb->select('partial u.{id, firstName, lastName}')
                ->from('Claroline\CoreBundle\Entity\User', 'u')
                ->andWhere('u.id IN (:ud)')
                ->setParameter('ud', $userlist);
            $usernames = $qb->getQuery()->getScalarResult();
            //$em->createQuery("SELECT u.id, u.firstName, u.lastName FROM Claroline\CoreBundle\Entity\User u WHERE u.id IN (".$userdata.")")->getScalarResult();
        }
*/
        //to rgb
        $colors = array_map(array($this, 'rgb2hex'), $this::$RGBCOLORS);

        $listexolist = $this->getListexolist($resourcedata);

        $json = array();
        $json['datasets'] = array();
        $user = array();
        $allgalmeanlast = array();
        $allgalmean = array();
        $usernames = array();

        foreach($listexolist as $exolist)
        {
            $datas = $this->resultsAndStatsForExercisesV3($exolist, $userlist);
            $uu = array_keys($datas['user']);

            foreach($datas['row'] as $dirid => $exerciselist)
            {
                //name of branch : name (containing exos) or category name (for questions : not interesting) or specific name (into widget setting) ?
                $json['labels'][] = $datas['directory'][$dirid];
                //stat for last
                $allgalmeanlast[] = number_format(($exerciselist['allgalmeanlast'])*100, 2);
                //stat for all
                $allgalmean[] = number_format(($exerciselist['allgalmean'])*100, 2);

                //array of user without data
                $userlisttmp = $userlist;
                //Display mean (use $exerciselist['mean_last'] instead, for last)
                foreach($exerciselist['mean_last'] as $u => $val)
                {
                    if (in_array($u, $userlist))
                    {
                        $usernames[$u] = $datas['user'][$u];
                        $user[$u]['name'] = $datas['user'][$u];
                        $user[$u]['mean'][] = number_format(($val)*100, 2);
                        //TODO : remove this hacky shit ! save the realname for later
                        $user[$u]['nameok'] = true;
                        //remove user
                        array_diff($userlisttmp, [$u]);
                    }
                }

                //put 0 for absence of data for a user
                foreach($userlist as $u)
                {
                    if (!in_array($u, $uu))
                    {
                        $user[$u]['name'] = $u;
                        $user[$u]['mean'][] = 0;
                        $user[$u]['nameok'] = false;
                    }
                }

            }
        }
//return new JsonResponse($usernames);die();

        //prepare data for json
        $inc = 0;
        //dataset for group
        $json['datasets'][] = $this->setObjectForRadarDataset('group', $allgalmean, $this->rgbacolor($colors[$inc]));
        $inc++;

        //datasets for users
        foreach($user as $uid => $ud)
        {
            //display only selected users
            if (in_array($uid, $userlist))
            {
                $name = ($ud['nameok']) ? $ud['name'] : $usernames[$uid];
                $json['datasets'][] = $this->setObjectForRadarDataset($name, $ud['mean'], $this->rgbacolor($colors[$inc]));
                $inc++;
            }
        }

        return new JsonResponse($json);
    }
}