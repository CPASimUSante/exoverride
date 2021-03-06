<?php

namespace CPASimUSante\ExoverrideBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UJM\ExoBundle\Repository\ResponseRepository as BaseRepository;

/**
 * PaperRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ResponseRepository extends BaseRepository
{
    public function getQuerySomeExercisesAllResponsesForAllUsers($exercises=array())
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('r')
            ->from('UJM\\ExoBundle\\Entity\\Response', 'r')
            ->join('r.paper', 'p')
            ->join('p.exercise', 'e')
            ->where('e.id IN (?1)')
            ->andWhere('p.interupt =  ?2')
//            ->orderBy($order, 'ASC')
            ->setParameters(array(1 => $exercises, 2 => 0));
        return $qb->getQuery();
    }
    public function getSomeExerciseAllResponsesForAllUsersIterator($exercises)
    {
        return $this->getQuerySomeExercisesAllResponsesForAllUsers($exercises)->iterate();
    }

    public function getSomeExerciseAllResponsesForAllUsers($exercises)
    {
        return $this->getQuerySomeExercisesAllResponsesForAllUsers($exercises)->getResult();
    }

    public function getSomeExerciseAllResponsesForAllUsersAsArray($exercises)
    {
        return $this->getQuerySomeExercisesAllResponsesForAllUsers($exercises)->getArrayResult();
    }

    /**
     *
     * @return array
     */
    public function getQueryExerciseAllResponsesForAllUsers($exoId, $order)
    {

        $qb = $this->_em->createQueryBuilder();
        $qb->select('r')
            ->from('UJM\\ExoBundle\\Entity\\Response', 'r')     //thus, avoid problem with "overriding" Response entity in ExoverrideBundle
            ->join('r.paper', 'p')
            ->join('p.exercise', 'e')
            ->where('e.id = ?1')
            ->andWhere('p.interupt =  ?2')
//            ->orderBy($order, 'ASC')
            ->setParameters(array(1 => $exoId, 2 => 0));
        return $qb->getQuery();

/*SELECT * FROM ujm_response AS r JOIN ujm_paper as p ON r.paper_id = p.id JOIN ujm_exercise as e ON e.id = p.exercise_id where e.id=6
        $qb = $this->createQueryBuilder('r');
        $qb->join('r.paper', 'p')
            ->join('p.exercise', 'e')
            ->where('e.id = ?1')
            ->andWhere('p.interupt =  ?2')
//            ->orderBy($order, 'ASC')
            ->setParameters(array(1 => $exoId, 2 => 0));
        return $qb->getQuery();
*/
    }

    public function getAverageForExerciseByUser($exoId)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('AVG(r.mark) as average_mark')
            ->addSelect('IDENTITY(p.user) as user')             //IDENTITY needed because user is a FK
            ->from('UJM\\ExoBundle\\Entity\\Response', 'r')     //thus, avoid problem with "overriding" Response entity in ExoverrideBundle
            ->join('r.paper', 'p')
            ->join('p.exercise', 'e')
            ->where('e.id = ?1')
            ->andWhere('p.interupt =  ?2')
            ->groupBy('p.user')
            ->setParameters(array(1 => $exoId, 2 => 0));
        return $qb->getQuery()->getResult();
    }

    public function getAverageForExerciseLastTryByUser($exoId)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('AVG(r.mark) as average_mark')
            ->addSelect('IDENTITY(p.user) as user')             //IDENTITY needed because user is a FK
            ->from('UJM\\ExoBundle\\Entity\\Response', 'r')     //thus, avoid problem with "overriding" Response entity in ExoverrideBundle
            ->join('r.paper', 'p')
            ->join('p.exercise', 'e')
            ->where('e.id = ?1')
            ->andWhere('p.interupt =  ?2')
            ->andWhere(
               $qb->expr()->in(
                   'p.id',
                   $this->_em->createQueryBuilder()->select('MAX(p2.id)')
                       ->from('UJM\\ExoBundle\\Entity\\Paper', 'p2')
                       ->where('p2.exercise= ?1')
                       ->groupBy('p2.user')
                       ->getDQL()
               ))
            ->groupBy('p.user')
            ->setParameters(array(1 => $exoId, 2 => 0));
        return $qb->getQuery()->getResult();
    }

    public function getExerciseAllResponsesForAllUsersIterator($exoId, $order)
    {
        return $this->getQueryExerciseAllResponsesForAllUsers($exoId, $order)->iterate();
    }

    public function getExerciseAllResponsesForAllUsersQuery($exoId, $order)
    {
        return $this->getQueryExerciseAllResponsesForAllUsers($exoId, $order)->getResult();
    }
}
