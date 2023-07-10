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
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Eccube\Request\Context;

use Eccube\Repository\ProductRepository;

class AdminOrderEditListener implements EventSubscriberInterface
{

    /**
     * @var Router
     */
    protected $router;
    /**
     * @var Context
     */
    protected $context;

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
     * @param Context $context
     * @param ProductRepository $productRepository
     * @param MemberRepository $memberRepository
    */
    public function __construct(
        EntityManagerInterface $entityManager,
        Context $context,
        Router $router,
        ProductRepository $productRepository,
        MemberRepository $memberRepository
        ) {
        $this->entityManager = $entityManager;
        $this->context = $context;
        $this->router = $router;
        $this->productRepository = $productRepository;
        $this->memberRepository = $memberRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::ADMIN_ORDER_EDIT_INDEX_INITIALIZE => 'onAdminOrderEditIndexInitialize',
        ];
    }

    public function onAdminOrderEditIndexInitialize(EventArgs $eventArgs)
    {
        $TargetOrder = $eventArgs->getArgument('TargetOrder');
        $builder = $eventArgs->getArgument('builder');
        $Admin = $this->context->getCurrentUser();

        if ($Admin->getAuthority()->getId()>0){
            foreach ($TargetOrder->getOrderItems() as $OrderItem){
                if ($OrderItem->isProduct()){
                    if ($OrderItem->getProductClass()->getMember()!== $Admin){
                        $TargetOrder->removeOrderItem($OrderItem);
                    }
                }
            }
        }
        $eventArgs->setArgument('TargetOrder', $TargetOrder);
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