<?php

namespace Plugin\MarketPlace4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\CsvType;

/**
 * Config
 *
 * @ORM\Table(name="plg_market_place4_config")
 * @ORM\Entity(repositoryClass="Plugin\MarketPlace4\Repository\ConfigRepository")
 */
class MarketPlace4Config
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
     * @var \Eccube\Entity\MailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\MailTemplate")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mail_template_id", nullable=true, referencedColumnName="id")
     * })
     */
    private $MailTemplate;

    /**
     * @var \Eccube\Entity\Master\CsvType
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\CsvType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="csv_type_id", nullable=true, referencedColumnName="id")
     * })
     */
    private $CsvType;

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
     * @return $this;
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Get CsvType
     *
     * @return \Eccube\Entity\Master\CsvType
     */
    public function getCsvType()
    {
        return $this->CsvType;
    }

    /**
     * Set CsvType
     *
     * @param CsvType $CsvType
     *
     * @return $this
     */
    public function setCsvType(CsvType $CsvType = null)
    {
        $this->CsvType = $CsvType;

        return $this;
    }
}
