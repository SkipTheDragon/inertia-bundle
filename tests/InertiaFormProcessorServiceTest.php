<?php

namespace Rompetomp\InertiaBundle\Tests;

use Rompetomp\InertiaBundle\Service\InertiaFormProcessorService;
use Rompetomp\InertiaBundle\Tests\Fixtures\InertiaBaseConfig;
use Rompetomp\InertiaBundle\Tests\Fixtures\TestFormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

class InertiaFormProcessorServiceTest extends InertiaBaseConfig
{
    function testShouldPassSimpleValidationForOneForm()
    {
        $inertiaFormProcessorService = new InertiaFormProcessorService();

        $factory = Forms::createFormFactoryBuilder()
            ->addExtensions([new ValidatorExtension(Validation::createValidator())])
            ->addTypeExtensions([])
            ->addTypes([])
            ->addTypeGuessers([])
            ->getFormFactory();

        $formData = [];

        $form = $factory->create(TestFormType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals([
            "test_form" => [
                "username" => "Your error message"
            ]
        ], $inertiaFormProcessorService->processForms([$form]));
    }
}
