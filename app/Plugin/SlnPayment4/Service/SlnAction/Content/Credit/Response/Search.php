<?php

namespace Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Response;

class Search extends Capture
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        $dataKey['Amount'] = '';
        $dataKey['SalesDate'] = '';
        return $dataKey;
    }
}