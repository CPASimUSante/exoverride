<?php
namespace CPASimUSante\ExoverrideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfig;

/**
 * @ORM\Entity(repositoryClass="CPASimUSante\ExoverrideBundle\Repository\ExoverrideStatConfigDataRepository")
 * @ORM\Table(name="cpasimusante__exoverride_stat_configuration_data")
 */
class ExoverrideStatConfigData
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="resourcelist", type="string", length=255, nullable=true)
     */
    protected $resourcelist;

    /**
     * @var string
     *
     * @ORM\Column(name="exolist", type="string", length=255, nullable=true)
     */
    protected $exolist;

    /**
     * @var statconfig
     *
     * @ORM\ManyToOne(targetEntity="CPASimUSante\ExoverrideBundle\Entity\ExoverrideStatConfig", inversedBy="datas")
     * @ORM\JoinColumn(name="statconfig_id", referencedColumnName="id")
     */
    protected $statconfig;

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

    /**
     * Set exolist
     *
     * @param string $exolist
     *
     * @return ExoverrideStatConfigData
     */
    public function setExolist($exolist)
    {
        $this->exolist = $exolist;

        return $this;
    }

    /**
     * Get exolist
     *
     * @return string
     */
    public function getExolist()
    {
        return $this->exolist;
    }

    /**
     * Set mainconfig
     *
     * @param ExoverrideStatConfig $statconfig
     *
     * @return ExoverrideStatConfigData
     */
    public function setStatConfig(ExoverrideStatConfig $statconfig = null)
    {
        $this->statconfig = $statconfig;

        return $this;
    }

    /**
     * Get mainconfig
     *
     * @return ExoverrideStatConfig
     */
    public function getStatConfig()
    {
        return $this->statconfig;
    }
}
