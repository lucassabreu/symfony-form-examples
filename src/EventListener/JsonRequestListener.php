<?php

namespace App\EventListener;

use function is_array;
use function json_decode;
use function str_contains;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonRequestListener implements EventSubscriberInterface
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (! $event->isMainRequest() ||
            ! str_contains($request->getContentType() ?: '', 'json')) {
            return;
        }

        $json = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR|JSON_OBJECT_AS_ARRAY);
        if (! is_array($json)) {
            return;
        }

        $request->request = new ParameterBag($json);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => '__invoke'];
    }
}
