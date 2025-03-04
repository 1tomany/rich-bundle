<?php

namespace OneToMany\RichBundle\Attribute;

use Symfony\Component\Validator\Constraints as Assert;

#[RichInput]
class PropertyInput
{

    #[Assert\Positive]
    #[RichPropertyRoute]
    public int $userId = 0 {
        set(mixed $v) => is_numeric($v) ? intval($v) : 0;
    }

    #[Assert\Positive]
    #[RichPropertyQuery]
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
    #[RichProperty]
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
