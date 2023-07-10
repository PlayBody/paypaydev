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


namespace Plugin\LuckyBag4\Controller\Admin\Product;

use Doctrine\ORM\NoResultException;
use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Admin\ProductClassMatrixType;
use Eccube\Util\CacheUtil;
use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Plugin\LuckyBag4\Entity\ProductLucky;
use Plugin\LuckyBag4\Form\Type\Admin\ProductLuckyMatrixType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Eccube\Repository\MemberRepository;
use Eccube\Repository\PluginRepository;
use Eccube\Repository\ProductClassRepository;
use Plugin\LuckyBag4\Repository\ProductLuckyRepository;
use Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository;



class ProductLuckyController extends AbstractController
{
    /** @var ProductRepository */
    protected $productRepository;

    /** @var ProductLuckyRepository */
    protected $productLuckyRepository;

    /** @var LuckyBag4ConfigRepository */
    protected $luckyBag4ConfigRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;
    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * ProductExController constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductLuckyRepository $productLuckyRepository
     * @param LuckyBag4ConfigRepository $luckyBag4ConfigRepository
     * @param MemberRepository $memberRepository
     * @param PluginRepository $pluginRepository
     * @param ProductClassRepository $productClassRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductLuckyRepository $productLuckyRepository,
        LuckyBag4ConfigRepository $luckyBag4ConfigRepository,
        MemberRepository $memberRepository,
        PluginRepository $pluginRepository,
        ProductClassRepository $productClassRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productLuckyRepository = $productLuckyRepository;
        $this->luckyBag4ConfigRepository = $luckyBag4ConfigRepository;
        $this->memberRepository = $memberRepository;
        $this->pluginRepository = $pluginRepository;
        $this->productClassRepository = $productClassRepository;
    }


    /**
     * @Route("/%eccube_admin_route%/product/product/lucky/{id}", requirements={"id" = "\d+"}, name="plg_product_lucky4_admin_product_product_lucky")
     * @Template("@LuckyBag4/admin/Product/product_lucky.twig")
     */
    public function index(Request $request, $id, CacheUtil $cacheUtil)
    {
        $Product = $this->productRepository->find($id);
        if (!$Product) {
            throw new NotFoundHttpException();
        }

        $ProductLuckies = $this->productLuckyRepository->findBy(['Product'=>$Product]);

        $ProductLuckies = $this->createProductLuckies($ProductLuckies);

        $builder = $this->formFactory->createBuilder(ProductLuckyMatrixType::class, [
            'product_luckies' => $ProductLuckies
        ], ['csrf_protection'=> false]);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // フォームではtokenを無効化しているのでここで確認する.
            $this->isTokenValid();

            $isRateCheck = $this->isRateCheck($form['product_luckies']->getData());

            if ($isRateCheck){
                $this->saveProductLuckies($Product, $form['product_luckies']->getData());

                $this->addSuccess('admin.common.save_complete', 'admin');

                $cacheUtil->clearDoctrineCache();

                if ($request->get('return_product_list')) {
                    return $this->redirectToRoute('plg_product_lucky4_admin_product_product_lucky', ['id' => $Product->getId(), 'return_product_list' => true]);
                }

                return $this->redirectToRoute('plg_product_lucky4_admin_product_product_lucky', ['id' => $Product->getId()]);
            }else{
                $this->addError('plg_lucky_bag4.form_error.lucky_rate_sum_error', 'admin');
            }

        }
        return [
            'form' => $form->createView(),
            'clearForm' => $this->createForm(FormType::class)->createView(),
            'Product' => $Product,
            'return_product_list' => $request->get('return_product_list') ? true : false,
        ];
    }
    /**
     * @Route("/%eccube_admin_route%/product/product/lucky/list", requirements={"id" = "\d+"}, name="plg_product_lucky4_admin_product_product_lucky_list")
     * @Template("@LuckyBag4/admin/Product/product_lucky_list.twig")
     */
    public function productLuckyList(Request $request)
    {
         if (!$request->isXmlHttpRequest()) {
             throw new BadRequestHttpException();
         }
        $product_id = $request->get('id');
        $Product = $this->productRepository->find($product_id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        $ProductLuckies = $this->productLuckyRepository->findBy(['Product'=>$Product]);

        return [
            'ProductLuckies' => $ProductLuckies
        ];
    }

    protected function createProductLuckies($ExistProductLuckies)
    {
        $ProductLuckies = [];

        $ii=0;
        foreach ($ExistProductLuckies as $pl){
            $ProductLuckies[] = $pl;
            $ii++;
        }

        $max = 10;
        $Config = $this->luckyBag4ConfigRepository->find(1);
        if ($Config->getProductLuckyMax()){
            $max = $Config->getProductLuckyMax();
        }

        for ($i=$ii;$i<$max;$i++){
            $ProductLucky = new ProductLucky();
            $ProductLuckies[] = $ProductLucky;
        }

        return $ProductLuckies;
    }

    protected function saveProductLuckies(Product $Product, $ProductLuckies = [])
    {
        foreach ($ProductLuckies as $pl) {
            if (!$pl->getId() && !$pl->isVisible()) {
                continue;
            }
            if ($pl->getId() && !$pl->isVisible()) {
                $this->entityManager->remove($pl);
                $this->entityManager->flush();
                continue;
            }

            $pl->setProduct($Product);
            $this->entityManager->persist($pl);
            $this->entityManager->flush();
        }

        $Admin = $this->memberRepository->find(1);
        // if MarketPlacePlugin
        $Plugin = $this->pluginRepository->findOneBy(['code'=>'MarketPlace4']);
        if (!empty($Plugin)) {
            $ProductClass = $this->productClassRepository->findOneBy(['Product'=>$Product, 'visible'=>'true']);
            $ProductClass->setMember($Admin);
            $this->entityManager->persist($ProductClass);
            $this->entityManager->flush();

        }
    }

    protected function isRateCheck($ProductLuckies = [])
    {
        $sum = 0;
        foreach ($ProductLuckies as $pl) {
            if (!$pl->isVisible()) {
                continue;
            }
            $sum += $pl->getLuckyRate();
        }

        return ($sum==100);
    }

    /**
     * @Route("/%eccube_admin_route%/product/product/lucky/{id}/clear", requirements={"id" = "\d+"}, name="plg_lucky_bag4_admin_product_lucky_clear")
     */
    public function clearProductLuckies(Request $request, Product $Product, CacheUtil $cacheUtil)
    {

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ProductLuckies = $this->productLuckyRepository->findBy([
                'Product' => $Product,
            ]);

            // デフォルト規格のみ有効にする
            foreach ($ProductLuckies as $pl) {
                $this->entityManager->remove($pl);
            }

            $this->entityManager->flush();

            $this->addSuccess('admin.product.reset_complete', 'admin');

            $cacheUtil->clearDoctrineCache();
        }

        if ($request->get('return_product_list')) {
            return $this->redirectToRoute('plg_product_lucky4_admin_product_product_lucky', ['id' => $Product->getId(), 'return_product_list' => true]);
        }

        return $this->redirectToRoute('plg_product_lucky4_admin_product_product_lucky', ['id' => $Product->getId()]);
    }
}
