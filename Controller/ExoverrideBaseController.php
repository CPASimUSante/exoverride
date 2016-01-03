<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UJM\ExoBundle\Entity\Exercise;

class ExoverrideBaseController extends BaseController
{
    /**
     * @param string $permission
     *
     * @param Exercise $exo
     *
     * @throws AccessDeniedException
     */
    protected function checkAccess($permission, Exercise $exo)
    {
        $collection = new ResourceCollection(array($exo->getResourceNode()));

        if (!$this->get('security.authorization_checker')->isGranted($permission, $collection)) {
            throw new AccessDeniedException($collection->getErrorsForDisplay());
        }
    }

    /**
     * @param string $permission
     *
     * @param Exercise $exo
     *
     * @return bool
     */
    protected function isUserGranted($permission, Exercise $exo, $collection = null)
    {
        if ($collection === null) {
            $collection = new ResourceCollection(array($exo->getResourceNode()));
        }
        $checkPermission = false;
        if ($this->get('security.authorization_checker')->isGranted($permission, $collection)) {
            $checkPermission = true;
        }

        return $checkPermission;
    }
}