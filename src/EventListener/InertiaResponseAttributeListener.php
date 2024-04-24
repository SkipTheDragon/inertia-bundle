<?php

namespace Rompetomp\InertiaBundle\EventListener;

use Rompetomp\InertiaBundle\Architecture\InertiaInterface;
use Rompetomp\InertiaBundle\Architecture\InertiaResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Generates an Inertia response from #[InertiaResponse()] attributes.
 *
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
class InertiaResponseAttributeListener implements EventSubscriberInterface
{
    /**
     * @param InertiaInterface $inertia
     */
    public function __construct(
        protected InertiaInterface $inertia,
    )
    {
    }

    /**
     * @param ViewEvent $event
     * @return void
     */
    public function onKernelView(ViewEvent $event): void
    {
        $parameters = $event->getControllerResult();

        if (!\is_array($parameters ?? [])) {
            return;
        }

        $attribute = $event->getRequest()->attributes->get('_template');

        if (!$attribute instanceof InertiaResponse && !$attribute = $event->controllerArgumentsEvent?->getAttributes()[InertiaResponse::class][0] ?? null) {
            return;
        }

        $parameters ??= $this->resolveParameters($event->controllerArgumentsEvent, $attribute->vars);

        $event->setResponse($this->inertia->render($attribute->component, $parameters, $attribute->viewData, $attribute->context, $attribute->url));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', -128],
        ];
    }

    /**
     * Combines the controller arguments with the attributes vars.
     *
     * @param ControllerArgumentsEvent $event
     * @param array|null $vars
     * @return array
     */
    private function resolveParameters(ControllerArgumentsEvent $event, ?array $vars): array
    {
        if ([] === $vars) {
            return [];
        }

        $parameters = $event->getNamedArguments();

        if (null !== $vars) {
            $parameters = array_intersect_key($parameters, array_flip($vars));
        }

        return $parameters;
    }
}
