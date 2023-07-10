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

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Csv;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Entity\ProductCategory;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductImage;
use Eccube\Entity\ProductStock;
use Eccube\Entity\ProductTag;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\ProductType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\ProductImageRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\TagRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\CsvExportService;
use Eccube\Util\CacheUtil;
use Eccube\Util\EntityUtil;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

use Eccube\Repository\MemberRepository;
use Plugin\MarketPlace4\Repository\MarketPlace4ConfigRepository;

class ProductExController extends AbstractController
{

    private $stock_flag;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    /**
     * @var MarketPlace4ConfigRepository
     */
    protected $marketPlace4ConfigRepository;
    /**
     * @var MemberRepository
     */
    protected $memberRepository;
    protected $productMemberClassRepository;
    protected $productClassRepository;

    /**
     * ProductExController constructor.
     *
     * @param ProductRepository $productRepository
     * @param MemberRepository $memberRepository
     * @param CsvExportService $csvExportService
     * @param MarketPlace4ConfigRepository $marketPlace4ConfigRepository
     * @param ProductClassRepository $productClassRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        CsvExportService $csvExportService,
        MarketPlace4ConfigRepository $marketPlace4ConfigRepository,
        MemberRepository $memberRepository,
        ProductClassRepository $productClassRepository
    ) {
        $this->memberRepository = $memberRepository;
        $this->productRepository = $productRepository;
        $this->csvExportService = $csvExportService;
        $this->marketPlace4ConfigRepository = $marketPlace4ConfigRepository;
        $this->productClassRepository = $productClassRepository;
    }


    /**
     * @Route("/%eccube_admin_route%/product/ex/classes/{id}/load", name="admin_productex_classes_load", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@MarketPlace4/admin/Product/product_class_popup_ex.twig")
     * @ParamConverter("Product")
     */
    public function loadProductClasses(Request $request, Product $Product)
    {
//         if (!$request->isXmlHttpRequest()) {
//             throw new BadRequestHttpException();
//         }

        /** @var $Product ProductRepository */
        if (!$Product) {
            throw new NotFoundHttpException();
        }

        if ($this->getUser()->getAuthority()->getId()>0){
            $Members = $this->memberRepository->findBy(['id'=>$this->getUser()->getId()]);
        }else{
            $Members = $this->memberRepository->findBy([], ['Authority'=>'asc', 'sort_no' => 'DESC']);
        }

        $data = [];
        foreach ($Members as $Member) {
            $class_data = [];
            $class_data['Member'] = $Member;
            $class_data['data'] = $this->productClassRepository->createQueryBuilder('pc')
                ->where('pc.Product = :Product')->setParameter('Product', $Product)
                ->andWhere('pc.Member = :Member')->setParameter('Member', $Member)
                ->andWhere('pc.visible = true')
                ->getQuery()->getResult();

            $data[] = $class_data;
        };

        $view = 'MarketPlace4/Resource/template/admin/Product/product_class_popup_ex.twig';

        return $this->render($view, array(
            'data' => $data,
            'Product' => $Product
        ));
    }

    /**
     * 
     *
     * @Route("/%eccube_admin_route%/products/ex/load/classes", name="admin_productex_load_class")
     *
     * @param Request $request
     * @return bool|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function loadProductDetailClasses(Request $request)
    {
         if (!$request->isXmlHttpRequest()) {
             throw new BadRequestHttpException();
         }

        $product_id = $request->get('id');
        $Product = $this->productRepository->find($product_id);
        /** @var $Product ProductRepository */
        if (!$Product) {
            throw new NotFoundHttpException();
        }
        if ($this->getUser()->getAuthority()->getId()>0){
            $Members = $this->memberRepository->findBy(['id'=>$this->getUser()->getId()]);
        }else{
            $Members = $this->memberRepository->findBy([], ['Authority'=>'asc', 'sort_no' => 'DESC']);
        }

        $data = [];
        foreach ($Members as $Member) {
            $class_data = [];
            $class_data['Member'] = $Member;
            $class_data['data'] = $this->productClassRepository->createQueryBuilder('pc')
                ->where('pc.Product = :Product')->setParameter('Product', $Product)
                ->andWhere('pc.Member = :Member')->setParameter('Member', $Member)
                ->getQuery()->getResult();

            $data[] = $class_data;
        };

        $view = 'MarketPlace4/Resource/template/admin/Product/product_class_popup_ex.twig';
        
        return $this->render($view, array(
            'data' => $data,
            'Product' => $Product
        ));

    }
    /**
     * 商品在庫CSVの出力.
     *
     * @Route("/%eccube_admin_route%/product/market_place4/stock_export", name="market_place4_admin_product_stock_export")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function export(Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);
        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request) {
            $Authority = $this->getUser()->getAuthority()->getId();
            // CSV種別を元に初期化.
//            $this->csvExportService->initCsvType(CsvType::CSV_TYPE_PRODUCT);
            $PluginConfig = $this->marketPlace4ConfigRepository->find(1);
            $this->csvExportService->initCsvType($PluginConfig->getCsvType());
            // ヘッダ行の出力.

            $row = [];
            foreach ($this->csvExportService->getCsvs() as $Csv) {
                if ($Authority > 0 && $Csv->getDispName() == '店舗名') continue;
                $row[] = $Csv->getDispName();
            }
            $this->csvExportService->fopen();
            $this->csvExportService->fputcsv($row);
            $this->csvExportService->fclose();

            $qb = $this->productClassRepository->createQueryBuilder('pc')
                ->leftJoin('pc.Member', 'm')
                ->leftJoin('m.Authority', 'auth')
                ->where('pc.ClassCategory1 is not null')
                ->andWhere('pc.visible = true')
            ;
            if ($Authority > 0){
                $qb = $qb->andWhere('m = :Member')->setParameter('Member', $this->getUser());
            }
            $qb->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->orderBy('auth.id', 'ASC')
                ->addOrderBy('m.sort_no', 'DESC')
                ->addOrderBy('pc.ClassCategory1', 'ASC');

            $qb->select('pc')
                    ->distinct();
            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);

            $this->csvExportService->exportData(function ($entity, CsvExportService $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();

                /** @var $ProductClass \Eccube\Entity\ProductClass */
                $ProductClass = $entity;

                $ExportCsvRow = new ExportCsvRow();

                    // CSV出力項目と合致するデータを取得.
                foreach ($Csvs as $Csv) {
                    if ($this->getUser()->getAuthority()->getId() > 0 && $Csv->getDispName() == '店舗名') continue;

                    // 商品データを検索.
                    $ExportCsvRow->setData($this->getData($Csv, $ProductClass));
                    if ($ExportCsvRow->isDataNull()) {
                        // 商品規格情報を検索.
                        $ExportCsvRow->setData($this->getData($Csv, $ProductClass));
                    }

                    $ExportCsvRow->pushData();
                }

                // 出力.
                $csvService->fputcsv($ExportCsvRow->getRow());
            });
       });

        $now = new \DateTime();
        $filename = '本店_メーカー別在庫一覧_'.$now->format('YmdHis').'.csv';
        if ($this->getUser()->getAuthority()->getId() > 0){
            $filename = $this->getUser()->getName() . '_仕入先別在庫一覧_'.$now->format('YmdHis').'.csv';
        }
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->send();

        log_info('商品在庫CSV出力ファイル名', [$filename]);

        return $response;
    }
    public function getData(Csv $Csv, $entity)
    {
        // エンティティ名が一致するかどうかチェック.
        $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
        $entityName = ClassUtils::getClass($entity);

        if ($csvEntityName !== $entityName) {
            return null;
        }
        // カラム名がエンティティに存在するかどうかをチェック.
        if (!$entity->offsetExists($Csv->getFieldName())) {
            return null;
        }

        // データを取得.
        $data = $entity->offsetGet($Csv->getFieldName());
        if ($Csv->getFieldName() == 'ClassCategory1'){
            if ($data->getBackEndName() == '新品'){
                $this->stock_flag = 'sin';
                return '通販新品';
            }
            if ($data->getBackEndName()=='中古A'){
                $this->stock_flag = 'jong';
                return '自店';
            }
            if ($data->getBackEndName()=='中古B'){
                $this->stock_flag = 'jong';
                return '通販Bﾗﾝｸ';
            }
            if ($data->getBackEndName()=='中古C'){
                $this->stock_flag = 'jong';
                return '通販Cﾗﾝｸ';
            }
        }

        // one to one の場合は, dtb_csv.reference_field_name, 合致する結果を取得する.
        if ($data instanceof \Eccube\Entity\AbstractEntity) {
            if (EntityUtil::isNotEmpty($data)) {
                return $data->offsetGet($Csv->getReferenceFieldName());
            }
        } elseif ($data instanceof \Eccube\Entity\Product) {
            if (EntityUtil::isNotEmpty($data)) {
                return $data->offsetGet($Csv->getReferenceFieldName());
            }
        } elseif ($data instanceof \Doctrine\Common\Collections\Collection) {
            // one to manyの場合は, カンマ区切りに変換する.
            $array = [];
            foreach ($data as $elem) {
                if (EntityUtil::isNotEmpty($elem)) {
                    $array[] = $elem->offsetGet($Csv->getReferenceFieldName());
                }
            }

            return implode($this->eccubeConfig['eccube_csv_export_multidata_separator'], $array);
        } elseif ($data instanceof \DateTime) {
            // datetimeの場合は文字列に変換する.
            return $data->format($this->eccubeConfig['eccube_csv_export_date_format']);
        } else {
            // スカラ値の場合はそのまま.
            if ($Csv->getDispName() == '新品(買切分)数量'){
                if ($this->stock_flag=='jong'){
                    return null;
                }
            }
            if ($Csv->getDispName() == '中古(買切分)数量'){
                if ($this->stock_flag=='sin'){
                    return null;
                }
            }
            return $data;
        }

        return null;
    }
}
