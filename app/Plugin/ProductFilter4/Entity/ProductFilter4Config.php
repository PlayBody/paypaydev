<?php

namespace Plugin\ProductFilter4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_product_filter4_config")
 * @ORM\Entity(repositoryClass="Plugin\ProductFilter4\Repository\ProductFilter4ConfigRepository")
 */
class ProductFilter4Config
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="max_price", type="string", length=10)
     */
    private $max_price;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this;
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxPrice()
    {
        return $this->max_price;
    }

    /**
     * @param string $max_price
     *
     * @return $this;
     */
    public function setMaxPrice($max_price)
    {
        $this->max_price = $max_price;

        return $this;
    }
}
