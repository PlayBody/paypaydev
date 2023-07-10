<?php

namespace Plugin\LuckyBag4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\MailTemplate;
use Plugin\MarketPlace4\Entity\MarketPlace4Config;

/**
 * Config
 *
 * @ORM\Table(name="plg_lucky_bag4_config")
 * @ORM\Entity(repositoryClass="Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository")
 */
class LuckyBag4Config
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
     * @var \Eccube\Entity\Master\SaleType
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\SaleType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sale_type_id", nullable=true, referencedColumnName="id")
     * })
     */
    private $SaleType;

    /**
     * @var string
     *
     * @ORM\Column(name="product_lucky_max", type="string", length=255)
     */
    private $product_lucky_max;

    /**
     * @var \Eccube\Entity\MailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\MailTemplate")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mail_template_id", nullable=true, referencedColumnName="id")
     * })
     */
    private $MailTemplate;
    /**
     * @var string
     *
     * @ORM\Column(name="add_point", type="string", length=255)
     */
    private $add_point;

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
     * @return LuckyBag4Config;
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }


    /**
     * @return \Eccube\Entity\Master\SaleType
     */
    public function getSaleType()
    {
        return $this->SaleType;
    }

    /**
     * @param \Eccube\Entity\Master\SaleType $SaleType
     *
     * @return LuckyBag4Config;
     */
    public function setSaleType($SaleType)
    {
        $this->SaleType = $SaleType;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductLuckyMax()
    {
        return $this->product_lucky_max;
    }

    /**
     * @param string $product_lucky_max
     *
     * @return LuckyBag4Config;
     */
    public function setProductLuckyMax($product_lucky_max)
    {
        $this->product_lucky_max = $product_lucky_max;

        return $this;
    }

    /**
     * Get MailTemplate
     *
     * @return \Eccube\Entity\MailTemplate
     */
    public function getMailTemplate()
    {
        return $this->MailTemplate;
    }

    /**
     * Set CsvType
     *
     * @param MailTemplate $MailTemplate
     *
     * @return $this
     */
    public function setMailTemplate(MailTemplate $MailTemplate = null)
    {
        $this->MailTemplate = $MailTemplate;
        return $this;
    }
    /**
     * @return string
     */
    public function getAddPoint()
    {
        return $this->add_point;
    }

    /**
     * @param string $add_point
     *
     * @return LuckyBag4Config;
     */
    public function setAddPoint($add_point)
    {
        $this->add_point = $add_point;

        return $this;
    }

}
