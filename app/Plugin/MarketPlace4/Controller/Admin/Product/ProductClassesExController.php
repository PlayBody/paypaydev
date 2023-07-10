<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\MarketPlace4\Controller\Admin\Product;


use Doctrine\ORM\NoResultException;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ClassName;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductStock;
use Eccube\Entity\TaxRule;
use Eccube\Form\Type\Admin\ProductClassMatrixType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ClassCategoryRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Util\CacheUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


use Eccube\Repository\MemberRepository;
use Eccube\Repository\ClassNameRepository;
use Eccube\Repository\ProductStockRepository;

class ProductClassesExController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * @var ClassCategoryRepository
     */
    protected $classCategoryRepository;

    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var ClassNameRepository
     */
    protected $classNameRepository;


    /**
     * @var ProductStockRepository
     */
    protected $productStockRepository;

    /**
     * TagExController constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductStockRepository $productStockRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductClassRepository $productClassRepository,
        ClassCategoryRepository $classCategoryRepository,
        BaseInfoRepository $baseInfoRepository,
        TaxRuleRepository $taxRuleRepository,
        MemberRepository $memberRepository,
        ClassNameRepository $classNameRepository,
        ProductStockRepository $productStockRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productClassRepository = $productClassRepository;
        $this->classCategoryRepository = $classCategoryRepository;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->memberRepository = $memberRepository;
        $this->classNameRepository = $classNameRepository;
        $this->productStockRepository = $productStockRepository;
    }


    /**
     * 
     *
     * @Route("/%eccube_admin_route%/product/product/ex/class/update", name="market_place4_admin_product_class_ex_update")
     * @Template("@MarketPlace4/admin/Product/product_class_ex.twig")
     */
    public function classesUpdate(Request $request, CacheUtil $cacheUtil)
    {
        $id = $request->get('id');
        $Product = $this->findProduct($id);
        if (!$Product) {
            throw new NotFoundHttpException();
        }
        $ClassName1 = null;
        $ClassName2 = null;

        $Member = $this->getUser();
        $Authority = $Member->getAuthority()->getId();

        if ($Authority==0){
            $Members = $this->memberRepository->createQueryBuilder('m')
                ->orderBy('m.Authority', 'ASC')
                ->addOrderBy('m.sort_no', 'DESC')
                ->getQuery()->getResult();
        }else{
            $Members[] = $Member;
        }

        if ($Product->hasProductClass()) {
            $EmptyMemberProductClasses = $Product->getProductClasses()
                ->filter(function ($pc) {
                    return $pc->getClassCategory1() !== null && $pc->getMember() === null;
                });
//            foreach ($EmptyMemberProductClasses as $pc){
//                $pc->setMember($this->memberRepository->find(1));
//                $this->entityManager->persist($pc);
//                $this->entityManager->flush();
//
//            }
            $ProductClassesAll = $Product->getProductClasses()
                ->filter(function ($pc) {
                    return $pc->getClassCategory1() !== null ;
                });

            // 規格ありの商品は編集画面を表示する.
            if ($Authority == 0 ){
                $ProductClasses = $ProductClassesAll;
            }else{
                $ProductClasses = $this->productClassRepository->findBy(['Product'=>$Product, 'Member'=>$Member]);
            }

            // 設定されている規格名1, 2を取得(商品規格の規格分類には必ず同じ値がセットされている)
            $FirstProductClass = $ProductClassesAll[0];
            $ClassName1 = $FirstProductClass->getClassCategory1()->getClassName();
            $ClassCategory2 = $FirstProductClass->getClassCategory2();
            $ClassName2 = $ClassCategory2 ? $ClassCategory2->getClassName() : null;

            // 規格名1/2から組み合わせを生成し, DBから取得した商品規格とマージする.
            $ProductClasses = $this->mergeProductClasses(
                $this->createProductClasses($ClassName1, $ClassName2, $Members),
                $ProductClasses);
            // 組み合わせのフォームを生成する.
            $form = $this->createMatrixForm($ProductClasses, $ClassName1, $ClassName2,
                ['product_classes_exist' => true]);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // フォームではtokenを無効化しているのでここで確認する.
                //$this->isTokenValid();
                $this->saveProductClasses($Product, $form['product_classes']->getData());

                $this->addSuccess('admin.common.save_complete', 'admin');

                $cacheUtil->clearDoctrineCache();

                if ($request->get('return_product_list')) {
                    return $this->redirectToRoute('admin_product_product_class', ['id' => $Product->getId(), 'return_product_list' => true]);
                }

                return $this->redirectToRoute('admin_product_product_class', ['id' => $Product->getId()]);
            }
        } else {
            // 規格なし商品
            $form = $this->createMatrixForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->isTokenValid();

                $isSave = $form['save']->isClicked();

                $ClassName1 = $form['class_name1']->getData();
                $ClassName2 = $form['class_name2']->getData();

                $ProductClasses = $this->createProductClasses($ClassName1, $ClassName2, $Members);

                $form = $this->createMatrixForm($ProductClasses, $ClassName1, $ClassName2,
                    ['product_classes_exist' => true]);

                if ($isSave) {
                    $form->handleRequest($request);
                    if ($form->isSubmitted() && $form->isValid()) {

                        $this->saveProductClasses($Product, $form['product_classes']->getData());

                        $this->addSuccess('admin.common.save_complete', 'admin');

                        $cacheUtil->clearDoctrineCache();

                        if ($request->get('return_product_list')) {
                            return $this->redirectToRoute('admin_product_product_class', ['id' => $Product->getId(), 'return_product_list' => true]);
                        }

                        return $this->redirectToRoute('admin_product_product_class', ['id' => $Product->getId()]);
                    }
                }
            }
        }


        return [
            'Members' => $Members,
            'Product' => $Product,
            'form' => $form->createView(),
            'clearForm' => $this->createForm(FormType::class)->createView(),
            'ClassName1' => $ClassName1,
            'ClassName2' => $ClassName2,
            'return_product_list' => $request->get('return_product_list') ? true : false,
        ];
    }

    /**
     * 商品を取得する.
     * 商品規格はvisible=trueのものだけを取得し, 規格分類はsort_no=DESCでソートされている.
     *
     * @param $id
     *
     * @return Product|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function findProduct($id)
    {
        $qb = $this->productRepository->createQueryBuilder('p')
            ->addSelect(['pc', 'cc1', 'cc2'])
            ->leftJoin('p.ProductClasses', 'pc')
            ->leftJoin('pc.ClassCategory1', 'cc1')
            ->leftJoin('pc.ClassCategory2', 'cc2')
            ->where('p.id = :id')
            ->andWhere('pc.visible = :pc_visible')
            ->setParameter('id', $id)
            ->setParameter('pc_visible', true)
            ->orderBy('cc1.sort_no', 'DESC')
            ->addOrderBy('cc2.sort_no', 'DESC');

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }



    /**
     * 商品規格の配列をマージする.
     *
     * @param $ProductClassesForMatrix
     * @param $ProductClasses
     *
     * @return array|ProductClass[]
     */
    protected function createMemberProductClasses($ProductClassesForMatrix, $Product, $Member)
    {

        $ProductClasses = $this->productClassRepository->findBy(['Product' => $Product, 'Member'=>$Member]);

        $mergedProductClasses = [];
        foreach ($ProductClassesForMatrix as $pcfm) {
            foreach ($ProductClasses as $pc) {
                    if ($pcfm->getClassCategory1()->getId() === $pc->getClassCategory1()->getId()) {
                        $cc2fm = $pcfm->getClassCategory2();
                        $cc2 = $pc->getClassCategory2();

                        if (null === $cc2fm && null === $cc2) {
                            $mergedProductClasses[] = $pc;
                            continue 2;
                        }

                        if ($cc2fm && $cc2 && $cc2fm->getId() === $cc2->getId()) {
                            $mergedProductClasses[] = $pc;
                            continue 2;
                        }
                    }
            }
            $pcm = new ProductClass();
            $pcm->setProduct($pcfm->getProduct());
            $pcm->setClassCategory1($pcfm->getClassCategory1());
            $pcm->setClassCategory2($pcfm->getClassCategory2());
            $pcm->setCode($pcfm->getCode());
            $pcm->setPrice01($pcfm->getPrice01());
            $pcm->setPrice02($pcfm->getPrice02());
            $pcm->setMember($Member);
            $mergedProductClasses[] = $pcm;
        }

        return $mergedProductClasses;
    }

    /**
     * 規格名1/2から, 商品規格の組み合わせを生成する.
     *
     * @param ClassName $ClassName1
     * @param ClassName|null $ClassName2
     *
     * @return array|ProductClass[]
     */
    protected function createProductClasses(ClassName $ClassName1, ClassName $ClassName2 = null, $Members)
    {
        $ProductClasses = [];
        $ClassCategories1 = $this->classCategoryRepository->findBy(['ClassName' => $ClassName1], ['sort_no' => 'DESC']);
        $ClassCategories2 = [];
        if ($ClassName2) {
            $ClassCategories2 = $this->classCategoryRepository->findBy(['ClassName' => $ClassName2],
                ['sort_no' => 'DESC']);
        }

        foreach ($Members as $Member){
            foreach ($ClassCategories1 as $ClassCategory1) {
                // 規格1のみ
                if (!$ClassName2) {
                    $ProductClass = new ProductClass();
                    $ProductClass->setClassCategory1($ClassCategory1);
                    $ProductClass->setMember($Member);
                    $ProductClasses[] = $ProductClass;
                    continue;
                }
                // 規格1/2
                foreach ($ClassCategories2 as $ClassCategory2) {
                    $ProductClass = new ProductClass();
                    $ProductClass->setClassCategory1($ClassCategory1);
                    $ProductClass->setClassCategory2($ClassCategory2);
                    $ProductClass->setMember($Member);
                    $ProductClasses[] = $ProductClass;
                }
            }
        }

        return $ProductClasses;
    }

    /**
     * 商品規格登録フォームを生成する.
     *
     * @param array $ProductClasses
     * @param ClassName|null $ClassName1
     * @param ClassName|null $ClassName2
     * @param array $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function createMatrixForm(
        $ProductClasses = [],
        ClassName $ClassName1 = null,
        ClassName $ClassName2 = null,
        array $options = []
    ) {
        $options = array_merge(['csrf_protection' => false], $options);
        $builder = $this->formFactory->createBuilder(ProductClassMatrixType::class, [
            'product_classes' => $ProductClasses,
            'class_name1' => $ClassName1,
            'class_name2' => $ClassName2,
        ], $options);

        return $builder->getForm();
    }

    /**
     * 商品規格を登録, 更新する.
     *
     * @param Product $Product
     * @param array|ProductClass[] $ProductClasses
     */
    protected function saveProductClasses(Product $Product, $ProductClasses=[])
    {
        foreach ($ProductClasses as $pc) {
            // 新規登録時、チェックを入れていなければ更新しない
            if (!$pc->getId() && !$pc->isVisible()) {
                continue;
            }

            // 無効から有効にした場合は, 過去の登録情報を検索.
            if (!$pc->getId()) {
                /** @var ProductClass $ExistsProductClass */
                $ExistsProductClass = $this->productClassRepository->findOneBy([
                    'Product' => $Product,
                    'ClassCategory1' => $pc->getClassCategory1(),
                    'ClassCategory2' => $pc->getClassCategory2(),
                    'Member' => $pc->getMember()
                ]);

                // 過去の登録情報があればその情報を復旧する.
                if ($ExistsProductClass) {
                    $ExistsProductClass->copyProperties($pc, [
                        'id',
                        'price01_inc_tax',
                        'price02_inc_tax',
                        'create_date',
                        'update_date',
                        'Creator',
                    ]);
                    $pc = $ExistsProductClass;
                }
            }
            // 更新時, チェックを外した場合はPOST内容を破棄してvisibleのみ更新する.
            if ($pc->getId() && !$pc->isVisible()) {
//                $this->entityManager->remove($pc);
 //               $this->entityManager->flush();
                $this->entityManager->refresh($pc);
                $pc->setVisible(false);
                continue;
            }

            $pc->setProduct($Product);

            $pc_member = $pc->getMember();
            if ($pc_member->getAuthority()->getId()>0){
                $qb = $this->productClassRepository->createQueryBuilder('pc')
                    ->leftJoin('pc.Member', 'm')
                    ->leftJoin('m.Authority', 'auth')
                    ->where('pc.Product = :Product')->setParameter('Product', $pc->getProduct())
                    ->andWhere('pc.ClassCategory1 = :ClassCategory1')->setParameter('ClassCategory1', $pc->getClassCategory1())
                    ->andWhere('auth.id = 0');

                if ($pc->getClassCategory2() != null){
                    $qb->andWhere('pc.ClassCategory2 = :ClassCategory2')->setParameter('ClassCategory2', $pc->getClassCategory2());
                }

                $pc_admin = $qb->getQuery()->getResult();

                if (empty($pc_admin)){

                    $qb = $this->productClassRepository->createQueryBuilder('pc')
                        ->leftJoin('pc.Member', m)
                        ->where('pc.Product = :Product')->setParameter('Product', $pc->getProduct())
                        ->andWhere('pc.ClassCategory1 = :ClassCategory1')->setParameter('ClassCategory1', $pc->getClassCategory1())
                        ->andWhere('m is null');

                    if ($pc->getClassCategory2() != null){
                        $qb->andWhere('pc.ClassCategory2 = :ClassCategory2')->setParameter('ClassCategory2', $pc->getClassCategory2());
                    }

                    $pc_default = $qb->getQuery()->getResult();

                    if (empty($pc_default)) {
                        $pc->setPrice01(null);
                        $pc->setPrice02(null);
                    }else{
                        $pc->setPrice01($pc_default[0]->getPrice01());
                        $pc->setPrice02($pc_default[0]->getPrice02());
                    }
                }else{
                    $pc->setPrice01($pc_admin[0]->getPrice01());
                    $pc->setPrice02($pc_admin[0]->getPrice02());
                }
            }
            $this->entityManager->persist($pc);
            $this->entityManager->flush();

            // 在庫の更新
            $ProductStock = $pc->getProductStock();
            if (!$ProductStock) {
                $ProductStock = new ProductStock();
                $ProductStock->setProductClass($pc);
                $this->entityManager->persist($ProductStock);
            }
            $ProductStock->setStock($pc->isStockUnlimited() ? null : $pc->getStock());

            if ($this->baseInfoRepository->get()->isOptionProductTaxRule()) {
                $rate = $pc->getTaxRate();
                $TaxRule = $pc->getTaxRule();
                if (is_numeric($rate)) {
                    if ($TaxRule) {
                        $TaxRule->setTaxRate($rate);
                    } else {
                        // 現在の税率設定の計算方法を設定する
                        $TaxRule = $this->taxRuleRepository->newTaxRule();
                        $TaxRule->setProduct($Product);
                        $TaxRule->setProductClass($pc);
                        $TaxRule->setTaxRate($rate);
                        $TaxRule->setApplyDate(new \DateTime());
                        $this->entityManager->persist($TaxRule);
                    }
                } else {
                    if ($TaxRule) {
                        $this->taxRuleRepository->delete($TaxRule);
                        $pc->setTaxRule(null);
                    }
                }
            }
        }

        // デフォルト規格を非表示にする.
        $DefaultProductClass = $this->productClassRepository->findOneBy([
            'Product' => $Product,
            'ClassCategory1' => null,
            'ClassCategory2' => null,
        ]);
        $DefaultProductClass->setVisible(false);

        $this->entityManager->flush();
    }


    protected function setProductClass($item, $ProductClasses, $Member)
    {
        foreach ($ProductClasses as $ProductClass) {
            if ($ProductClass->getClassCategory1() == $item->getClassCategory1() &&
                $ProductClass->getClassCategory2() == $item->getClassCategory2()){
                $item->setId($ProductClass->getId());
                $item->setCode($ProductClass->getCode());
                $item->setSaleLimit($ProductClass->getSaleLimit());
                $item->setPrice01($ProductClass->getPrice01());
                $item->setPrice02($ProductClass->getPrice02());
                $item->setDeliveryDuration($ProductClass->getDeliveryDuration());
                $item->setSaleType($ProductClass->getSaleType());

            }
        }
        return $item;
    }

    protected function mergeProductClasses($ProductClassesForMatrix, $ProductClasses)
    {
        $mergedProductClasses = [];
        foreach ($ProductClassesForMatrix as $pcfm) {
            foreach ($ProductClasses as $pc) {
                if ($pcfm->getClassCategory1()->getId() === $pc->getClassCategory1()->getId()) {
                    $cc2fm = $pcfm->getClassCategory2();
                    $cc2 = $pc->getClassCategory2();
                    if($pcfm->getMember() === $pc->getMember()){

                        if (null === $cc2fm && null === $cc2) {
                            $mergedProductClasses[] = $pc;
                            continue 2;
                        }

                        if ($cc2fm && $cc2 && $cc2fm->getId() === $cc2->getId()) {
                            $pc->setMember($pcfm->getMember());
                            $mergedProductClasses[] = $pc;
                            continue 2;
                        }
                    }
                }
            }

            $mergedProductClasses[] = $pcfm;
        }

        return $mergedProductClasses;
    }
}
