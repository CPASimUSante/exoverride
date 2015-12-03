<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CPASimUSante\ExoverrideBundle\Listener;

use Claroline\CoreBundle\Event\DisplayWidgetEvent;
use Claroline\CoreBundle\Event\ConfigureWidgetEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service()
 */
class ExoverrideWidgetListener
{
    private $httpKernel;
    private $request;

    /**
     * @DI\InjectParams({
     *  "requestStack"      = @DI\Inject("request_stack"),
     *  "httpKernel"        = @DI\Inject("http_kernel"),
     * })
     */
    public function __contruct(
        requestStack $requestStack,
        HttpKernelInterface $httpKernel
    )
    {
        $this->httpKernel   = $httpKernel;
        $this->request      = $requestStack->getCurrentRequest();
    }

    /**
     * Listener to the widget display
     *
     * @DI\Observe("widget_exo_stat")
     * @param DisplayWidgetEvent $event
     */
    public function onDisplay(DisplayWidgetEvent $event)
    {
        $widgetInstance = $event->getInstance();
        $params = array();
        $params['_controller'] = 'CPASimUSanteExoverrideBundle:ExoverrideWidget:userStatDisplay';
        $params['widgetInstance'] = $widgetInstance->getId();
        $this->redirect($params, $event);
    }

    /**
     * Widget configuration
     * @DI\Observe("widget_exo_stat_configuration")
     * @param ConfigureWidgetEvent $event
     */
    public function onConfigure(ConfigureWidgetEvent $event)
    {
        $widgetInstance = $event->getInstance();
        $params = array();
        $params['_controller'] = 'CPASimUSanteExoverrideBundle:ExoverrideWidget:userStatWidgetConfigureForm';
        $params['widgetInstance'] = $widgetInstance->getId();
        $this->redirect($params, $event);
    }

    /**
     * call to controller method. Send param & event
     * @param $params
     * @param $event
     */
    private function redirect($params, $event)
    {
        // ???
        $subRequest = $this->request->duplicate(array(), null, $params);
        //Create a response
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        //fill the event with the content
        $event->setContent($response->getContent());
        $event->stopPropagation();
    }
}