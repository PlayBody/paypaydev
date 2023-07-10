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

/**
 * ProductItem
 *
 * @ORM\Table(name="plg_productplus_dtb_product_item")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ProductPlus\Repository\ProductItemRepository")
 */
class ProductItem extends \Eccube\Entity\AbstractEntity
{
    const TEXT_TYPE = 1;
    const TEXTAREA_TYPE = 2;
    const IMAGE_TYPE = 3;
    const DATE_TYPE = 4;
    const SELECT_TYPE = 10;
    const RADIO_TYPE = 11;
    const CHECKBOX_TYPE = 12;
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
     * @ORM\Column(name="name", type="string", length=4000, nullable=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="input_type", type="smallint", options={"default":0})
     */
    private $input_type;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true)
     */
    private $is_required;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="search_flg", type="boolean", nullable=true)
     */
    private $search_flg;

    /**
     * @var boolean|null
     *
     * @ORM\Column(name="keyword_flg", type="boolean", nullable=true)
     */
    private $keyword_flg;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer")
     */
    private $sort_no;

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
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ProductPlus\Entity\ProductItemOption", mappedBy="ProductItem", cascade={"persist","remove"})
     */
    private $Options;

    public function __construct()
    {
        $this->Options = new ArrayCollection();
    }

    public function getId()
    {
    return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setInputType($type)
    {
        $this->input_type = $type;

        return $this;
    }

    public function getInputType()
    {
        return $this->input_type;
    }

    public function setIsRequired($required)
    {
        $this->is_required = $required;

        return $this;
    }

    public function getIsRequired()
    {
        return $this->is_required;
    }

    public function setSearchFlg($flg)
    {
        $this->search_flg = $flg;

        return $this;
    }

    public function getSearchFlg()
    {
        return $this->search_flg;
    }

    public function setKeywordFlg($flg)
    {
        $this->keyword_flg = $flg;

        return $this;
    }

    public function getKeywordFlg()
    {
        return $this->keyword_flg;
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

    public function setCreateDate($date)
    {
        $this->create_date = $date;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setUpdateDate($date)
    {
        $this->update_date = $date;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }

    public function addOptions(\Plugin\ProductPlus\Entity\ProductItemOption $Option)
    {
        $this->Options[] = $Option;

        return $this;
    }

    public function removeOptions(\Plugin\ProductPlus\Entity\ProductItemOption $Option)
    {
        $this->Options->removeElement($Option);
    }

    public function getOptions()
    {
        return $this->Options;
    }
}
