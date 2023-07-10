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

use Eccube\Repository\CsvRepository;
use Plugin\ProductPlus\Entity\ProductItem;
use Plugin\ProductPlus\Repository\ProductItemRepository;
use Plugin\ProductPlus\Repository\ProductDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductItemController extends \Eccube\Controller\AbstractController
{
    private $productItemRepository;
    private $productDataRepository;
    private $csvRepository;

    public function __construct(
            ProductItemRepository $productItemRepository,
            ProductDataRepository $productDataRepository,
            CsvRepository $csvRepository
            )
    {
        $this->productItemRepository = $productItemRepository;
        $this->productDataRepository = $productDataRepository;
        $this->csvRepository = $csvRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem", name="admin_product_productitem")
     * @Template("@ProductPlus/admin/Product/index.twig")
     */
    public function index(Request $request)
    {
        $arrTYPE = [];
        $arrTYPE[ProductItem::TEXT_TYPE] = trans('productplus.form.productitem.choice.text');
        $arrTYPE[ProductItem::TEXTAREA_TYPE] = trans('productplus.form.productitem.choice.textarea');
        $arrTYPE[ProductItem::SELECT_TYPE] = trans('productplus.form.productitem.choice.select');
        $arrTYPE[ProductItem::RADIO_TYPE] = trans('productplus.form.productitem.choice.radio');
        $arrTYPE[ProductItem::CHECKBOX_TYPE] = trans('productplus.form.productitem.choice.checkbox');
        $arrTYPE[ProductItem::IMAGE_TYPE] = trans('productplus.form.productitem.choice.image');
        $arrTYPE[ProductItem::DATE_TYPE] = trans('productplus.form.productitem.choice.date');

        $ProductItems = $this->productItemRepository->getList();

        return [
            'ProductItems' => $ProductItems,
            'arrTYPE' => $arrTYPE,
            'isExtend' => class_exists('\Plugin\SearchPlus\Form\Extension\Admin\ProductItemExtension'),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem/{id}/delete", requirements={"id" = "\d+"}, name="admin_product_productitem_delete",methods={"DELETE"})
     */
    public function delete(Request $request, $id)
    {
        $this->isTokenValid();

        $TargetProductItem = $this->productItemRepository->find($id);
        if (!$TargetProductItem) {
            throw new NotFoundHttpException();
        }

        $status = false;
        $ProductDatas = $this->productDataRepository->findBy(['ProductItem' => $TargetProductItem]);
        foreach($ProductDatas as $ProductData){
            $this->entityManager->remove($ProductData);
        }
        $Csvs = $this->csvRepository->findBy(['entity_name' => 'Plugin\\ProductPlus\\Entity\\ProductData' , 'reference_field_name' => $TargetProductItem->getId()]);

        foreach($Csvs as $Csv){
            if(!is_null($Csv)){
                $this->entityManager->remove($Csv);
            }
        }
        $this->entityManager->flush();
        $status = $this->productItemRepository->delete($TargetProductItem);

        if ($status === true) {
            $this->addSuccess('admin.product.productitem.delete.complete', 'admin');
        } else {
            $this->addError('admin.product.productitem.delete.error', 'admin');
        }

        return $this->redirectToRoute('admin_product_productitem');
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem/sort_no/move", name="admin_product_productitem_sort_no_move",methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $itemId => $sortNo) {
                $Item = $this->productItemRepository
                    ->find($itemId);
                $Item->setSortNo($sortNo);
                $this->entityManager->persist($Item);
            }
            $this->entityManager->flush();
        }
        return new Response();
    }
}