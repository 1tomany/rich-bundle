<?php

namespace OneToMany\RichBundle\Form;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\Exception\RuntimeException;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use function is_string;
use function is_subclass_of;

/**
 * @template C of CommandInterface
 */
readonly class InputDataMapper implements DataMapperInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private InputParserInterface $inputParser,
    ) {
    }

    /**
     * @param ?InputInterface<C> $viewData
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
    }

    /**
     * @param-out InputInterface<C> $viewData
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$request = $this->requestStack->getMainRequest()) {
            throw new RuntimeException('Mapping the form failed because the data mapper requires an HTTP request.');
        }

        $formData = [];

        foreach ($forms as $form) {
            $formData[$form->getName()] = $form->getData();
        }

        if (!isset($form) || (null === $type = $this->getDataClass($form))) {
            throw new RuntimeException('Mapping the form failed because the "data_class" option was not set.');
        }

        $viewData = $this->inputParser->parse($request, $type, $formData);
    }

    /**
     * @return ?class-string<InputInterface<C>>
     */
    private function getDataClass(FormInterface $form): ?string
    {
        do {
            $dataClass = $form->getConfig()->getOption('data_class');

            if ($dataClass) {
                break;
            }
        } while ($form = $form->getParent());

        if (!is_string($dataClass)) {
            return null;
        }

        return is_subclass_of($dataClass, InputInterface::class, true) ? $dataClass : null;
    }
}
