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

namespace Plugin\ProductPlus\Controller\Admin;

use Plugin\ProductPlus\Entity\ProductItemOption;
use Plugin\ProductPlus\Form\Type\Admin\ProductItemOptionType;
use Plugin\ProductPlus\Repository\ProductItemRepository;
use Plugin\ProductPlus\Repository\ProductItemOptionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductItemOptionEditController extends \Eccube\Controller\AbstractController
{

    private $productItemRepository;
    private $productItemOptionRepository;

    public function __construct(
            ProductItemRepository $productItemRepository,
            ProductItemOptionRepository $productItemOptionRepository
            )
    {
        $this->productItemRepository = $productItemRepository;
        $this->productItemOptionRepository = $productItemOptionRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem/option/{item_id}" , requirements={"item_id" = "\d+"}, name="admin_product_productitem_option")
     * @Route("/%eccube_admin_route%/product/productitem/option/{item_id}/new", requirements={"item_id" = "\d+"}, name="admin_product_productitem_option_new")
     * @Route("/%eccube_admin_route%/product/productitem/option/{item_id}/edit/{id}", requirements={"item_id" = "\d+", "id" = "\d+"}, name="admin_product_productitem_option_edit")
     * @Template("@ProductPlus/admin/Product/option.twig")
     */
    public function index(Request $request, $item_id, $id = null)
    {
        //
        $ProductItem = $this->productItemRepository->find($item_id);
        if (!$ProductItem) {
            throw new NotFoundHttpException();
        }
        if ($id) {
            $TargetOption = $this->productItemOptionRepository->find($id);
            if (!$TargetOption || $TargetOption->getProductItem() != $ProductItem) {
                throw new NotFoundHttpException();
            }
        } else {
            $TargetOption = new ProductItemOption();
            $TargetOption->setProductItem($ProductItem);
        }

        //
        $form = $this->formFactory
            ->createBuilder(ProductItemOptionType::class, $TargetOption)
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $status = $this->productItemOptionRepository->save($TargetOption);

                if ($status) {
                    $this->addSuccess('admin.product.productitem.option.save.complete', 'admin');

                    return $this->redirectToRoute('admin_product_productitem_option', ['item_id' => $ProductItem->getId()]);
                } else {
                    $this->addError('admin.product.productitem.option.save.error', 'admin');
                }
            }
        }

        $Options = $this->productItemOptionRepository->getList($ProductItem);

        return [
            'form' => $form->createView(),
            'ProductItem' => $ProductItem,
            'Options' => $Options,
            'TargetOption' => $TargetOption,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem/option/{item_id}/{id}/delete", requirements={"item_id" = "\d+","id" = "\d+"}, name="admin_product_productitem_option_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $item_id, $id)
    {
        $this->isTokenValid();
        //
        $ProductItem = $this->productItemRepository->find($item_id);
        if (!$ProductItem) {
            throw new NotFoundHttpException();
        }
        $TargetOption = $this->productItemOptionRepository->find($id);
        if (!$TargetOption || $TargetOption->getProductItem() != $ProductItem) {
            throw new NotFoundHttpException();
        }

        $status = false;
        $status = $this->productItemOptionRepository->delete($TargetOption);

        if ($status === true) {
            $this->addSuccess('admin.product.productitem.option.delete.complete', 'admin');
        } else {
            $this->addError('admin.product.productitem.option.delete.error', 'admin');
        }

        return $this->redirectToRoute('admin_product_productitem_option', ['item_id' => $ProductItem->getId()]);
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem/option/sort_no/move", name="admin_product_productitem_option_sort_no_move",methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $optionId => $sortNo) {
                $Option = $this->productItemOptionRepository
                    ->find($optionId);
                $Option->setSortNo($sortNo);
                $this->entityManager->persist($Option);
            }
            $this->entityManager->flush();
        }
        return new Response();
    }
}