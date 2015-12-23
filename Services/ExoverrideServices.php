<?php

namespace CPASimUSante\ExoverrideBundle\Services;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;
use Claroline\CoreBundle\Persistence\ObjectManager;

class ExoverrideServices
{
    protected $om;

    /**
     * Constructor
     *
     * @access public
     *
     * @param \Claroline\CoreBundle\Persistence\ObjectManager $om Dependency Injection
     *
     */
    public function __construct(
        ObjectManager $om
    )
    {
        $this->om = $om;
    }

    /**
     * Return the list of exercise corresponding to resource nodes
     *
     * @access public
     *
     * @param string list of resource nodes
     *
     * @return Object
     */
    public function getExoList($resourcelist= '')
    {
        $resources = ($resourcelist == '') ? array() : explode(',', $resourcelist);
        return $this->om
                       ->getRepository('UJMExoBundle:Exercise')
                       ->findByResourceNode($resources);
    }

    /**
     * Query the correct Exercises for access rights to the resource
     *
     * Resources of type exercise
     * WHERE workspace = `$wid`
     * AND all questions are
     *      of interaction type 'interactionQCM'
     *      AND of QCM type= 2 (QCU)
     *      AND in categories $cids
     *
     * @param integer $wid
     * @param array $cids
     * @return mixed
     */
    public function getFilteredExercise($wid, $cids)
    {
        /*
        //regular SQL
        $dql = "
            SELECT DISTINCT rn.* FROM claro_resource_node AS rn
            JOIN claro_resource_type AS rt
                ON rn.resource_type_id = rt.id
            LEFT JOIN ujm_exercise AS ex
            ON rn.id = ex.resourceNode_id
            LEFT JOIN ujm_exercise_question AS exq
            ON ex.id = exq.exercise_id
            WHERE rn.workspace_id = :wid
            AND rt.name = 'ujm_exercise'
            AND exq.question_id IN (
                SELECT qu.id
                FROM ujm_question AS qu
                   LEFT JOIN ujm_interaction AS i
                   ON qu.id = i.question_id
                   LEFT JOIN ujm_interaction_qcm AS iqcm
                   ON i.id = iqcm.interaction_id
                WHERE i.type = 'interactionQCM'
                AND iqcm.type_qcm_id = 2
                AND qu.category_id IN (:cid)
            )
        ";
        */
        $eol = PHP_EOL;
        $category = ($cids == array()) ? "" : "AND question.category_id IN (:cids) {$eol}";
        $dql = "
            SELECT DISTINCT resourceNode FROM Claroline\CoreBundle\Entity\Resource\ResourceNode resourceNode {$eol}
            JOIN Claroline\CoreBundle\Entity\Resource\ResourceType resourceType {$eol}
            LEFT JOIN UJM\ExoBundle\Entity\Exercise exercise {$eol}
            LEFT JOIN UJM\ExoBundle\Entity\ExerciseQuestion exerciseQuestion {$eol}
            WHERE resourceNode.workspace_id = :wid {$eol}
            AND resourceType.name = 'ujm_exercise' {$eol}
            AND exerciseQuestion.question_id IN ( {$eol}
                SELECT question.id {$eol}
                FROM UJM\ExoBundle\Entity\Question question {$eol}
                   LEFT JOIN UJM\ExoBundle\Entity\Interaction interaction {$eol}
                   LEFT JOIN UJM\ExoBundle\Entity\InteractionQCM interactionQCM {$eol}
                WHERE interaction.type = 'interactionQCM' {$eol}
                AND interactionQCM.typeQCM = 2 {$eol}
                {$category}
            )
        ";
        $query = $this->om->createQuery($dql);
        $query->setParameter('wid', $wid);
        $query->setParameter('cids', $cids);
        return $query->getResult();
    }
}
