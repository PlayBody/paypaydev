<?php

namespace Plugin\ProductDisplayRank4\Controller\Admin;

use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Product;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Util\CacheUtil;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvImportController extends AbstractCsvImportController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    private $errors = [];

    /**
     * CsvImportController constructor.
     *
     * @throws \Exception
     */
    public function __construct(
        ProductRepository $productRepository,
        BaseInfoRepository $baseInfoRepository,
        ValidatorInterface $validator
    ) {
        $this->productRepository = $productRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->validator = $validator;
    }

    /**
     * 登録CSVアップロード
     *
     * @Route("/%eccube_admin_route%/product/product_display_rank_csv_upload", name="admin_product_display_rank_csv_import")
     * @Template("@ProductDisplayRank4/admin/Product/csv_display_rank.twig")
     * @param Request $request
     * @param CacheUtil $cacheUtil
     * @return array
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function index(Request $request, CacheUtil $cacheUtil)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();

        $headers = $this->getProductDisplayRankCsvHeader();
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    log_info('商品表示順CSV登録開始');
                    $data = $this->getImportData($formFile);
                    if ($data === false) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $headerByKey = array_flip(array_map($getId, $headers));

                    $columnHeaders = $data->getColumnHeaders();
                    if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $size = count($data);
                    if ($size < 1) {
                        $this->addErrors(trans('admin.common.csv_invalid_no_data'));

                        return $this->renderWithError($form, $headers, false);
                    }
                    $this->entityManager->getConfiguration()->setSQLLogger(null);
                    $this->entityManager->getConnection()->beginTransaction();
                    // CSVファイルの登録処理
                    foreach ($data as $row) {
                        /* @var $Product Product */
                        if (isset($row[$headerByKey['id']]) && strlen($row[$headerByKey['id']]) > 0) {
                            if (!preg_match('/^\d+$/', $row[$headerByKey['id']])) {
                                $this->addErrors(($data->key() + 1).'行目の商品IDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                            $Product = $this->productRepository->find($row[$headerByKey['id']]);
                            if (!$Product) {
                                $this->addErrors(($data->key() + 1).'行目の更新対象の商品IDが存在しません。');

                                return $this->renderWithError($form, $headers);
                            }
                        }
                        if (!isset($row[$headerByKey['display_rank']]) || StringUtil::isBlank($row[$headerByKey['display_rank']])) {
                            $this->addErrors(($data->key() + 1).'行目の表示順が設定されていません。');
                            return $this->renderWithError($form, $headers);
                        } else if(!preg_match('/^\d+$/', $row[$headerByKey['display_rank']])){
                            $this->addErrors(($data->key() + 1).'行目の表示順に数字以外の文字が含まれています');
                        } else {
                            $Product->setDisplayRank(StringUtil::trimAll($row[$headerByKey['display_rank']]));
                        }

                        if ($this->hasErrors()) {
                            return $this->renderWithError($form, $headers);
                        }

                        $this->entityManager->flush($Product);
                    }

                    $this->entityManager->getConnection()->commit();
                    log_info('商品表示順CSV登録完了');
                    $message = 'admin.common.csv_upload_complete';
                    $this->session->getFlashBag()->add('eccube.admin.success', $message);

                    $cacheUtil->clearDoctrineCache();
                }
            }
        }

        return $this->renderWithError($form, $headers);
    }

    /**
     * アップロード用CSV雛形ファイルダウンロード
     *
     * @Route("/%eccube_admin_route%/product/product_display_rank/csv_template",  name="admin_product_product_display_rank_csv_template")
     *
     * @return StreamedResponse
     */
    public function csvTemplate(Request $request)
    {
        $headers = $this->getProductDisplayRankCsvHeader();
        $filename = 'product_display_rank.csv';

        return $this->sendTemplateResponse($request, array_keys($headers), $filename);
    }

    /**
     * 登録、更新時のエラー画面表示
     *
     * @param FormInterface $form
     * @param array $headers
     * @param bool $rollback
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function renderWithError($form, $headers, $rollback = true)
    {
        if ($this->hasErrors()) {
            if ($rollback) {
                $this->entityManager->getConnection()->rollback();
            }
        }

        $this->removeUploadedFile();

        return [
            'form' => $form->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
        ];
    }

    /**
     * 登録、更新時のエラー画面表示
     */
    protected function addErrors($message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * 商品表示順CSVヘッダー定義
     */
    protected function getProductDisplayRankCsvHeader()
    {
        return [
            trans('admin.product.product_display_rank_csv.product_id_col') => [
                'id' => 'id',
                'description' => 'admin.product.product_display_rank_csv.product_id_description',
                'required' => true,
            ],
            trans('admin.product.product_display_rank_csv.product_display_rank_col') => [
                'id' => 'display_rank',
                'description' => 'admin.product.product_display_rank_csv.product_display_rank_description',
                'required' => true,
            ],
        ];
    }
}
