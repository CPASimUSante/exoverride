<?php
namespace CPASimUSante\ExoverrideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Doctrine\Common\Collections\ArrayCollection;
use CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfigData;

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
     * @ORM\Column(name="userlist", type="string", length=255, nullable=true)
     */
    protected $userList;

    /**
     * @var $datas[]
     *
     * @ORM\OneToMany(targetEntity="CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfigData", mappedBy="statconfig", cascade={"all"})
     */
    protected $datas;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->datas = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
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
     * Add data
     *
     * @param ExoverrideStatConfigData $data
     *
     * @return ExoverrideStatConfig
     */
    public function addData(ExoverrideStatConfigData $data)
    {
        /*       $this->datas[] = $data;
               //$item->setItemselector($this);
               return $this;
       */
        $data->setStatConfig($this);

        $this->datas->add($data);
    }

    /**
     * Remove data
     *
     * @param ExoverrideStatConfigData $data
     */
    public function removeData(ExoverrideStatConfigData $data)
    {
        $this->datas->removeElement($data);
    }

    /**
     * Get datas
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDatas()
    {
        return $this->datas;
    }
}
