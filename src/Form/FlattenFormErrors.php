<?php

namespace App\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class FlattenFormErrors implements \JsonSerializable
{
    public readonly string $message;
    public readonly array $validations;

    public function __construct(FormInterface $form, string $name = null)
    {
        list('errors' => $this->validations, 'message' => $this->message) = $this->extract(
            $form,
            $name ?? $form->getName()
        );
    }

    /** @return array{errors:array<string,string[]>,message:string} */
    private function extract(FormInterface $form, string $prefix = ''): array
    {
        $errors = array_map(
            fn ($error) => $error->getMessage(),
            array_filter(
                iterator_to_array($form->getErrors(deep: false)),
                fn ($error) => $error instanceof FormError,
            ),
        );

        $message = 0 === strlen($prefix) ? implode(PHP_EOL, $errors) : implode(
            PHP_EOL . $form->getConfig()->getOption('label') ?: $form->getName() . ': ',
            $errors
        );

        if (0 !== count($errors)) {
            $errors = [$prefix ?: '.' => $errors];
        }

        foreach ($form->all() as $childForm) {
            if (! $childForm instanceof FormInterface) {
                continue;
            }

            list('message' => $childMessages, 'errors' => $childErrors) = $this->extract(
                $childForm,
                ($prefix ? $prefix . '.' : '') . $childForm->getName()
            );

            if (0 === count($childErrors)) {
                continue;
            }

            $message .= PHP_EOL . $childMessages;
            $errors += $childErrors;
        }

        return compact('message', 'errors');
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return ['message' => $this->message, 'validations' => $this->validations];
    }
}
