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

        if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise)) {
            $iterableResult = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Paper')
                ->getExerciseAllPapersIterator($exerciseId);
            while (false !== ($row = $iterableResult->next())) {
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
            }
        } else {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }
        return $results;
    }

    public function exportResCompleteCSVAction($exerciseId)
    {
        $em = $this->getDoctrine()->getManager();
        $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($exerciseId);

        if ($this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise)) {
            $iterableResult = $this->getDoctrine()
                ->getManager()
                ->getRepository('UJMExoBundle:Paper')
                ->getExerciseAllPapersIterator($exerciseId);
            $handle = fopen('php://memory', 'r+');

            while (false !== ($row = $iterableResult->next())) {
                $rowCSV = array();
                $infosPaper = $this->container->get('ujm.exercise_services')->getInfosPaper($row[0]);
                $score = $infosPaper['scorePaper'] / $infosPaper['maxExoScore'];
                $score = $score * 20;

                $rowCSV[] = $row[0]->getUser()->getLastName() . '-' . $row[0]->getUser()->getFirstName();
                $rowCSV[] = $row[0]->getNumPaper();
                $rowCSV[] = $row[0]->getStart()->format('Y-m-d H:i:s');
                if ($row[0]->getEnd()) {
                    $rowCSV[] = $row[0]->getEnd()->format('Y-m-d H:i:s');
                } else {
                    $rowCSV[] = $this->get('translator')->trans('no_finish');
                }
                $rowCSV[] = $row[0]->getInterupt();
                $rowCSV[] = $this->container->get('ujm.exercise_services')->roundUpDown($score);

                fputcsv($handle, $rowCSV);
                $em->detach($row[0]);
            }

            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            $date = new \DateTime();
            $now = $date->format('Ymd-His');
            return new Response($content, 200, array(
                'Content-Type' => 'application/force-download',
                'Content-Disposition' => 'attachment; filename="exportall-'.$now.'.csv"'
            ));

        } else {

            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
        }
    }
}