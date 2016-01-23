<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use Claroline\CoreBundle\Manager\UserManager;
use CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfig;
use CPASimUSante\ExoverrideBundle\Form\ExoverrideStatConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

class ExoverrideWidgetController extends Controller
{
    private $formFactory;
    private $userManager;
    private $request;

    /**
     * @DI\InjectParams({
     *     "formFactory"           = @DI\Inject("form.factory"),
     *     "userManager"           = @DI\Inject("claroline.manager.user_manager"),
     *     "requestStack"          = @DI\Inject("request_stack"),
     * })
     * @param FormFactory $formFactory
     * @param UserManager $userManager
     * @param RequestStack $requestStack
     */
    public function __construct(
        FormFactory $formFactory,
        UserManager $userManager,
        RequestStack $requestStack
    )
    {
        //Object manager initialization
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

        $widgetExoverride = $em->getRepository('CPASimUSanteExoverrideBundle:ExoverrideStatConfig')
            ->findOneByWidgetInstance($widgetInstance);

        $userlist      = '';
        $resourcelist  = '';

        //parameters needed to display graph
        if ($widgetExoverride !== null)
        {
            $userlist      = $widgetExoverride->getUserList();
            $datas         = $widgetExoverride->getDatas();
            foreach($datas as $data)
            {
                $resourcelist .= $data->getExolist().";";
            }
            $resourcelist = trim($resourcelist, ";");
        }

        $exoverrideService = $this->container->get('cpasimusante.exoverride_services');
        //list of user roles
        $roleList = $exoverrideService->userHasRole();

        //is the user the creator of the exercice ?
        //$isExerciseAdmin = $this->container->get('ujm.exercise_services')->isExerciseAdmin($exercise);

        //list of ws the user can use this widget
        $userCanAccessWs = $exoverrideService->userCanAccessWs();

        return array(
            'widgetInstance'    => $widgetInstance,
            'userlist'          => $userlist,
            'resourcelist'      => $resourcelist,
            'userCanAccessWs'   => $userCanAccessWs,
            'roleList'          => $roleList,
        );
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

        $exoverrideService = $this->container->get('cpasimusante.exoverride_services');
        $userCanAccessWs = $exoverrideService->userCanAccessWs();
        //block the access to configure for users with no right
        if ($userCanAccessWs  == array())
        {
            return $this->render(
                'CPASimUSanteExoverrideBundle:Widget:statWidgetConfigure.html.twig',
                array(
                    'userCanAccessWs'   => $userCanAccessWs
                )
            );
        }
        else
        {
            $em = $this->get('doctrine.orm.entity_manager');

            $widgetExoverride = $em->getRepository('CPASimUSanteExoverrideBundle:ExoverrideStatConfig')
                ->findOneByWidgetInstance($widgetInstance);

            // Create an ArrayCollection of the current Data objects in the database
            $originalDatas = new ArrayCollection();
            $resourcelist = array();

            //first run
            if (null === $widgetExoverride) {
                $widgetExoverride = new ExoverrideStatConfig();
                $widgetExoverride
                    ->setWidgetInstance($widgetInstance);
            }
            //retrieve data before
            else
            {
                foreach ($widgetExoverride->getDatas() as $data) {
                    $originalDatas->add($data);
                }

                foreach ($originalDatas as $data) {
                    $resourcelist[] = $data->getResourcelist();
                }
            }
            $form = $this->formFactory->create(new ExoverrideStatConfigType(), $widgetExoverride);
            $form->handleRequest($request);

            $userlist = $widgetExoverride->getUserList();

            if ($form->isValid()) {
                //Need the exercise list corresponding to resource list to persist
                //in order to avoid having a request each time
                $services = $this->container->get('cpasimusante.exoverride_services');
                $widgetExoverride = $form->getData();

                // remove the relationship between the data and the ExoverrideStatConfig
                foreach ($originalDatas as $data) {

//echo 'data->getResourcelist()<pre>';var_dump($data->getResourcelist());echo '</pre>';

                  /*  if (false === $widgetExoverride->getDatas()->contains($data)) {
                        // in a a many-to-one relationship, remove the relationship
                        $data->setExoverrideStatConfig(null);
                        $em->persist($data);
                        // to delete the Item entirely, you can also do that
                        $em->remove($data);
                    }*/
                }
                $em->persist($widgetExoverride);
                $em->flush();

                foreach ($widgetExoverride->getDatas() as $data) {
                    $resourcelist[] = $data->getResourcelist();
                    $exercices = $services->getExoList($data->getResourcelist());
                    $list = array();
                    foreach ($exercices as $exos)
                    {
                        $list[] = $exos->getId();
                    }
                    $exolist = implode(',', $list);
                    $data->setExolist($exolist);
                }
                $em->persist($widgetExoverride);
                $em->flush();

                return new Response('', Response::HTTP_NO_CONTENT);
            }

            return $this->render(
                'CPASimUSanteExoverrideBundle:Widget:statWidgetConfigure.html.twig',
                array(
                    'form'              => $form->createView(),
                    'widgetInstance'    => $widgetInstance,
                    'userlist'          => $userlist,
                    'resourcelist'      => json_encode($resourcelist),
                    'userCanAccessWs'   => $userCanAccessWs
                )
            );
        }
    }
}