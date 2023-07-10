<?php

namespace Plugin\MarketPlace4\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormBuilderInterface;

use Eccube\Entity\Member;
use Eccube\Form\Type\Admin\MemberType;

class MemberExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $market_place4_email = '';

        $Member = $builder->getData();
        if ($Member->getId()) {
            $market_place4_email = $Member->getMarketPlace4Email();
            $market_place_matome = $Member->isMarketPlaceMatome();
        }

        $builder
            ->add('market_place4_email', TextType::class, [
                'attr' => [
                    'required' => true,
                    'value' => $market_place4_email,
                ]
            ])
            ->add('market_place_matome', CheckboxType::class, [
                'label' => 'おまとめ対象',
                'attr' => [
                    'required' => true,
                    'value' => $market_place_matome,
                ]
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Member $Member */
            $Member = $event->getData();
            if ($Member->getMarketPlace4Email() == null) {
                $Member->setMarketPlace4Email('');
            }
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return MemberType::class;
    }
}
