<?php

namespace App\Controller\Reusing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['constraints' => [
                new NotBlank(),
                new Length(min: 5),
            ]])
            ->add('dueDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [new GreaterThanOrEqual(
                    new \DateTime('today'),
                    message: 'Due Date can not be in the past'
                )],
            ])
            ->add('assignee', UsernameType::class)
            ->add('stackholders', CollectionType::class, [
                'entry_type' => UsernameType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('save', SubmitType::class);
    }
}
