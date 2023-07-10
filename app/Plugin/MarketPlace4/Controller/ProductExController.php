<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MarketPlace4\Controller;

use Eccube\Controller\AbstractController;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\CartItem;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Master\ProductListMaxType;
use Eccube\Form\Type\Master\ProductListOrderByType;
use Eccube\Form\Type\SearchProductType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\Master\ProductListMaxRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Eccube\Repository\MemberRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\CartItemRepository;

class ProductExController extends AbstractController
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
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
     * @var CartItemRepository
     */
    protected $cartItemRepository;
    /**
     * @var CartService
     */
    protected $cartService;
    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    private $title = '';

    /**
     * ProductController constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductClassRepository $productClassRepository
     * @param MemberRepository $memberRepository
     * @param CartItemRepository $cartItemRepository
     * @param CartService $cartService
     * @param PurchaseFlow $purchaseFlow
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductClassRepository $productClassRepository,
        MemberRepository $memberRepository,
        CartItemRepository $cartItemRepository,
        CartService $cartService,
        PurchaseFlow $purchaseFlow
    ) {
        $this->productRepository = $productRepository;
        $this->productClassRepository = $productClassRepository;
        $this->memberRepository = $memberRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->cartService = $cartService;
        $this->purchaseFlow = $purchaseFlow;
    }


    /**
     * @Route("/products/detail/shop", name="product_ex_detail_shop")
     *
     */
    public function shop(Request $request)
    {
        $product_id =$request->get('id');

        $Product = $this->productRepository->find($product_id);

        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }


        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $Members = $this->memberRepository->createQueryBuilder('m')
            ->orderBy('m.Authority', 'ASC')
            ->addOrderBy('m.sort_no', 'DESC')
            ->getQuery()
            ->getResult();

        $ProductClassesByMember = [];

        foreach ($Members as $Member) {
            $tmpMember = [];
            $tmpMember['list'] = $this->productClassRepository->findBy(['Member'=>$Member, 'Product'=>$Product, 'visible'=>true]);
            if (empty($tmpMember['list'])) continue;
            $tmpMember['member_id'] = $Member->getId();
            $tmpMember['member_name'] = $Member->getName();
            $ProductClassesByMember[]=$tmpMember;
        }

        //$data = $this->productClassRepository->getList($Product);

        //var_dump($data);die();

        $is_favorite = false;
        $view = 'MarketPlace4/Resource/template/Product/product_detail_shop.twig';
        return $this->render($view, array(
            'Members' => $Members,
            'ProductClassesByMember' => $ProductClassesByMember,
            'title' => $this->title,
            'subtitle' => $Product->getName(),
            'form' => $builder->getForm()->createView(),
            'Product' => $Product,
            'is_favorite' => $is_favorite,
        ));
    }
    /**
     * カートに追加.
     *
     * @Route("/products/ex/add_cart/{id}", name="product_ex_add_cart", methods={"POST"}, requirements={"id" = "\d+"})
     */
    public function addCartEx(Request $request, Product $Product)
    {
        // エラーメッセージの配列
        $errorMessages = [];
        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }
        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            throw new NotFoundHttpException();
        }

        $addCartData = $form->getData();

        log_info(
            'カート追加処理開始',
            [
                'product_id' => $Product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
            ]
        );

        $quantity = $addCartData['quantity'];

        while ($quantity>0){


            $cart = $this->cartService->getCart();
            $items = [];
            if (!empty($cart)) $items = $cart->getCartItems();

            $ProductClass = $this->setProductClass($addCartData['product_class_id'], $items);

            if (empty($ProductClass)) break;
            if ($ProductClass->getStock() >= $quantity) {
                $cart_quantitiy = $quantity;
                $quantity=0;
            }else{
                $cart_quantitiy = $ProductClass->getStock();
                $quantity = $quantity - $cart_quantitiy;
            }
            $this->cartService->addProduct($ProductClass, $cart_quantitiy);

            $Carts = $this->cartService->getCarts();

            foreach ($Carts as $Cart) {
                $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
                // 復旧不可のエラーが発生した場合は追加した明細を削除.
                if ($result->hasError()) {
                    $this->cartService->removeProduct($addCartData['product_class_id']);
                    foreach ($result->getErrors() as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                }
                foreach ($result->getWarning() as $warning) {
                    $errorMessages[] = $warning->getMessage();
                }
            }

            $this->cartService->save();
        }


        // 明細の正規化

        log_info(
            'カート追加処理完了',
            [
                'product_id' => $Product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
            ]
        );

        if ($request->isXmlHttpRequest()) {
            // ajaxでのリクエストの場合は結果をjson形式で返す。

            // 初期化
            $done = null;
            $messages = [];

            if (empty($errorMessages)) {
                // エラーが発生していない場合
                $done = true;
                array_push($messages, trans('front.product.add_cart_complete'));
            } else {
                // エラーが発生している場合
                $done = false;
                $messages = $errorMessages;
            }

            return $this->json(['done' => $done, 'messages' => $messages]);
        } else {
            // ajax以外でのリクエストの場合はカート画面へリダイレクト
            foreach ($errorMessages as $errorMessage) {
                $this->addRequestError($errorMessage);
            }

            return $this->redirectToRoute('cart');
        }
    }
    /**
     * カートに追加.
     *
     * @Route("/products/ex/class_cart", name="class_ex_add_cart", methods={"POST"})
     */
    public function addClassCartEx(Request $request)
    {
        $product_class_id = $request->get('class_id');

        $ProductClass = $this->productClassRepository->find($product_class_id);

        if (empty($ProductClass)){
            throw new NotFoundHttpException();
        }

        $this->cartService->addProduct($ProductClass, 1);

        $Carts = $this->cartService->getCarts();

        foreach ($Carts as $Cart) {
            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
            // 復旧不可のエラーが発生した場合は追加した明細を削除.
            if ($result->hasError()) {
                $this->cartService->removeProduct($ProductClass);
                foreach ($result->getErrors() as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
            foreach ($result->getWarning() as $warning) {
                $errorMessages[] = $warning->getMessage();
            }
        }

        $this->cartService->save();

        // 初期化
        $done = null;
        $messages = [];

        if (empty($errorMessages)) {
            // エラーが発生していない場合
            $done = true;
            array_push($messages, trans('front.product.add_cart_complete'));
        } else {
            // エラーが発生している場合
            $done = false;
            $messages = $errorMessages;
        }

        return $this->json(['done' => $done, 'messages' => $messages]);
    }

    protected function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            // if ($this->BaseInfo->isOptionNostockHidden()) {
            //     if (!$Product->getStockFind()) {
            //         return false;
            //     }
            // }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }

    protected function setProductClass($product_class_id, $cartItems){
        $ProductClass = $this->productClassRepository->find($product_class_id);
        $ProductClassCategory1 = $ProductClass->getClassCategory1();
        $ProductClassCategory2 = $ProductClass->getClassCategory2();
        if (!empty($cartItems)) {

            $tmpAry = [];
            foreach ($cartItems as $item) {
                //$memberid = $item->getMember()->getId();
                $memberid = $item->getProductClass()->getMember()->getId();
                $tmpAry[$memberid][$item->getProductClass()->getId()] = $item->getQuantity();
            }
        }
        $qb = $this->productClassRepository->createQueryBuilder('pc')
            ->where('pc.Product = :Product')->setParameter('Product', $ProductClass->getProduct());

        if ($ProductClassCategory1)
            $qb->andWhere('pc.ClassCategory1 = :ClassCategory1')->setParameter('ClassCategory1', $ProductClassCategory1);

        if ($ProductClassCategory2)
            $qb->andWhere('pc.ClassCategory2 = :ClassCategory2')->setParameter('ClassCategory2', $ProductClassCategory2);

        $qb->andWhere('pc.Member is not Null');
        $qb->andWhere('pc.visible = true');

        $ProductClasses = $qb->getQuery()->getResult();
        $tmpPcAry = [];
        $max=0;
        $max_flag = 0;

        foreach ($ProductClasses as $pc){

            if (!$tmpAry[$pc->getMember()->getId()][$pc->getId()]) continue;

            if ($pc->getStock()<1) continue;

            $stock = $pc->getStock();

            if ($tmpAry[$pc->getMember()->getId()][$pc->getId()]) {
                $stock = $stock - $tmpAry[$pc->getMember()->getId()][$pc->getId()];
                if ($stock < 1) continue;
            }
            if ($max<$stock){
                $max_flag=1;
                $max = $stock;
                $max_class = $pc;
            }
        }

        if ($max_flag==1){
            $max_class->setStock($max);
            return $max_class;
        }

        foreach ($ProductClasses as $pc){
            if (!$tmpAry[$pc->getMember()->getId()]) continue;

            if ($pc->getStock()<1) continue;

            $stock = $pc->getStock();

            if ($tmpAry[$pc->getMember()->getId()][$pc->getId()]) {
                $stock = $stock - $tmpAry[$pc->getMember()->getId()][$pc->getId()];
                if ($stock < 1) continue;
            }
            if ($max<$stock){
                $max_flag=1;
                $max = $stock;
                $max_class = $pc;
            }
        }

        if ($max_flag==1){
            $max_class->setStock($max);
            return $max_class;
        }

        foreach ($ProductClasses as $pc){
            if ($tmpAry[$pc->getMember()->getId()]) continue;
            if ($pc->getStock()<1) continue;
            $tmpPcAry[$pc->getMember()->getId()][$pc->getId()] = $pc->getStock();
            if ($max<$tmpPcAry[$pc->getMember()->getId()][$pc->getId()]){
                $max_flag=1;
                $max = $tmpPcAry[$pc->getMember()->getId()][$pc->getId()];
                $max_class = $pc;
            }
        }

        if ($max_flag==1){
            $max_class->setStock($max);
            return $max_class;
        }

        return [];

    }

    public function addProduct($ProductClass, $quantity = 1, $Member)
    {

        if (!$ProductClass instanceof ProductClass) {

            if (is_null($ProductClass)) {
                return false;
            }
        }
        $ClassCategory1 = $ProductClass->getClassCategory1();
        if ($ClassCategory1 && !$ClassCategory1->isVisible()) {
            return false;
        }
        $ClassCategory2 = $ProductClass->getClassCategory2();
        if ($ClassCategory2 && !$ClassCategory2->isVisible()) {
            return false;
        }

        $newItem = new CartItem();
        $newItem->setQuantity($quantity);
        $newItem->setPrice($ProductClass->getPrice02IncTax());
        $newItem->setProductClass($ProductClass);
        $newItem->setMember($Member);


        return $newItem;
    }
}
