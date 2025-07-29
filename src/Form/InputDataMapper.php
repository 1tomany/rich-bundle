<?php

namespace OneToMany\RichBundle\Form;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\Input\InputParser;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template C of CommandInterface
 */
readonly class InputDataMapper implements DataMapperInterface
{
    public function __construct(private InputParserInterface $inputParser)
    {
    }

    /**
     * @param ?InputInterface<C> $viewData
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
    }

    /**
     * @param InputInterface<C> $viewData
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        // $class = null;

        $request = new Request(server: [
            'CONTENT_TYPE' => 'multipart/form-data',
        ]);

        foreach ($forms as $field => $form) {
            $class = $form->getRoot()->getConfig()->getOption('data_class');

            // $request->request->set($field, $form->getData());
        }
    }
}
