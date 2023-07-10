<?php
namespace Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Request;

class Change extends Capture
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        unset($dataKey['SalesDate']);
        $dataKey['Amount'] = '';
        return $dataKey;
    }
}