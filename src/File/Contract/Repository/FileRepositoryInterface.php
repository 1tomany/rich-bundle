<?php

namespace App\File\Contract\Repository;

use App\Entity\File;

interface FileRepositoryInterface
{
    public function findOneById(?int $fileId): ?File;
}
