<?php

namespace CPASimUSante\ExoverrideBundle\Listener;

use Claroline\CoreBundle\Event\PluginOptionsEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExoverrideListener extends ContainerAware
{
    /**
     * @param PluginOptionsEvent $event
     */
    public function onAdministrate(PluginOptionsEvent $event)
    {
        $requestStack = $this->container->get('request_stack');
        $httpKernel = $this->container->get('http_kernel');
        $request = $requestStack->getCurrentRequest();
        $params = array('_controller' => 'CPASimUSanteExoverrideBundle:MainConfig:adminOpen');
        $subRequest = $request->duplicate(array(), null, $params);
        $response = $httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
