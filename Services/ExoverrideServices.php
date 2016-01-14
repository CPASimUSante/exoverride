<?php

namespace CPASimUSante\ExoverrideBundle\Services;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Claroline\CoreBundle\Manager\WorkspaceManager;
use Claroline\CoreBundle\Manager\RoleManager;

class ExoverrideServices
{
    private $om;
    private $tokenStorage;
    private $wsManager;
    private $roleManager;

    /**
     * Constructor
     *
     * @access public
     *
     * @param \Claroline\CoreBundle\Persistence\ObjectManager $om Dependency Injection
     *
     */
    public function __construct(
        ObjectManager $om,
        TokenStorageInterface $tokenStorage,
        WorkspaceManager $wsManager,
        RoleManager $roleManager
    )
    {
        $this->om               = $om;
        $this->tokenStorage     = $tokenStorage;
        $this->wsManager        = $wsManager;
        $this->roleManager      = $roleManager;
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
/*
DELETE FROM `cpasimusante__exoverride_stat_configuration_data`;
DELETE FROM `cpasimusante__exoverride_stat_configuration`;
 */
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

    /**
     * return list of ws where the user can access this bundle
     *
     * @return array
     */
    public function userCanAccessWs()
    {
        //get ws the user has access to
        $user = $this->tokenStorage->getToken()->getUser();
        $workspaces = $this->wsManager->getWorkspacesByUser($user);
        $wsids = array();
        foreach($workspaces as $ws)
        {
            $wsids[] = $ws->getId();
        }
        //get ws the bundle is linked to (from the bundle MainConfig)
        $awsids = array();
        $authorizedWs = $this->om
            ->getRepository('CPASimUSanteExoverrideBundle:MainConfig')->findAll();
        if (isset($authorizedWs[0]))
        {
            $authorizedWsItems = $authorizedWs[0]->getItems();
            foreach($authorizedWsItems as $authorizedWsItem)
            {
                $awsids[] = $authorizedWsItem->getWorkspace()->getId();
            }
        }
        return array_intersect($wsids, $awsids);
    }

    /**
     * list of user role by ws
     * @return array
     */
    public function userHasRole()
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $roles = $this->roleManager->getWorkspaceRolesByUser($user);
        $rolelist = array();
        foreach($roles as $role)
        {
            $rolelist[$role->getWorkspace()->getId()][] = $role->getTranslationKey();
        }

        return $rolelist;
    }
}
