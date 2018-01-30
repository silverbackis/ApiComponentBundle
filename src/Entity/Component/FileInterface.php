<?php

namespace Silverback\ApiComponentBundle\Entity\Component;

interface FileInterface
{
    public function getFilePath(): ?string;
    public function setFilePath(?string $filePath): void;
    public static function getImagineFilters(): array;
}
