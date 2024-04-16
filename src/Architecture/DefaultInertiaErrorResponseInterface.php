<?php

namespace Rompetomp\InertiaBundle\Architecture;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author  Tudorache Leonard Valentin <tudorache.leonard@wyverr.com>
 */
interface DefaultInertiaErrorResponseInterface
{
    public function getResponse(): Response;
}
