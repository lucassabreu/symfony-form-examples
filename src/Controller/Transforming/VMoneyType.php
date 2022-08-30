<?php

namespace App\Controller\Transforming;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class VMoneyType extends AbstractType
{
    public function getParent(): string
    {
        return IntegerType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            transform: fn ($value) => $value instanceof Money ? $value->value : null,
            reverseTransform: fn ($value) => is_null($value) ? null : new Money($value),
        ));
    }
}
