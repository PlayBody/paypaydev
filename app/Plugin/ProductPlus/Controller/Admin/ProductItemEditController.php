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

use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Repository\CsvRepository;
use Plugin\ProductPlus\Entity\ProductItem;
use Plugin\ProductPlus\Form\Type\Admin\ProductItemType;
use Plugin\ProductPlus\Repository\ProductItemRepository;
use Plugin\ProductPlus\Repository\ProductDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class ProductItemEditController extends \Eccube\Controller\AbstractController
{

    private $productItemRepository;
    private $productDataRepository;
    private $csvTypeRepository;
    private $csvRepository;

    public function __construct(
            ProductItemRepository $productItemRepository,
            ProductDataRepository $productDataRepository,
            CsvTypeRepository $csvTypeRepository,
            CsvRepository $csvRepository
            )
    {
        $this->productItemRepository = $productItemRepository;
        $this->productDataRepository = $productDataRepository;
        $this->csvTypeRepository = $csvTypeRepository;
        $this->csvRepository = $csvRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/product/productitem/edit", name="admin_product_productitem_new")
     * @Route("/%eccube_admin_route%/product/productitem/edit/{id}", requirements={"id" = "\d+"}, name="admin_product_productitem_edit")
     * @Template("@ProductPlus/admin/Product/edit.twig")
     */
    public function index(Request $request, $id = null)
    {
        // 編集
        if ($id) {
            $ProductItem = $this->productItemRepository->find($id);

            if (is_null($ProductItem)) {
                throw new NotFoundHttpException();
            }
        // 新規登録
        } else {
            $ProductItem = new ProductItem();
        }

        $OriginProductItem = clone $ProductItem;
        $form = $this->formFactory
            ->createBuilder(ProductItemType::class, $ProductItem)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $productItemId = $ProductItem->getId();
                if($this->productItemRepository->save($ProductItem)){
                    if(($OriginProductItem->getInputType() < ProductItem::SELECT_TYPE && $ProductItem->getInputType() >= ProductItem::SELECT_TYPE)
                        || ($OriginProductItem->getInputType() == ProductItem::IMAGE_TYPE && $ProductItem->getInputType() != ProductItem::IMAGE_TYPE)
                        ){
                        $productDatas  = $this->productDataRepository->findBy(['ProductItem' => $ProductItem]);
                        if($productDatas){
                            foreach($productDatas as $productData){
                                $this->entityManager->remove($productData);
                            }
                            $this->entityManager->flush();
                        }
                    }
                    if($OriginProductItem->getInputType() == ProductItem::CHECKBOX_TYPE && $ProductItem->getInputType() != ProductItem::CHECKBOX_TYPE){
                        $productDatas  = $this->productDataRepository->findBy(['ProductItem' => $ProductItem]);
                        if($productDatas){
                            foreach($productDatas as $productData){
                                $first = true;
                                foreach($productData->getDetails() as $Detail){
                                    if($first){
                                        $first = false;
                                        continue;
                                    }
                                    $productData->removeDetail($Detail);
                                    $this->entityManager->remove($Detail);
                                }
                                $this->entityManager->persist($productData);
                            }
                            $this->entityManager->flush();
                        }
                    }
                    // CSV項目登録
                    if(($OriginProductItem->getInputType() >= ProductItem::SELECT_TYPE && $ProductItem->getInputType() < ProductItem::SELECT_TYPE)
                            || ($OriginProductItem->getInputType() < ProductItem::SELECT_TYPE && $ProductItem->getInputType() >= ProductItem::SELECT_TYPE)
                            || !$productItemId){
                        $CsvType = $this->csvTypeRepository->find(\Eccube\Entity\Master\CsvType::CSV_TYPE_PRODUCT);
                        $sortNo = $this->entityManager->createQueryBuilder()
                                ->select('MAX(c.sort_no)')
                                ->from('Eccube\Entity\Csv','c')
                                ->where('c.CsvType = :csvType')
                                ->setParameter(':csvType',$CsvType)
                                ->getQuery()
                                ->getSingleScalarResult();
                        if (!$sortNo) {
                            $sortNo = 0;
                        }

                        if($productItemId){
                            $Csvs = $this->csvRepository->findBy(['entity_name' => 'Plugin\\ProductPlus\\Entity\\ProductData' , 'reference_field_name' => $productItemId]);
                            foreach($Csvs as $Csv){
                                $this->entityManager->remove($Csv);
                            }
                        }

                        if($ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                            $Csv = new \Eccube\Entity\Csv();
                            $Csv->setCsvType($CsvType);
                            $Csv->setEntityName('Plugin\\ProductPlus\\Entity\\ProductData');
                            $Csv->setFieldName('product_item_id');
                            $Csv->setReferenceFieldName($ProductItem->getId());
                            $Csv->setDispName($ProductItem->getName().trans('productplus.csv.common.id'));
                            $Csv->setEnabled(false);
                            $Csv->setSortNo(++$sortNo);
                            $Csv->setCreateDate(new \DateTime());
                            $Csv->setUpdateDate(new \DateTime());
                            $this->entityManager->persist($Csv);

                            $Csv = new \Eccube\Entity\Csv();
                            $Csv->setCsvType($CsvType);
                            $Csv->setEntityName('Plugin\\ProductPlus\\Entity\\ProductData');
                            $Csv->setFieldName('product_item_value');
                            $Csv->setReferenceFieldName($ProductItem->getId());
                            $Csv->setDispName($ProductItem->getName().trans('productplus.csv.common.name'));
                            $Csv->setEnabled(false);
                            $Csv->setSortNo(++$sortNo);
                            $Csv->setCreateDate(new \DateTime());
                            $Csv->setUpdateDate(new \DateTime());
                            $this->entityManager->persist($Csv);
                        }else{
                            $Csv = new \Eccube\Entity\Csv();
                            $Csv->setCsvType($CsvType);
                            $Csv->setEntityName('Plugin\\ProductPlus\\Entity\\ProductData');
                            $Csv->setFieldName('product_item_value');
                            $Csv->setReferenceFieldName($ProductItem->getId());
                            $Csv->setDispName($ProductItem->getName());
                            $Csv->setEnabled(false);
                            $Csv->setSortNo(++$sortNo);
                            $Csv->setCreateDate(new \DateTime());
                            $Csv->setUpdateDate(new \DateTime());
                            $this->entityManager->persist($Csv);
                        }
                    }else{
                        if($ProductItem->getInputType() >= ProductItem::SELECT_TYPE){
                            $Csv = $this->csvRepository->findOneBy(['entity_name' => 'Plugin\\ProductPlus\\Entity\\ProductData' , 'field_name' => 'product_item_id' ,'reference_field_name' => $productItemId]);
                            $Csv->setDispName($ProductItem->getName().trans('productplus.csv.common.id'));
                            $this->entityManager->persist($Csv);

                            $Csv = $this->csvRepository->findOneBy(['entity_name' => 'Plugin\\ProductPlus\\Entity\\ProductData' , 'field_name' => 'product_item_value' ,'reference_field_name' => $productItemId]);
                            $Csv->setDispName($ProductItem->getName().trans('productplus.csv.common.name'));
                            $this->entityManager->persist($Csv);

                        }else{
                            $Csv = $this->csvRepository->findOneBy(['entity_name' => 'Plugin\\ProductPlus\\Entity\\ProductData' , 'reference_field_name' => $productItemId]);
                            $Csv->setDispName($ProductItem->getName());
                            $this->entityManager->persist($Csv);
                        }
                    }

                    $this->entityManager->flush();

                    $this->addSuccess('admin.product.productitem.save.complete', 'admin');
                    return $this->redirectToRoute('admin_product_productitem_edit',['id' => $ProductItem->getId()]);
                }else{
                    $this->addError('admin.product.productitem.save.error', 'admin');
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
}