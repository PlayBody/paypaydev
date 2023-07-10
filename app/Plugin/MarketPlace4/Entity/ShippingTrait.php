<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Shipping")
 */
trait ShippingTrait
{

    /**
     * @var \Plugin\MarketPlace4\Entity\DeliveryType
     *
     * @ORM\ManyToOne(targetEntity="Plugin\MarketPlace4\Entity\DeliveryType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="delivery_type_id", referencedColumnName="id")
     * })
     */
    private $DeliveryType;


    /**
     * Set Member.
     *
     * @param \Plugin\MarketPlace4\Entity\DeliveryType|null $DeliveryType
     *
     * @return this
     */
    public function setDeliveryType(\Plugin\MarketPlace4\Entity\DeliveryType $DeliveryType = null)
    {
        $this->DeliveryType = $DeliveryType;

        return $this;
    }

    /**
     * Get Member.
     *
     * @return Plugin\MarketPlace4\Entity\DeliveryType
     */
    public function getDeliveryType()
    {
        return $this->DeliveryType;
    }


}
