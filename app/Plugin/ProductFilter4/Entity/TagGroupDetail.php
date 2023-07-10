<?php

namespace Plugin\ProductFilter4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
/**
 *
 *
 * @ORM\Table(name="plg_product_filter4_tag_group_detail")
 * @ORM\Entity(repositoryClass="Plugin\ProductFilter4\Repository\TagGroupDetailRepository")
 */
class TagGroupDetail
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
     * @var \Plugin\ProductFilter4\Entity\TagGroup
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ProductFilter4\Entity\TagGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tag_group_id", referencedColumnName="id")
     * })
     */
    private $TagGroup;

    /**
     * @var \Eccube\Entity\Tag
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Tag")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     * })
     */
    private $Tag;

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
     * Set tagGroup.
     *
     * @param \Plugin\ProductFilter4\Entity\TagGroup|null $tagGroup
     *
     * @return TagGroupDetail
     */
    public function setTagGroup(\Plugin\ProductFilter4\Entity\TagGroup $tagGroup = null)
    {
        $this->TagGroup = $tagGroup;

        return $this;
    }

    /**
     * Get tagGroup.
     *
     * @return \Plugin\ProductFilter4\Entity\TagGroup|null
     */
    public function getTagGroup()
    {
        return $this->TagGroup;
    }

    /**
     * Set tag.
     *
     * @param \Eccube\Entity\Tag|null $tag
     *
     * @return TagGroupDetail
     */
    public function setTag(\Eccube\Entity\Tag $tag = null)
    {
        $this->Tag = $tag;

        return $this;
    }

    /**
     * Get tagGroup.
     *
     * @return \Eccube\Entity\Tag|null
     */
    public function getTag()
    {
        return $this->Tag;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return this
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
     * @return TagGroupDetail
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
