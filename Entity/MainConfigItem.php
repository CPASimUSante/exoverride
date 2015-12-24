<?php

namespace CPASimUSante\ExoverrideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use CPASimUSante\ExoverrideBundle\Entity\MainConfig;
use Claroline\CoreBundle\Entity\Workspace\Workspace;

/**
 * MainConfigItem
 *
 * @ORM\Table(name="cpasimusante__exoverride_mainconfig_item")
 * @ORM\Entity(repositoryClass="CPASimUSante\ExoverrideBundle\Repository\MainConfigItemRepository")
 */
class MainConfigItem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Workspace to apply
     * @var Workspace
     *
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\Workspace\Workspace", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private $workspace;

    /**
     * @var MainConfig
     *
     * @ORM\ManyToOne(targetEntity="CPASimUSante\ExoverrideBundle\Entity\MainConfig", inversedBy="items")
     * @ORM\JoinColumn(name="mainconfig_id", referencedColumnName="id")
     */
    protected $mainconfig;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set workspace
     *
     * @param Workspace $workspace
     *
     * @return MainConfigItem
     */
    public function setWorkspace(Workspace $workspace = null)
    {
        $this->workspace = $workspace;

        return $this;
    }

    /**
     * Get workspace
     *
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * Set mainconfig
     *
     * @param MainConfig $mainconfig
     *
     * @return MainConfigItem
     */
    public function setMainconfig(MainConfig $mainconfig = null)
    {
        $this->mainconfig = $mainconfig;

        return $this;
    }

    /**
     * Get mainconfig
     *
     * @return MainConfig
     */
    public function getMainconfig()
    {
        return $this->mainconfig;
    }
}
