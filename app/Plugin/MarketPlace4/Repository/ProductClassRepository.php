<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MarketPlace4\Repository;

use Eccube\Repository\ProductClassRepository as BaseProductClassRepository;

use Eccube\Entity\Product;
use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\ProductClass;

use Doctrine\ORM\NoResultException;
use Customize\Common\OrderStatus as CustomizeOrderStatus;
use Eccube\Doctrine\Query\Queries;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Request\Context;
use Eccube\Util\StringUtil;
use Eccube\Repository\QueryKey;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ProductClassRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductClassRepository extends BaseProductClassRepository
{

    public function getList(Product $Product)
    {
        // second level cacheを効かせるためfindByで取得
        $Results = [];

        $qb = $this->createQueryBuilder('pc');
        $qb->select('pc');
        $qb->leftJoin('pc.Member', 'm');
        $qb->where('pc.Product = :Product')->setParameter('Product', $Product);
        $qb->andWhere('pc.stock > 0');

        $qb->groupBy('m');
        $qb->orderBy('m.Authority','asc');
        $qb->addOrderBy('m.sort_no','desc');

        $Results = $qb ->getQuery()->getResult();


        return $Results;
    }
}
