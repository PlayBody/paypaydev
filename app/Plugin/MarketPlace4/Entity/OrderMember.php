<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
/**
 *
 *
 * @ORM\Table(name="plg_market_place4_order_member")
 * @ORM\Entity(repositoryClass="Plugin\MarketPlace4\Repository\OrderMemberRepository")
 */
class OrderMember
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
     * @var \Eccube\Entity\Order
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;
    /**
     * @var \Eccube\Entity\Shipping
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Shipping")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="shipping_id", referencedColumnName="id")
     * })
     */
    private $Shipping;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="member_id", referencedColumnName="id")
     * })
     */
    private $Member;
   /**
     * @var string|null
     *
     * @ORM\Column(name="amount", type="string", length=10, nullable=true)
     */
    private $amount;
    /**
     * @var boolean
     *
     * @ORM\Column(name="$matome", type="boolean", options={"default":false})
     */
    private $matome = false;
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
     * Set order.
     *
     * @param \Eccube\Entity\Order|null $order
     *
     * @return OrderMember
     */
    public function setOrder(\Eccube\Entity\Order $order = null)
    {
        $this->Order = $order;

        return $this;
    }

    /**
     * Get order.
     *
     * @return \Eccube\Entity\Order|null
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set Shipping.
     *
     * @param \Eccube\Entity\Shipping|null $shipping
     *
     * @return OrderMember
     */
    public function setShipping(\Eccube\Entity\Shipping $shipping = null)
    {
        $this->Shipping = $shipping;

        return $this;
    }

    /**
     * Get Shipping.
     *
     * @return \Eccube\Entity\Shipping|null
     */
    public function getShipping()
    {
        return $this->Shipping;
    }


    /**
     * Set member.
     *
     * @param \Eccube\Entity\member|null $member
     *
     * @return OrderMember
     */
    public function setMember(\Eccube\Entity\Member $member = null)
    {
        $this->Member = $member;

        return $this;
    }

    /**
     * Get member.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getMember()
    {
        return $this->Member;
    }
    /**
     * Set amount.
     *
     * @param string|null $amount
     *
     * @return this
     */
    public function setAmount($amount = null)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return string|null
     */
    public function getAmount()
    {
        return $this->amount;
    }
    /**
     * Set matome.
     *
     * @param boolean $matome
     *
     * @return $this
     */
    public function setMatome($matome)
    {
        $this->matome = $matome;

        return $this;
    }

    /**
     * Get matome.
     *
     * @return boolean
     */
    public function isMatome()
    {
        return $this->matome;
    }
    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return ProductClassMember
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
     * @return ProductClassMember
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
