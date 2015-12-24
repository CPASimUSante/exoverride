<?php

namespace CPASimUSante\ExoverrideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use CPASimUSante\ExoverrideBundle\Entity\MainConfigItem;

/**
 * MainConfig
 *
 * @ORM\Table(name="cpasimusante__exoverride_mainconfig")
 * @ORM\Entity(repositoryClass="CPASimUSante\ExoverrideBundle\Repository\MainConfigRepository")
 */
class MainConfig
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
     * @var Items[]
     *
     * @ORM\OneToMany(targetEntity="CPASimUSante\ExoverrideBundle\Entity\MainConfigItem", mappedBy="mainconfig", cascade={"all"})
     */
    protected $items;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
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

    /**
     * Add item
     *
     * @param MainConfigItem $item
     *
     * @return Mainconfig
     */
    public function addItem(MainConfigItem $item)
    {
        /*       $this->items[] = $item;
               //$item->setItemselector($this);
               return $this;
       */
        $item->setMainconfig($this);

        $this->items->add($item);
    }

    /**
     * Remove item
     *
     * @param MainConfigItem $item
     */
    public function removeItem(MainConfigItem $item)
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }
}
