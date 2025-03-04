<?php

require_once __DIR__ . '/../vendor/autoload.php';

use OneToMany\RichBundle\Attribute\PropertySource;
use OneToMany\RichBundle\Attribute\RichInput;
use OneToMany\RichBundle\Attribute\RichProperty;
use OneToMany\RichBundle\Attribute\RichPropertyIgnored;
use OneToMany\RichBundle\Attribute\RichPropertyQuery;
use OneToMany\RichBundle\Attribute\RichPropertyRoute;
use OneToMany\RichBundle\ValueResolver\InputValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyInfo\Extractor\ConstructorExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

#[RichInput]
class PropertyInput
{

    #[RichPropertyIgnored]
    public int $id = 10;

    #[Assert\Positive]
    #[RichPropertyRoute]
    public int $userId = 0 {
        set(mixed $v) => is_numeric($v) ? intval($v) : 0;
    }

    #[Assert\Positive]
    #[RichPropertyQuery]
    #[RichPropertyRoute]
    public int $priceId = 0 {
        set(mixed $v) => is_numeric($v) ? intval($v) : 0;
    }

    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    #[RichProperty]
    public string $email = '' {
        set (mixed $v) => is_string($v) ? strtolower($v) : '';
    }

    /**
     * @var array<string, int>
     */
    public array $data = [] {
        set (mixed $v) => $this->mapData($v);
    }

    /**
     * @return array<string, int>
     */
    private function mapData(mixed $data): array
    {
        $mappedData = [];

        if (!is_array($data)) {
            return $mappedData;
        }

        foreach ($data as $k => $v) {
            if (!is_string($k)) {
                continue;
            }

            if (!is_int($v)) {
                continue;
            }

            $mappedData[$k] = $v;
        }

        return $mappedData;
    }

}

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

$validator = Validation::createValidatorBuilder()
    ->enableAttributeMapping()
    ->getValidator();

$request = new Request(
    query: [
        'priceId' => '100',
        'email' => 'NEIL@1TOMANY.COM',
    ],
    request: [ ],
    attributes: [
        '_route_params' => [
            'userId' => 15,
            'priceId' => 666,
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
