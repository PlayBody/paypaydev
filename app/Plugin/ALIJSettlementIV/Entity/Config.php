<?php

namespace Plugin\ALIJSettlementIV\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_ALIJ_config")
 * @ORM\Entity(repositoryClass="Plugin\ALIJSettlementIV\Repository\ConfigRepository")
 */
class Config
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
     * @ORM\Column(name="siteid", type="string", length=255)
     */
    private $siteId;

    /**
     * @var string
     *
     * @ORM\Column(name="sitepassword", type="string", length=255)
     */
    private $sitePassword;

    /**
     * @var boolean
     *
     * @ORM\Column(name="useCont", type="boolean", nullable=true)
     */
    private $useCont;

    /**
     * @var boolean
     *
     * @ORM\Column(name="useQuick", type="boolean", nullable=true)
     */
    private $useQuick;

    /**
     * @var boolean
     *
     * @ORM\Column(name="useAmountCheck", type="boolean", nullable=true)
     */
    private $useAmountCheck;

    /**
     * @var boolean
     *
     * @ORM\Column(name="useTest", type="boolean", nullable=true)
     */
    private $useTest;

    /**
     * @var string
     *
     * @ORM\Column(name="serverIP", type="string", length=255)
     */
    private $serverIP;

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
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param string $siteId
     *
     * @return $this;
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSitePassword()
    {
        return $this->sitePassword;
    }

    /**
     * @param string $sitePassword
     *
     * @return $this;
     */
    public function setSitePassword($sitePassword)
    {
        $this->sitePassword = $sitePassword;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUseCont()
    {
        return $this->useCont;
    }

    /**
     * @param boolean $useCont
     *
     * @return $this;
     */
    public function setUseCont($useCont)
    {
        $this->useCont = $useCont;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUseQuick()
    {
        return $this->useQuick;
    }

    /**
     * @param boolean $useQuick
     *
     * @return $this;
     */
    public function setUseQuick($useQuick)
    {
        $this->useQuick = $useQuick;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUseAmountCheck()
    {
        return $this->useAmountCheck;
    }

    /**
     * @param boolean $useAmountCheck
     *
     * @return $this;
     */
    public function setUseAmountCheck($useAmountCheck)
    {
        $this->useAmountCheck = $useAmountCheck;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUseTest()
    {
        return $this->useTest;
    }

    /**
     * @param boolean $useTest
     *
     * @return $this;
     */
    public function setUseTest($useTest)
    {
        $this->useTest = $useTest;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerIP()
    {
        return $this->serverIP;
    }

    /**
     * @param string $serverIP
     *
     * @return $this;
     */
    public function setServerIP($serverIP)
    {
        $this->serverIP = $serverIP;

        return $this;
    }
}