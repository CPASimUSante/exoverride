<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Claroline\CoreBundle\Manager\UserManager;
use CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfig;
use CPASimUSante\ExoverrideBundle\Form\ExoverrideStatConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

class ExoverrideWidgetController extends Controller
{
    private $tokenStorage;
    private $formFactory;
    private $userManager;
    private $request;

    /**
     * @DI\InjectParams({
     *     "tokenStorage"          = @DI\Inject("security.token_storage"),
     *     "formFactory"           = @DI\Inject("form.factory"),
     *     "userManager"           = @DI\Inject("claroline.manager.user_manager"),
     *     "requestStack"          = @DI\Inject("request_stack"),
     * })
     * @param FormFactory $formFactory
     * @param UserManager $userManager
     * @param RequestStack $requestStack
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        FormFactory $formFactory,
        UserManager $userManager,
        RequestStack $requestStack
    )
    {
        //Object manager initialization
        $this->tokenStorage      = $tokenStorage;
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
        $em = $this->get('doctrine.orm.entity_manager');
        $widgetExoverrideRadar = $em->getRepository('CPASimUSanteExoverrideBundle:ExoverrideStatConfig')
            ->findOneByWidgetInstance($widgetInstance);
        //parameters needed to display graph
        if ($widgetExoverrideRadar !== null)
        {
            $userlist      = $widgetExoverrideRadar->getUserlist();
            $resourcelist  = $widgetExoverrideRadar->getExolist();
        }
        else
        {
            $userlist      = '';
            $resourcelist  = '';
        }
        $userCanAccessWs = $this->userCanAccessWs();

        return array(
            'widgetInstance'    => $widgetInstance,
            'userlist'          => $userlist,
            'resourcelist'      => $resourcelist,
            'userCanAccessWs'   => $userCanAccessWs,
        );
    }

    public function userCanAccessWs()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        //get ws the user has access to
        $user = $this->tokenStorage->getToken()->getUser();
        $manager = $this->get('claroline.manager.workspace_manager');
        $workspaces = $manager->getWorkspacesByUser($user);
        $wsids = array();
        foreach($workspaces as $ws)
        {
            $wsids[] = $ws->getId();
        }
        //get ws the bundle is linked to (from the bundle MainConfig)
        $awsids = array();
        $authorizedWs = $em->getRepository('CPASimUSanteExoverrideBundle:MainConfig')->findAll();
        $authorizedWsItems = $authorizedWs[0]->getItems();
        foreach($authorizedWsItems as $authorizedWsItem)
        {
            $awsids[] = $authorizedWsItem->getWorkspace()->getId();
        }
        return array_intersect($wsids, $awsids);
    }

    /**
     * Called on onConfigure Listener method
     *
     * @param WidgetInstance $widgetInstance
     * @return array    AJAX response
     *
     * @EXT\ParamConverter("authenticatedUser", options={"authenticatedUser" = true})
     */
    public function userStatWidgetConfigureFormAction(WidgetInstance $widgetInstance, Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('edit', $widgetInstance)) {
            throw new AccessDeniedException();
        }
        $userCanAccessWs = $this->userCanAccessWs();
        if ($userCanAccessWs  == array())
        {
            return $this->render(
                'CPASimUSanteExoverrideBundle:Widget:statWidgetConfigure.html.twig',
                array(
                    'userCanAccessWs'   => $userCanAccessWs
                )
            );
        }
        $em = $this->get('doctrine.orm.entity_manager');

        $widgetExoverrideRadar = $em->getRepository('CPASimUSanteExoverrideBundle:ExoverrideStatConfig')
            ->findOneByWidgetInstance($widgetInstance);

        if (null === $widgetExoverrideRadar) {
            $widgetExoverrideRadar = new ExoverrideStatConfig();
            $widgetExoverrideRadar
                ->setWidgetInstance($widgetInstance);
        }

        $userlist = $widgetExoverrideRadar->getUserList();
        $resourcelist = $widgetExoverrideRadar->getResourcelist();

        $form = $this->formFactory->create(new ExoverrideStatConfigType(), $widgetExoverrideRadar);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $widgetExoverrideRadar = $form->getData();
            //Need the exercise list corresponding to resource list to persist
            //in order to avoid having a request each time
            $services = $this->container->get('cpasimusante.exoverride_services');
            $exercices = $services->getExoList($widgetExoverrideRadar->getResourcelist());
            $list = array();
            foreach ($exercices as $exos)
            {
                $list[] = $exos->getId();
            }
            $exolist = implode(',', $list);
            $widgetExoverrideRadar->setExolist($exolist);
            $em->persist($widgetExoverrideRadar);
            $em->flush();
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        return $this->render(
            'CPASimUSanteExoverrideBundle:Widget:statWidgetConfigure.html.twig',
            array(
                'form'              => $form->createView(),
                'widgetInstance'    => $widgetInstance,
                'userlist'          => $userlist,
                'resourcelist'      => $resourcelist,
                'userCanAccessWs'   => $userCanAccessWs
            )
        );
    }
}