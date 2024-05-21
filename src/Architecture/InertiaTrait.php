<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * The InertiaTrait provides the Inertia service to the class that uses it.
 * This is just QOL, so you don't have to write the same code over and over again.
 *
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
