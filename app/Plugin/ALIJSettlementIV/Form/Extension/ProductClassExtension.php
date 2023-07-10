<?php

namespace Plugin\ALIJSettlementIV\Form\Extension;

use Eccube\Form\Type\Admin\ProductClassEditType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Plugin\ALIJSettlementIV\Repository\ConfigRepository;

class ProductClassExtension extends AbstractTypeExtension
{
    /**
     * @var
     */
    protected $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $siteConfig = $this->configRepository->get();
        if ($siteConfig->getUseCont() === false) {

            $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $form->remove('alij_cont_enable')
                    ->remove('alij_cont_times')
                    ->remove('alij_cont_amount2');
            });

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $form->remove('alij_cont_enable')
                    ->remove('alij_cont_times')
                    ->remove('alij_cont_amount2');
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductClassEditType::class;
    }
}
