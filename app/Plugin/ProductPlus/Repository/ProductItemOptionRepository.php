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
use Plugin\ProductPlus\Entity\ProductItemOption;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProductItemOptionRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry, string $entityClass = \Plugin\ProductPlus\Entity\ProductItemOption::class)
    {
        parent::__construct($registry, $entityClass);
    }

    public function getList($ProductItem = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.sort_no', 'DESC');
        if ($ProductItem) {
            $qb->where('o.ProductItem = :ProductItem')->setParameter('ProductItem', $ProductItem);
        }
        $Options = $qb->getQuery()
            ->getResult();

        return $Options;
    }

    public function save($Option)
    {
        $em = $this->getEntityManager();
        try {
            if (!$Option->getId()) {
                $ProductItem = $Option->getProductItem();
                $sortNo = $this->createQueryBuilder('o')
                    ->select('MAX(o.sort_no)')
                    ->where('o.ProductItem = :ProductItem')->setParameter('ProductItem', $ProductItem)
                    ->getQuery()
                    ->getSingleScalarResult();
                if (!$sortNo) {
                    $sortNo = 0;
                }
                $Option->setSortNo($sortNo + 1);
            }

            $em->persist($Option);
            $em->flush($Option);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($Option)
    {
        $em = $this->getEntityManager();
        try {
            $sortNo = $Option->getSortNo();
            $ProductItem = $Option->getProductItem();

            $em->createQueryBuilder()
                ->update('Plugin\ProductPlus\Entity\ProductItemOption', 'o')
                ->set('o.sort_no', 'o.sort_no - 1')
                ->where('o.sort_no > :sort_no AND o.ProductItem = :ProductItem')
                ->setParameter('sort_no', $sortNo)
                ->setParameter('ProductItem', $ProductItem)
                ->getQuery()
                ->execute();

            $em->remove($Option);
            $em->flush($Option);

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}