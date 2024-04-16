<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
trait InertiaTrait
{
    protected InertiaInterface $inertia;

    #[Required]
    public function setInertiaService(InertiaInterface $inertiaService): void
    {
        $this->inertia = $inertiaService;
    }
}
