<?php

namespace Plugin\SlnPayment4\Service\SlnAction\Content\Receipt\Request;

class Del extends Chg
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        unset($dataKey['PayLimit']);
        unset($dataKey['Amount']);
        return $dataKey;
    }
}