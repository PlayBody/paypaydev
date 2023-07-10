<?php

namespace Plugin\LuckyBag4\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\ClassName;
use Eccube\Form\Type\Admin\ProductClassEditType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use Plugin\LuckyBag4\Entity\ProductLucky;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class ProductLuckyMatrixType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product_luckies', CollectionType::class, [
                'entry_type' => ProductLuckyType::class,
                'allow_add' => true,
                'error_bubbling' => false,
            ])
            ->add('save', SubmitType::class);

        if ($options['product_luckies_exist']) {
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $ProductLukies = $form['product_luckies']->getData();
                $hasVisible = false;
                foreach ($ProductLukies as $pl) {
                    if ($pl->isVisible()) {
                        $hasVisible = true;
                        break;
                    }
                }

                if (!$hasVisible) {
                    $form['product_luckies']->addError(new FormError(trans('admin.product.unselected_class')));
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'product_luckies_exist' => false,
        ]);

    }
}
