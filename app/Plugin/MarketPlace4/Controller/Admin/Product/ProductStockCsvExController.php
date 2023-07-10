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


use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\MemberRepository;
use Eccube\Entity\ProductImage;
use Eccube\Entity\ProductStock;
use Eccube\Entity\ProductTag;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ClassCategoryRepository;
use Eccube\Repository\DeliveryDurationRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\TagRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\CsvImportService;
use Eccube\Util\CacheUtil;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductStockCsvExController extends AbstractCsvImportController
{

    private $errors = [];

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;
    /**
     * @var ClassCategoryRepository
     */
    protected $classCategoryRepository;

    /**
     * ProductExController constructor.
     *
     * @param ProductClassRepository $productClassRepository
     * @param MemberRepository $memberRepository
     * @param ClassCategoryRepository $classCategoryRepository
     */
    public function __construct(
        ProductClassRepository $productClassRepository,
        MemberRepository $memberRepository,
        ClassCategoryRepository $classCategoryRepository
    ) {
        $this->productClassRepository = $productClassRepository;
        $this->memberRepository = $memberRepository;
        $this->classCategoryRepository = $classCategoryRepository;
    }

    /**
     *
     * @Route("/%eccube_admin_route%/product/ex/product_csv_inout", name="market_place4_admin_product_stock_csv_inout")
     * @Template("@MarketPlace4/admin/Product/product_stock_csv_inout.twig")
     *
     */
    public function csvInout(Request $request)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $headers = $this->getProductCsvHeader();
        if ($this->getUser()->getAuthority()->getId()>0){
            $headers = $this->getProductCsvHeaderMember();
        }
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
		    
                    $data = $this->getImportData($formFile);

                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return [
                            'form' => $form
                            ];
                    }

                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $columnHeaders = $data->getColumnHeaders();
                    if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, false);
                    }

                    $size = count($data);

                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, false);
                    }

                    $headerSize = count($columnHeaders);
                    $headerByKey = array_flip(array_map($getId, $headers));
		
                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    // $this->entityManager->getConnection()->beginTransaction();
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        $line = $data->key() + 1;
                        if ($headerSize != count($row)) {
                            $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                            $this->addErrors($message);

                            return $this->renderWithError($form, false);
                        }
                        if ($this->getUser()->getAuthority()->getId()==0) {
                            $member_department = $row[$headerByKey['department']];
                            $Member = $this->memberRepository->findOneBy(['name'=>$member_department]);
                            if (empty($Member)){
                                $Member = $this->memberRepository->find(1);
                            }
                        }else{
                            $Member = $this->getUser();
                        }
                        $product_code = $row[$headerByKey['code']];
                        $class_category = $row[$headerByKey['class_category']];
                        $sin_stock = $row[$headerByKey['sin_stock']];
                        $old_stock = $row[$headerByKey['old_stock']];

                        if ($class_category == '通販新品') $class_category = '新品';
                        if ($class_category == '自店') $class_category = '中古A';
                        if ($class_category == '通販Bﾗﾝｸ') $class_category = '中古B';
                        if ($class_category == '通販Cﾗﾝｸ') $class_category = '中古C';

                        $ClassCategory = $this->classCategoryRepository->findOneBy(['backend_name'=>$class_category]);

                        if (empty($ClassCategory)) continue;
                        $ProductClass = $this->productClassRepository->createQueryBuilder('pc')
                            ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                            ->andWhere('pc.ClassCategory1 = :ClassCategory')->setParameter('ClassCategory', $ClassCategory)
                            ->andWhere('pc.Member = :Member')->setParameter('Member', $Member)
                            ->getQuery()->getResult();
                        $stock = 0;
                        if ($class_category == '新品'){
                            $stock = $sin_stock;
                        }else{
                            $stock = $old_stock;
                        }
                        if ($stock<0) $stock = 0;

                        if (!empty($ProductClass)) {
                            $ProductClass1 = $ProductClass[0];
                            $ProductClass1->setStock($stock);
                            $ProductClass1->setVisible(true);
                            $ProductStock = $ProductClass1->getProductStock();
                            if (empty($ProductStock)){
                                $ProductStock = new ProductStock();
                                $ProductStock->setProductClass($ProductClass1);
                                $ProductStock->setStock($stock);
                            }else{
                                $ProductStock->setStock($stock);
                            }
                            $this->entityManager->persist($ProductClass1);
                            $this->entityManager->persist($ProductStock);
                            $this->entityManager->flush();

                        }else{
                            $ProductClassSamples = $this->productClassRepository->createQueryBuilder('pc')
                                ->where('pc.code = :product_code')->setParameter('product_code', $product_code)
                                ->andWhere('pc.ClassCategory1 = :ClassCategory')->setParameter('ClassCategory', $ClassCategory)
                                ->getQuery()->getResult();
                            if (!empty($ProductClassSamples)){
                                $ProductClassSample = $ProductClassSamples[0];
                                $ProductClass = new ProductClass();
                                $ProductClass
                                    ->setProduct($ProductClassSample->getProduct())
                                    ->setSaleType($ProductClassSample->getSaleType())
                                    ->setClassCategory1($ClassCategory)
                                    ->setCode($ProductClassSample->getCode())
                                    ->setStock($stock)
                                    ->setPrice02($ProductClassSample->getPrice02())
                                    ->setVisible(true)
                                    ->setCurrencyCode($ProductClassSample->getCurrencyCode())
                                    ->setMember($Member)
                                ;

                                $ProductStock = new ProductStock();
                                $ProductStock->setProductClass($ProductClass);
                                $ProductStock->setStock($stock);

                                $this->entityManager->persist($ProductClass);
                                $this->entityManager->persist($ProductStock);
                                $this->entityManager->flush();

                            }

                        }
                    }

                    //$this->entityManager->getConnection()->commit();
                    $message = 'admin.common.csv_upload_complete';
                    $this->session->getFlashBag()->add('eccube.admin.success', $message);
                }
            }
        }

        $this->removeUploadedFile();

        return [
            'form' => $form->createView(),
            'errors' => $this->errors,
        ];
    }

    protected function addErrors($message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }
    /**
     * 商品登録CSVヘッダー定義
     *
     * @return array
     */
    protected function getProductCsvHeader()
    {
        return [
            trans('店舗名') => [
                'id' => 'department',
                'description' => '店舗名',
                'required' => true,
            ],
            trans('仕入先') => [
                'id' => 'class_category',
                'description' => '仕入先',
                'required' => true,
            ],
            trans('商品コード') => [
                'id' => 'code',
                'description' => '商品コード',
                'required' => true,
            ],
            trans('商品名') => [
                'id' => 'product_name',
                'description' => '商品コード',
                'required' => true,
            ],
            trans('新品(買切分)数量') => [
                'id' => 'sin_stock',
                'description' => '新品(買切分)数量',
                'required' => true,
            ],
            trans('中古(買切分)数量') => [
                'id' => 'old_stock',
                'description' => '中古(買切分)数量',
                'required' => true,
            ]
        ];
    }
    /**
     * 商品登録CSVヘッダー定義
     *
     * @return array
     */
    protected function getProductCsvHeaderMember()
    {
        return [
            trans('仕入先') => [
                'id' => 'class_category',
                'description' => '仕入先',
                'required' => true,
            ],
            trans('商品コード') => [
                'id' => 'code',
                'description' => '商品コード',
                'required' => true,
            ],
            trans('商品名') => [
                'id' => 'product_name',
                'description' => '商品コード',
                'required' => true,
            ],
            trans('新品(買切分)数量') => [
                'id' => 'sin_stock',
                'description' => '新品(買切分)数量',
                'required' => true,
            ],
            trans('中古(買切分)数量') => [
                'id' => 'old_stock',
                'description' => '中古(買切分)数量',
                'required' => true,
            ]
        ];
    }
    protected function renderWithError($form, $rollback = true)
    {
        if ($this->hasErrors()) {
            if ($rollback) {
                $this->entityManager->getConnection()->rollback();
            }
        }

        $this->removeUploadedFile();

        return [
            'form' => $form->createView(),
            'errors' => $this->errors,
        ];
    }
}
