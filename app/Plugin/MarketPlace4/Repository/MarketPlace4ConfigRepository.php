<?php

namespace Plugin\MarketPlace4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\MarketPlace4\Entity\MarketPlace4Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MarketPlace4ConfigRepository extends AbstractRepository
{
    /**
     * ConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MarketPlace4Config::class);
    }

    /**
     * @param int $id
     *
     * @return null|MarketPlace4Config
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
