<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="messeage_member_id", referencedColumnName="id")
     * })
     */
    private $MessageMember;


    /**
     * Set Member.
     *
     * @param \Eccube\Entity\Member|null $member
     *
     * @return this
     */
    public function setMessageMember(\Eccube\Entity\Member $member = null)
    {
        $this->MessageMember = $member;

        return $this;
    }

    /**
     * Get Member.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getMessageMember()
    {
        return $this->MessageMember;
    }

}
