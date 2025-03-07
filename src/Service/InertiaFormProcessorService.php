<?php

namespace Rompetomp\InertiaBundle\Service;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * The complete behavior is explained here: https://inertiajs.com/validation
 * Basically, we serialize the form errors to an array in a format that Inertia understands natively.
 */
class InertiaFormProcessorService
{
    /**
     * Entry point for processing forms.
     *
     * @param array<FormInterface> $forms
     * @return array
     */
    public function processForms(array $forms): array
    {
        $errors = [];

        foreach ($forms as $form) {
            /**
             * Process each form and get the errors.
             * Each form has a name and a list of errors.
             */
            $errors[$form->getName()] = $this->getFormErrors($form);
        }

        return $errors;
    }

    /**
     * Gets the form errors for each field.
     *
     * @param FormInterface $form
     * @return array
     */
    protected function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form as $childForm) {
            $errors = $this->getChildErrors($childForm);
        }

        return $errors;
    }

    /**
     * Gets all validation errors from a field.
     *
     * @param FormInterface $form
     * @return array
     */
    protected function getChildErrors(FormInterface $form): array
    {
        $errors = [];

        /**
         * @var FormError $error
         */
        foreach ($form->getErrors(true, true) as $error) {
            $errors[$form->getName()] = $error->getMessage();
        }

        return $errors;
    }
}
