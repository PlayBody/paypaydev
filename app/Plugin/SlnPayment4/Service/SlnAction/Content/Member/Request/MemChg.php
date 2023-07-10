<?php

namespace Plugin\SlnPayment4\Service\SlnAction\Content\Member\Request;

class MemChg extends MemAdd
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['NewKaiinPass'] = '';
        return $dataKey;
    }
}