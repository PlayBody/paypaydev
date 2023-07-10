<?php
/*
* Plugin Name : ProductPlus
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\ProductPlus\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProductItemRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, string $entityClass = \Plugin\ProductPlus\Entity\ProductItem::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function getList()
    {
        $qb = $this->createQueryBuilder('ci')
            ->orderBy('ci.sort_no', 'DESC');
        $ProductItems = $qb->getQuery()
            ->getResult();

        return $ProductItems;
    }

    public function save($ProductItem)
    {
        $em = $this->getEntityManager();
        try {
            if (!$ProductItem->getId()) {
                $sortNo = $this->createQueryBuilder('ci')
                    ->select('MAX(ci.sort_no)')
                    ->getQuery()
                    ->getSingleScalarResult();
                if (!$sortNo) {
                    $sortNo = 0;
                }
                $ProductItem->setSortNo($sortNo + 1);
            }

            $em->persist($ProductItem);
            $em->flush($ProductItem);

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($ProductItem)
    {
        $em = $this->getEntityManager();
        try {

            $sortNo = $ProductItem->getSortNo();
            $em->createQueryBuilder()
                ->update('Plugin\ProductPlus\Entity\ProductItem', 'ci')
                ->set('ci.sort_no', 'ci.sort_no - 1')
                ->where('ci.sort_no > :sort_no')->setParameter('sort_no', $sortNo)
                ->getQuery()
                ->execute();

            $em->remove($ProductItem);
            $em->flush($ProductItem);

        } catch (\Exception $e) {

            return false;
        }

        return true;
    }
}