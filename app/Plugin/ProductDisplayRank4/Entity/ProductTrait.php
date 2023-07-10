<?php

namespace Plugin\ProductDisplayRank4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Trait ProductTrait
 * @package Plugin\ProductDisplayRank4\Entity
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @ORM\Column(type="integer", options={"unsigned":false, "default" : 0})
     * @var integer
     */
    private $display_rank;

    /**
     * @return int
     */
    public function getDisplayRank()
    {
        return $this->display_rank;
    }

    /**
     * @param int $display_rank
     * @return ProductTrait
     */
    public function setDisplayRank($display_rank)
    {
        $this->display_rank = $display_rank;
        return $this;
    }
}