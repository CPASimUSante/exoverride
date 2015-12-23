<?php

namespace CPASimUSante\ExoverrideBundle\Controller;

use CPASimUSante\ExoverrideBundle\Entity\MainConfig;
use CPASimUSante\ExoverrideBundle\Exception\NoMainConfigException;
use CPASimUSante\ExoverrideBundle\Form\MainConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class MainConfigController
 *
 * @category   Controller
 * @package    CPASimUSante
 * @subpackage ExoverrideBundle
 * @author     CPASimUSante <contact@simusante.com>
 * @copyright  2015 CPASimUSante
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @version    0.1
 * @link       http://simusante.com
 *
 * @EXT\Route(
 *      "/config",
 *      name    = "cpasimusante_exoverride_mainconfig",
 *      service = "cpasimusante_exoverride.controller.mainconfig"
 * )
 */
class MainConfigController extends Controller
{
    /**
     * Configuration
     *
     * @EXT\Route("/exoverrideconfig", name="cpasimusante_exoverride_mainconfig", options={"expose"=true})
     * @EXT\Template("CPASimUSanteExoverrideBundle::config.html.twig")
     */
    public function adminOpenAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $mainConfig = $this->getMainConfig();

        // Create an ArrayCollection of the current Item objects in the database
        $originalItems = new ArrayCollection();
        foreach ($mainConfig->getItems() as $item) {
            $originalItems->add($item);
        }

        //working because call to service_container in controller.yml
        $form = $this->get('form.factory')
            ->create(new MainConfigType(), $mainConfig);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // remove the relationship between the item and the ItemSelector
            foreach ($originalItems as $item) {
                if (false === $mainConfig->getItems()->contains($item)) {
                    // in a a many-to-one relationship, remove the relationship
                    $item->setMainconfig(null);
                    $em->persist($item);
                    // to delete the Item entirely, you can also do that
                    $em->remove($item);
                }
            }
            $em->persist($mainConfig);
            $em->flush();
        }

        return array(
            'form'      => $form->createView(),
        );
    }

    public function getMainConfig()
    {
        try {
            $mainConfig = $this->getDoctrine()
                ->getManager()
                ->getRepository('CPASimUSanteExoverrideBundle:MainConfig')
                ->findAll();
            if (sizeof($mainConfig) == 0) {
                throw new NoMainConfigException();
            } else {
                return $mainConfig[0];
            }
        } catch (NoMainConfigException $nme) {
            return new MainConfig();
        }
    }
}