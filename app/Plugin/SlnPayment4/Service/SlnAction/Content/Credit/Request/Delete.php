<?php
namespace Plugin\SlnPayment4\Service\SlnAction\Content\Credit\Request;

class Delete extends Capture
{
    public function getDataKey()
    {
        $dataKey = parent::getDataKey();
        unset($dataKey['SalesDate']);
        return $dataKey;
    }
}