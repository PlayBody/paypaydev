<?php

namespace Plugin\SlnPayment4\Service\SlnAction\Content\Member\Response;

class MemUnInval extends MemInval
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['CompanyCd'] = '';
        return $dataKey;
    }
}