<?php

namespace Plugin\LuckyBag4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\LuckyBag4\Entity\LuckyBag4Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * LuckyBag4ConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LuckyBag4ConfigRepository extends AbstractRepository
{
    /**
     * LuckyBag4ConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LuckyBag4Config::class);
    }

    /**
     * @param int $id
     *
     * @return null|LuckyBag4Config
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
