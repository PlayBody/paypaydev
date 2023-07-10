<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
/**
 * @EntityExtension("Eccube\Entity\DeliveryFee")
 */
trait DeliveryFeeTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="free_fee", type="decimal", precision=12, scale=2, options={"unsigned":true})
     */
    private $free_fee;

    /**
     * Set fee.
     *
     * @param string $free_fee
     *
     * @return $this
     */
    public function setFreeFee($free_fee)
    {
        $this->free_fee = $free_fee;

        return $this;
    }

    /**
     * Get fee.
     *
     * @return string
     */
    public function getFreeFee()
    {
        return $this->free_fee;
    }
	
	
    /**
     * @var string
     *
     * @ORM\Column(name="add_fee", type="decimal", precision=12, scale=2, options={"unsigned":true})
     */
    private $add_fee;

    /**
     * Set fee.
     *
     * @param string $add_fee
     *
     * @return $this
     */
    public function setAddFee($add_fee)
    {
        $this->add_fee = $add_fee;

        return $this;
    }

    /**
     * Get fee.
     *
     * @return string
     */
    public function getAddFee()
    {
        return $this->add_fee;
    }
}
