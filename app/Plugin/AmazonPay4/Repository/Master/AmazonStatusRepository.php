<?php

namespace Plugin\AmazonPay4\Repository\Master;

use Eccube\Repository\AbstractRepository;
use Plugin\AmazonPay4\Entity\Master\AmazonStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AmazonStatusRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AmazonStatus::class);
    }
}
?>