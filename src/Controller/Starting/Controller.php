<?php

namespace App\Controller\Starting;

use App\Controller\ControllerInterface;
use App\Form\FlattenFormErrors;
use function str_contains;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Twig\Environment;

#[Route(path:'/first-example', methods:['POST', 'GET'])]
class Controller implements ControllerInterface
{
    public function __invoke(
        Request $request,
        FormFactoryInterface $formFactory,
        Environment $twig,
    ): Response {
        /** @var FormInterface */
        $form = $formFactory->createBuilder()
            ->add('name', TextType::class, ['constraints' => [
                new NotBlank(),
                new Length(min: 5),
            ]])
            ->add('dueDate', DateType::class, ['widget' => 'single_text', 'constraints' => [
                new GreaterThanOrEqual(new \DateTime('today'), message: 'Due Date can not be in the past'),
            ]])
            ->add('save', SubmitType::class)
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
