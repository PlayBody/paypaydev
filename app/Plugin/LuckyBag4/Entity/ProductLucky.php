<?php

namespace Plugin\LuckyBag4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
/**
 *
 *
 * @ORM\Table(name="plg_lucky_bag4_product_lucky")
 * @ORM\Entity(repositoryClass="Plugin\LuckyBag4\Repository\ProductLuckyRepository")
 */
class ProductLucky
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
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

   /**
     * @var string|null
     *
     * @ORM\Column(name="product_code", type="string", length=255, nullable=true)
     */
    private $product_code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="lucky_rate", type="string", length=10, nullable=true)
     */
    private $lucky_rate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", options={"default":true})
     */
    private $visible;

    /**
     * @var boolean
     *
     * @ORM\Column(name="add_point", type="boolean", options={"default":false})
     */
    private $add_point;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set Product.
     *
     * @param \Eccube\Entity\Product|null $Product
     *
     * @return ProductLucky
     */
    public function setProduct(\Eccube\Entity\Product $Product = null)
    {
        $this->Product = $Product;

        return $this;
    }

    /**
     * Get Product.
     *
     * @return \Eccube\Entity\Product|null
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * Set product_code.
     *
     * @param string|null $product_code
     *
     * @return ProductLucky
     */
    public function setProductCode($product_code = null)
    {
        $this->product_code = $product_code;

        return $this;
    }

    /**
     * Get product_code.
     *
     * @return string|null
     */
    public function getProductCode()
    {
        return $this->product_code;
    }

    /**
     * Set lucky_rate.
     *
     * @param string|null $lucky_rate
     *
     * @return ProductLucky
     */
    public function setLuckyRate($lucky_rate = null)
    {
        $this->lucky_rate = $lucky_rate;

        return $this;
    }

    /**
     * Get lucky_rate.
     *
     * @return string|null
     */
    public function getLuckyRate()
    {
        return $this->lucky_rate;
    }

    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     *
     * @return ProductLucky
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAddPoint()
    {
        return $this->add_point;
    }

    /**
     * @param boolean $add_point
     *
     * @return ProductLucky
     */
    public function setAddPoint($add_point)
    {
        $this->add_point = $add_point;

        return $this;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return ProductLucky
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return ProductLucky
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
