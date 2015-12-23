<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Claroline\CoreBundle\Library\Resource\ResourceCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExoverrideBaseController extends BaseController
{
    /**
     * @param string $permission
     *
     * @param Wiki $wiki
     *
     * @throws AccessDeniedException
     */
    protected function checkAccess($permission, Wiki $wiki)
    {

        $collection = new ResourceCollection(array($wiki->getResourceNode()));

        if (!$this->get('security.authorization_checker')->isGranted($permission, $collection)) {
            throw new AccessDeniedException($collection->getErrorsForDisplay());
        }
    }

    /**
     * @param string $permission
     *
     * @param Wiki $wiki
     *
     * @return bool
     */
    protected function isUserGranted($permission, Wiki $wiki, $collection = null)
    {
        if ($collection === null) {
            $collection = new ResourceCollection(array($wiki->getResourceNode()));
        }
        $checkPermission = false;
        if ($this->get('security.authorization_checker')->isGranted($permission, $collection)) {
            $checkPermission = true;
        }

        return $checkPermission;
    }
}