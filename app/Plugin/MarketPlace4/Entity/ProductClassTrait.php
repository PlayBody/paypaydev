<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{

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
     * Set Member.
     *
     * @param \Eccube\Entity\Member|null $member
     *
     * @return ProductClass
     */
    public function setMember(\Eccube\Entity\Member $member = null)
    {
        $this->Member = $member;

        return $this;
    }

    /**
     * Get Member.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getMember()
    {
        return $this->Member;
    }


    /**
     * Set id
     *
     * @param integer $id
     *
     * @return ProductClass
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
