<?php

namespace App\Controller\DataClass;

use App\Controller\Reusing\UpdateTaskType as AppUpdateTaskType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataClassTaskType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', TaskDTO::class);
    }

    public function getParent(): string
    {
        return AppUpdateTaskType::class;
    }
}
