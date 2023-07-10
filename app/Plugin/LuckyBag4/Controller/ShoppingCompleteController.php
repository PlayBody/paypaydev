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


namespace Plugin\LuckyBag4\Controller;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Csv;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\MailHistory;
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
use Eccube\Repository\OrderRepository;
use Eccube\Repository\CustomerRepository;
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
use Plugin\LuckyBag4\Repository\LuckyBag4ConfigRepository;

class ShoppingCompleteController extends AbstractController
{

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var LuckyBag4ConfigRepository
     */
    protected $configRepository;
    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * ProductExController constructor.
     *
     * @param ProductClassRepository $productClassRepository
     * @param OrderRepository $orderRepository
     * @param LuckyBag4ConfigRepository $configRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param CustomerRepository $customerRepository
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
     */
    public function __construct(
        ProductClassRepository $productClassRepository,
        OrderRepository $orderRepository,
        LuckyBag4ConfigRepository $configRepository,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        \Swift_Mailer $mailer,
        \Twig_Environment $twig
    ) {
        $this->productClassRepository = $productClassRepository;
        $this->orderRepository = $orderRepository;
        $this->configRepository = $configRepository;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->customerRepository = $customerRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * @Route("/shopping_complete/get_product_image", name="lucky_bag4_shopping_complete_select_product", methods={"GET"})
     * @Template("@LuckyBag4/Shopping/complete_view_product_image.twig")
     */
    public function loadProductClasses(Request $request)
    {
         if (!$request->isXmlHttpRequest()) {
             throw new BadRequestHttpException();
         }

        $id = $request->get('id');
        $Order = $this->orderRepository->find($id);

        if (!$Order) {
            throw new NotFoundHttpException();
        }
        $data = [];
        foreach ($Order->getOrderItems() as $OrderItem){
            if ($OrderItem->isProduct()){
                $temp = [];
                $ProductClass = $OrderItem->getProductClass();
                if (!empty($ProductClass)){
                    $ProductImage = $ProductClass->getProduct()->getProductImage()[0];
                    $image_file_name = $ProductImage->getFileName();
                    $temp['image'] = $image_file_name;
                    $temp['ProductClass'] = $ProductClass;
                }else{
                    $temp['image'] = 'point_back.png';
                    $temp['points'] = $OrderItem->getPointRate().'points';
                }
                $data[] = $temp;
            }
        }

        $this->sendMail($Order);

        return [
            'data' => $data,
        ];
    }

    protected function sendMail($Order){
        $Config = $this->configRepository->find(1);

        $MailTemplate = $Config->getMailTemplate();

        $BaseInfo = $this->baseInfoRepository->find(1);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Member' => $this->getUser()->getUsername(),
            'Order' => $Order
        ]);

        $message = (new \Swift_Message())
            ->setSubject($MailTemplate->getMailSubject())
            ->setFrom([$BaseInfo->getEmail01() => $BaseInfo->getShopName()])
            ->setTo([$this->getUser()->getUsername()]);
        $message->setBody($body);

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        $this->entityManager->persist($MailHistory);
        $this->entityManager->flush();

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Member' => $BaseInfo->getShopName(),
            'Order' => $Order
        ]);

        $message = (new \Swift_Message())
            ->setSubject('[ '. $BaseInfo->getShopName() .' ] ' . $MailTemplate->getMailSubject())
            ->setFrom([$BaseInfo->getEmail01() => $BaseInfo->getShopName()])
            ->setTo([$BaseInfo->getEmail01()]);
        $message->setBody($body);

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        $this->entityManager->persist($MailHistory);
        $this->entityManager->flush();

    }
}
