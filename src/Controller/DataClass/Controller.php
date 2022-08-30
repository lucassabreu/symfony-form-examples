<?php

namespace App\Controller\DataClass;

use App\Controller\ControllerInterface;
use App\Form\FlattenFormErrors;
use function str_contains;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route(path:'/data-class-example', methods:['POST', 'GET'])]
class Controller implements ControllerInterface
{
    public function __invoke(
        Request $request,
        FormFactoryInterface $formFactory,
        Environment $twig,
    ): Response {
        $dto = new TaskDTO();
        $dto->setStackholders(['']);
        /** @var FormInterface */
        $form = $formFactory->createNamedBuilder('form', DataClassTaskType::class, $dto)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return new JsonResponse($form->getData());
        }

        if (array_filter($request->getAcceptableContentTypes(), fn ($type) => str_contains($type, 'json'))) {
            return $form->isSubmitted()
                ? new JsonResponse(new FlattenFormErrors($form), 400) : new JsonResponse();
        }

        return new Response($twig->render('form.html.twig', [
            'form' => $form->createView(),
        ]));
    }
}
