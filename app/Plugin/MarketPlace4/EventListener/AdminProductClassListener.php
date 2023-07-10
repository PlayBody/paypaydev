<?php
namespace Plugin\MarketPlace4\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Controller\Admin\Product\ProductClassController;
use Eccube\Controller\ShoppingController;
use Eccube\Entity\CartItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;

use Eccube\Service\CartService;
use Eccube\Repository\CartRepository;
use Eccube\Repository\CartItemRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Eccube\Repository\MemberRepository;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

use Eccube\Repository\ProductRepository;

class AdminProductClassListener implements EventSubscriberInterface
{

    /**
     * @var Router
     */
    protected $router;

    /**
    * @var EntityManagerInterface
    */
    protected $entityManager;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var MemberRepository
     */
    protected $memberRepository;

    /**
    * CartAddCompleteListener constructor.
    *
     * @param Router $router
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param MemberRepository $memberRepository
    */
    public function __construct(
        EntityManagerInterface $entityManager,
        Router $router,
        ProductRepository $productRepository,
        MemberRepository $memberRepository
        ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->productRepository = $productRepository;
        $this->memberRepository = $memberRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelAdminProductClassController',
            KernelEvents::RESPONSE  => 'onKernelAdminProductClassResponse'
        ];
    }

    public function onKernelAdminProductClassController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller)) return;

        if ($controller[0] instanceof ProductClassController){
            if ($controller[1] == 'index'){
                $product_id = $event->getRequest()->attributes->get('id');
                $return_product_list = $event->getRequest()->query->get('return_product_list');
                $url = $this->router->generate('market_place4_admin_product_class_ex_update', ['id'=>$product_id, 'return_product_list'=>$return_product_list]);

                 $event->setController(function () use($url) {
                      return new RedirectResponse($url);
                 });
            }
        }
    }

    public function onKernelAdminProductClassResponse(FilterResponseEvent $event)
    {
//        if ($event->getRequest()->attributes->get('page_info') == 'admin_product_class') {
//            $product_id = $event->getRequest()->attributes->get('id');
//            $Product = $this->productRepository->find($product_id);
//            if (empty($Product)) return;
//
//
//           // $form->handleRequest($event->getRequest());
//            var_dump($event->getRequest());die();
//            //$ProductClasses = $Product->getProductClasses();
//
//            //foreach ($ProductClasses as $pc){
//            //    if(!$pc->isVisible()) continue;
//            //}
//        }
    }
}
?>