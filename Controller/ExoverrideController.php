<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;

class ExoverrideController extends Controller
{
    /**
     * @EXT\Route("/index", name="cpasimusante_exoverride_index")
     * @EXT\Template
     * @return Response
     * @throws \Exception
     */
    public function indexAction()
    {
        throw new \Exception('hello');
    }
}
