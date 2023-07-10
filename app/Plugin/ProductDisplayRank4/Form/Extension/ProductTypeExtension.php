<?php

namespace Plugin\ProductDisplayRank4\Form\Extension;

use Customize\Constant\TagGroup;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Category;
use Eccube\Entity\Tag;
use Eccube\Form\Type\Admin\ProductType;
use Eccube\Form\Type\SearchProductBlockType;
use Eccube\Form\Type\SearchProductType;
use Eccube\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class ProductTypeExtension
 * @FormExtension
 */
class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $builder->add('display_rank', IntegerType::class, [
           'label' => '表示順',
           'required' => false,
           'constraints' => [
               new Regex([
                   'pattern' => "/^\d+$/u",
                   'message' => 'form_error.numeric_only',
               ]),
               new Length(['max' => 11]),
           ],
           'eccube_form_options' => [
               'auto_render' => true,
           ],
       ]);

       $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event){
           $Product = $event->getData();
           if ($Product) {
               if (is_null($Product->getDisplayRank())) {
                   $Product->setDisplayRank(0);
               }
           }
       });
    }




    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getExtendedType()
    {
        return ProductType::class;
    }
}