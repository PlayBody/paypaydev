<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Member")
 */
trait MemberTrait
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $market_place4_email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="market_place_matome", type="boolean", options={"default":false})
     */
    private $market_place_matome = false;

    /**
     * @return string
     */
    public function getMarketPlace4Email()
    {
        return $this->market_place4_email;
    }

    /**
     * @param string $market_place4_email
     *
     * @return $this;
     */
    public function setMarketPlace4Email($market_place4_email)
    {
        $this->market_place4_email = $market_place4_email;

        return $this;
    }

    /**
     * Set marketPlaceMatome.
     *
     * @param boolean $market_place_matome
     *
     * @return $this
     */
    public function setMarketPlaceMatome($market_place_matome)
    {
        $this->market_place_matome = $market_place_matome;

        return $this;
    }

    /**
     * Get marketPlaceMatome.
     *
     * @return boolean
     */
    public function isMarketPlaceMatome()
    {
        return $this->market_place_matome;
    }
}
