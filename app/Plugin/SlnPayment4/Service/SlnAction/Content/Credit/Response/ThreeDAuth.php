<?php

namespace Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Response;

use Plugin\SlnPayment4\Service\SlnContent\Basic as contentBasic;
use Plugin\SlnPayment4\Service\SlnContent\Credit\ThreeDMaster;

class ThreeDAuth extends Auth
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['SecureResultCode'] = '';
        return $dataKey;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Basic::setContent()
     */
    public function setContent(contentBasic $content) {
        if($content instanceof ThreeDMaster) {
            $this->content = $content;
        } else {
            throw new \Exception("content not Plugin\SlnPayment4\Service\SlnContent\Credit\Master");
        }
    }
}