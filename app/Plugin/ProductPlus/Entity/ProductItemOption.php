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
 * ProductItemOption
 *
 * @ORM\Table(name="plg_productplus_dtb_product_item_option")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductPlus\Repository\ProductItemOptionRepository")
 */
class ProductItemOption extends \Eccube\Entity\AbstractEntity
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
     * @ORM\Column(name="text", type="string", length=4000)
     */
    private $text;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer")
     */
    private $sort_no;

    /**
     * @var \Plugin\ProductPlus\Entity\ProductItem
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ProductPlus\Entity\ProductItem", inversedBy="Options")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_item_id", referencedColumnName="id")
     * })
     */
    private $ProductItem;


    public function getId()
    {
    return $this->id;
    }

    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    public function getText()
    {
        return $this->text;
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

    public function setProductItem(\Plugin\ProductPlus\Entity\ProductItem $ProductItem)
    {
        $this->ProductItem = $ProductItem;

        return $this;
    }

    public function getProductItem()
    {
        return $this->ProductItem;
    }
}
