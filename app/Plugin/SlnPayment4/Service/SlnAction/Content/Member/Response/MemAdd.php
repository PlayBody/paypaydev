<?php
namespace Plugin\SlnPayment4\Service\SlnAction\Content\Member\Response;

use Plugin\SlnPayment4\Service\SlnAction\Content\Basic;
use Plugin\SlnPayment4\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment4\Service\SlnContent\Credit\Member;

class MemAdd extends Basic
{
    /**
     * @var Member
     */
    protected $content;
    
    public function getDataKey()
    {
        return array(
            'TransactionId' => '',
            'TransactionDate' => '',
            'OperateId' => '',
            'MerchantFree1' => '',
            'MerchantFree2' => '',
            'MerchantFree3' => '',
            'ResponseCd' => '',
            'CompanyCd' => '',
            'EnableCheckUseKbn' => '',
            'McSecCd' => '',
            'McKanaSei' => '',
            'McKanaMei' => '',
            'McBirthDay' => '',
            'McTelNo' => '',
        );
    }
    
    public function getOperatePrefix()
    {
        return "4";
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof Member) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment4\Service\SlnContent\Credit\Member");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @var Plugin\SlnPayment4\Service\SlnContent\Credit\Member
     */
    public function getContent() {
        return $this->content;
    }
}