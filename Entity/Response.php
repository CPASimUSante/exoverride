<?php

namespace CPASimUSante\ExoverrideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UJM\ExoBundle\Entity\Response as BaseEntity;

/**
 * UJM\ExoBundle\Entity\Response
 *
 * @ORM\Entity(repositoryClass="CPASimUSante\ExoverrideBundle\Repository\ResponseRepository")
 * @ORM\Table(name="ujm_response")
 */
class Response extends BaseEntity
{

}
