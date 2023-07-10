<?php

namespace Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Request;

use Plugin\SlnPayment4\Service\SlnContent\Credit\Master;
use Plugin\SlnPayment4\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment4\Service\SlnContent\Basic as contentBasic;

class Check extends Basic
{
    /**
     * @var Master
     */
    protected $content;
    
    public function getDataKey()
    {
        return array(
            'MerchantId' => '',
            'MerchantPass' => '',
            'TransactionDate' => '',
            'OperateId' => '',
            'MerchantFree1' => '',
            'MerchantFree2' => '',
            'MerchantFree3' => '',
            'TenantId' => '',
            'KaiinId' => '',
            'KaiinPass' => '',
            'CardNo' => '',
            'CardExp' => '',
            'SecCd' => '',
            'KanaSei' => '',
            'KanaMei' => '',
            'BirthDay' => '',
            'TelNo' => '',
            'eLIO' => '',
            'MessageVersionNo3D' => '',
            'TransactionId3D' => '',
            'EncodeXId3D' => '',
            'TransactionStatus3D' => '',
            'CAVVAlgorithm3D' => '',
            'CAVV3D' => '',
            'ECI3D' => '',
            'PANCardNo3D' => '',
        );
    }
    
    public function getOperatePrefix()
    {
        return "1";
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof Master) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment4\Service\SlnContent\Credit\Master");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment4\Service\SlnContent\Credit\Master
     */
    public function getContent() {
        return $this->content;
    }
}