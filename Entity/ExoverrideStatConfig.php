<?php
namespace CPASimUSante\ExoverrideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="CPASimUSante\ExoverrideBundle\Repository\ExoverrideStatConfigRepository")
 * @ORM\Table(name="cpasimusante__exoverride_stat_configuration")
 */
class ExoverrideStatConfig
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\Widget\WidgetInstance")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $widgetInstance;

    /**
     * @var string
     *
     * @ORM\Column(name="userlist", type="string", length=255)
     */
    protected $userList;

    /**
     * @var string
     *
     * @ORM\Column(name="resourcelist", type="string", length=255)
     */
    protected $resourcelist;

    public function getId()
    {
        return $this->id;
    }

    public function setWidgetInstance(WidgetInstance $ds)
    {
        $this->widgetInstance = $ds;
    }

    public function getWidgetInstance()
    {
        return $this->widgetInstance;
    }

    /**
     * Set userList
     *
     * @param string $userList
     *
     * @return ExoverrideStatConfig
     */
    public function setUserList($userList)
    {
        $this->userList = $userList;

        return $this;
    }

    /**
     * Get userList
     *
     * @return string
     */
    public function getUserList()
    {
        return $this->userList;
    }

    /**
     * Set resourcelist
     *
     * @param string $resourcelist
     *
     * @return ExoverrideStatConfig
     */
    public function setResourcelist($resourcelist)
    {
        $this->resourcelist = $resourcelist;

        return $this;
    }

    /**
     * Get resourcelist
     *
     * @return string
     */
    public function getResourcelist()
    {
        return $this->resourcelist;
    }
}
