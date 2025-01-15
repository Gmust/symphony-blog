<?php

namespace App\Form;

use App\Entity\KeyValueStore;
use App\Form\DataTransformer\KeyValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KeyValueStoreType extends AbstractType
{
    private $keyValueTransformer;

    public function __construct(KeyValueTransformer $keyValueTransformer)
    {
        $this->keyValueTransformer = $keyValueTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', TextType::class, [
                'label' => 'Key',
            ])
            ->add('value', TextareaType::class, [
                'label' => 'Value',
                'attr' => [
                    'placeholder' => 'Enter comma-separated values for multiple entries'
                ],
            ]);

        $builder->get('value')->addModelTransformer($this->keyValueTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KeyValueStore::class,
        ]);
    }
}
