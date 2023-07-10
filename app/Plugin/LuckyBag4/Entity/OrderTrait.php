<?php

namespace Plugin\LuckyBag4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lucky_bag4_sale_type;

    /**
     * @return string
     */
    public function getLuckyBag4SaleType()
    {
        return $this->lucky_bag4_sale_type;
    }

    /**
     * @param string $lucky_bag4_sale_type
     *
     * @return OrderTrait;
     */
    public function setLuckyBag4SaleType($lucky_bag4_sale_type)
    {
        $this->lucky_bag4_sale_type = $lucky_bag4_sale_type;

        return $this;
    }

}
