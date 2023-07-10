<?php
/*
* Plugin Name : ProductPlus
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\ProductPlus\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductDataDetail
 *
 * @ORM\Table(name="plg_productplus_dtb_product_data_detail", indexes={
 *     @ORM\Index(name="plg_productplus_dtb_product_data_detail_num_value_idx", columns={"num_value"}),
 *     @ORM\Index(name="plg_productplus_dtb_product_data_detail_date_value_idx", columns={"date_value"})
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductPlus\Repository\ProductDataDetailRepository")
 */
class ProductDataDetail extends \Eccube\Entity\AbstractEntity
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
     * @var string|null
     *
     * @ORM\Column(name="value", type="string", length=4000, nullable=true)
     */
    private $value;

    /**
     * @var int|null
     *
     * @ORM\Column(name="num_value", type="integer", nullable=true)
     */
    private $num_value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_value", type="datetimetz", nullable=true)
     */
    private $date_value;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer", nullable=true)
     */
    private $sort_no;

    /**
     * @var \Plugin\ProductPlus\Entity\ProductData
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ProductPlus\Entity\ProductData", inversedBy="Details")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_data_id", referencedColumnName="id")
     * })
     */
    private $ProductData;

    public function getId()
    {
        return $this->id;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setNumValue($numValue)
    {
        $this->num_value = $numValue;

        return $this;
    }

    public function getNumValue()
    {
        return $this->num_value;
    }

    public function setDateValue($dateValue)
    {
        $this->date_value = $dateValue;

        return $this;
    }

    public function getDateValue()
    {
        return $this->date_value;
    }

    public function setSortNo($sortNo)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    public function getSortNo()
    {
        return $this->sort_no;
    }

    public function setProductData(\Plugin\ProductPlus\Entity\ProductData $productData = null)
    {
        $this->ProductData = $productData;

        return $this;
    }

    public function getProductData()
    {
        return $this->ProductData;
    }
}
