<?php
namespace Plugin\MarketPlace4\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Controller\Admin\Product\ProductClassController;
use Eccube\Controller\ShoppingController;
use Eccube\Entity\CartItem;
use Eccube\Entity\MailHistory;
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

use Eccube\Repository\ProductRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\MailHistoryRepository;

use Plugin\MarketPlace4\Repository\MarketPlace4ConfigRepository;
use Plugin\MarketPlace4\Repository\OrderMemberRepository;
use Eccube\Repository\BaseInfoRepository;

class ShoppingOrderCompleteListener implements EventSubscriberInterface
{

    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var MarketPlace4ConfigRepository
     */
    protected $marketPlace4ConfigRepository;

    /**
     * @var MailHistoryRepository
     */
    protected $mailHistoryRepository;

    /**
     * @var OrderMemberRepository
     */
    protected $orderMemberRepository;

    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;
    /**
    * @var EntityManagerInterface
    */
    protected $entityManager;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
    * ShoppingOrderCompleteListener constructor.
    *
     * @param MailTemplateRepository $mailTemplateRepository
     * @param MarketPlace4ConfigRepository $marketPlace4ConfigRepository
     * @param MailHistoryRepository $mailHistoryRepository
     * @param OrderMemberRepository $orderMemberRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param EntityManagerInterface $entityManager
     * @param BaseInfo $baseInfo
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
    */
    public function __construct(
        MailTemplateRepository $mailTemplateRepository,
        MarketPlace4ConfigRepository $marketPlace4ConfigRepository,
        MailHistoryRepository $mailHistoryRepository,
        OrderMemberRepository $orderMemberRepository,
        BaseInfoRepository $baseInfoRepository,
        EntityManagerInterface $entityManager,
        \Swift_Mailer $mailer,
        \Twig_Environment $twig
        ) {
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->marketPlace4ConfigRepository = $marketPlace4ConfigRepository;
        $this->mailHistoryRepository = $mailHistoryRepository;
        $this->orderMemberRepository = $orderMemberRepository;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE => 'onFrontShoppingComplete',
        ];
    }

    public function onFrontShoppingComplete(EventArgs $eventArgs)
    {
        $Order = $eventArgs->getArgument('Order');

        $MarketPlace4Config = $this->marketPlace4ConfigRepository->find(1);

        $MailTemplate = $MarketPlace4Config->getMailTemplate();


        $OrderMembers = $this->orderMemberRepository->findBy(['Order'=>$Order]);
        $BaseInfo = $this->baseInfoRepository->find(1);
        foreach ($OrderMembers as $OrderMember){

            $Member = $OrderMember->getMember();

            if (empty($Member->getMarketPlace4Email())) continue;
            $OrderItems = [];
            $sum = 0;
            foreach ($Order->getOrderItems() as $item){
                if ($item->isProduct()==false) continue;
                if ($item->getProductClass()->getMember() == $Member){
                    $OrderItems[] = $item;
                    $sum = sum + ($item->getPriceIncTax() * $item->getQuantity());
                }
            }

            $body = $this->twig->render($MailTemplate->getFileName(), [
                'Order' => $Order,
                'Member' => $Member,
                'OrderItems' => $OrderItems,
                'OrderMember' => $OrderMember,
                'sum' => $sum,
                'total' => $sum+$OrderMember->getAmount()
            ]);


            $message = (new \Swift_Message())
                ->setSubject('['.$BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
                ->setFrom([$BaseInfo->getEmail01() => $BaseInfo->getShopName()])
                ->setTo([$Member->getMarketPlace4Email()]);
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

}
?>