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
use Doctrine\Common\Collections\ArrayCollection;
use Plugin\ProductPlus\Entity\ProductItem;

/**
 * ProductData
 *
 * @ORM\Table(name="plg_productplus_dtb_product_data")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductPlus\Repository\ProductDataRepository")
 */
class ProductData extends \Eccube\Entity\AbstractEntity
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
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ProductPlus\Entity\ProductDataDetail", mappedBy="ProductData", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "sort_no"="ASC"
     * })
     */
    private $Details;

    /**
     * @var \Plugin\ProductPlus\Entity\ProductItem
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ProductPlus\Entity\ProductItem")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_item_id", referencedColumnName="id")
     * })
     */
    private $ProductItem;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="ProductDatas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    public function __construct()
    {
        $this->Details = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCreateDate($date)
    {
        $this->create_date = $date;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function addDetail(\Plugin\ProductPlus\Entity\ProductDataDetail $detail)
    {
        $this->Details[] = $detail;

        return $this;
    }

    public function removeDetail(\Plugin\ProductPlus\Entity\ProductDataDetail $detail)
    {
        $this->Details->removeElement($detail);
    }

    public function getDetails()
    {
        return $this->Details;
    }

    public function setProductItem(\Plugin\ProductPlus\Entity\ProductItem $ProductItem)
    {
        $this->ProductItem = $ProductItem;

        return $this;
    }

    public function getProductItem()
    {
        return $this->ProductItem;
    }

    public function setProduct(\Eccube\Entity\Product $Product)
    {
        $this->Product = $Product;

        return $this;
    }

    public function getProduct()
    {
        return $this->Product;
    }

    public function getValue()
    {
        $ret = [];
        foreach($this->Details as $detail){
            if($this->ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                $value = $detail->getNumValue();
            }else{
                $value = $detail->getValue();
            }
            $ret[] = $value;
        }
        if(!($this->ProductItem->getInputType() == ProductItem::IMAGE_TYPE || $this->ProductItem->getInputType() == ProductItem::CHECKBOX_TYPE)){
            return $ret[0];
        }
        return $ret;
    }

    public function getNumValue()
    {
        $ret = [];
        foreach($this->Details as $detail){
            $ret[] = $detail->getNumValue();
        }
        return $ret;
    }

    public function getDataValue()
    {
        $ret = [];
        if($this->ProductItem){
            $Options = $this->ProductItem->getOptions();
            foreach($this->Details as $detail){
                if($this->ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                    foreach($Options as $Option){
                        if($detail->getNumValue() == $Option->getId())$ret[] = $Option->getText();
                    }
                }else{
                    $ret[] = $detail->getValue();
                }
            }
        }
        return implode(',', $ret);
    }

    public function getViewValue()
    {
        $ret = [];
        if($this->ProductItem){
            $Options = $this->ProductItem->getOptions();
            foreach($this->Details as $detail){
                if($this->ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                    foreach($Options as $Option){
                        if($detail->getNumValue() == $Option->getId())$ret[] = $Option->getText();
                    }
                }else{
                    $ret[] = $detail->getValue();
                }
            }
            if($this->ProductItem->getInputType() == ProductItem::IMAGE_TYPE || $this->ProductItem->getInputType() == ProductItem::CHECKBOX_TYPE){
                return $ret;
            }else{
                return implode(',', $ret);
            }
        }
    }
}
