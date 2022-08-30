<?php

namespace App\Controller\DataClass;

use App\Controller\Reusing\UsernameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PropertyPathType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('int_id', IntegerType::class, [
                'property_path' => 'id',
                'constraints' => [new NotBlank()],
            ])
            ->add('str_name', TextType::class, [
                'property_path' => 'name',
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 5),
                ],
            ])
            ->add('dat_due_date', DateType::class, [
                'property_path' => 'dueDate',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [new GreaterThanOrEqual(
                    new \DateTime('today'),
                    message: 'Due Date can not be in the past'
                )],
            ])
            ->add('str_assignee', UsernameType::class, ['property_path' => 'assignee'])
            ->add('arr_stackholders', CollectionType::class, [
                'property_path' => 'stackholders',
                'entry_type' => UsernameType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', TaskDTO::class);
    }
}
