<?php

require_once __DIR__ . '/vendor/autoload.php';

use OneToMany\RichBundle\Attribute\PropertyInput;
use OneToMany\RichBundle\ValueResolver\InputValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\PropertyInfo\Extractor\ConstructorExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

$constructorExtractor = new ConstructorExtractor(...[
    'extractors' => [new PhpDocExtractor()]
]);

$typeExtractor = new PropertyInfoExtractor(...[
    'typeExtractors' => [$constructorExtractor]
]);

$objectNormalizer = new ObjectNormalizer(...[
    'propertyTypeExtractor' => $typeExtractor
]);

$serializer = new Serializer([
    new BackedEnumNormalizer(),
    new DateTimeNormalizer(),
    new ArrayDenormalizer(),
    $objectNormalizer,
]);

$data = [
    'email' => 'VIC@1TOMANY.COM',
    'data' => [
        'id' => 10,
        'age' => 40,
        'name' => 'Vic',
        'height' => 74,
        'weight' => 248.8,
    ],
];

// $pi = new PropertyInput();
// $pi->data = $data['data'];

// $pi = $serializer->denormalize($data, PropertyInput::class);
// print_r($pi);

$validator = Validation::createValidatorBuilder()
    ->enableAttributeMapping()
    ->getValidator();

// $violations = $validator->validate($pi);

$request = new Request(
    query: [
        'priceId' => '100',
        'email' => 'NEIL@1TOMANY.COM',
    ],
    request: [ ],
    attributes: [
        '_route_params' => [
            'userId' => 15,
        ],
    ],
    cookies: [ ],
    files: [ ],
    server: [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/api/users/10',
        'QUERY_STRING' => 'priceId=100',
        'HTTP_CONTENT_TYPE' => 'application/json',
    ],
    content: json_encode($data),
);

$metadata = new ArgumentMetadata('input', PropertyInput::class, false, false, null, false);

$resolver = new InputValueResolver($serializer, $validator);
$results = $resolver->resolve($request, $metadata);

print_r($results);
