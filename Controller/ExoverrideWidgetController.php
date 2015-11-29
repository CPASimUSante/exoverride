<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Claroline\CoreBundle\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Claroline\CoreBundle\Persistence\ObjectManager;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExoverrideWidgetController extends Controller
{
    private $om;
    private $formFactory;
    private $userManager;
    private $request;
    /**
     * @DI\InjectParams({
     *     "om"                    = @DI\Inject("claroline.persistence.object_manager"),
     *     "formFactory"           = @DI\Inject("form.factory"),
     *     "userManager"           = @DI\Inject("claroline.manager.user_manager"),
     *     "requestStack"          = @DI\Inject("request_stack"),
     * })
     */
    public function __construct(
        ObjectManager $om,
        FormFactory $formFactory,
        UserManager $userManager,
        RequestStack $requestStack
    )
    {
        //Object manager initialization
        $this->om                = $om;
        $this->formFactory       = $formFactory;
        $this->userManager       = $userManager;
        $this->request           = $requestStack->getCurrentRequest();
    }

    /******************
     * Widget methods *
     ******************/

    /**
     * Called on onDisplay Listener method
     *
     * @EXT\Route(
     *     "/statwidget/{widgetInstance}",
     *     name="cpasimusante_statwidget",
     *     options={"expose"=true}
     * )
     * @EXT\Template("CPASimUSanteExoverrideBundle:Widget:statWidgetDisplay.html.twig")
     */
    public function userStatDisplayAction(WidgetInstance $widgetInstance)
    {
        return array('widgetInstance' => $widgetInstance);
    }

    /**
     * Called on onConfigure Listener method
     *
     * @param WidgetInstance $widgetInstance
     * @return array    AJAX response
     *
     * @EXT\Route(
     *     "/configwidget/{widgetInstance}",
     *     name="cpasimusante_statwidget_config",
     *     options={"expose"=true}
     * )
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     * @EXT\Template("CPASimUSanteExoverrideBundle:Widget:statWidgetConfigure.html.twig")
     */
    public function userStatWidgetConfigureFormAction(WidgetInstance $widgetInstance)
    {
        return array('widgetInstance' => $widgetInstance);
    }
}