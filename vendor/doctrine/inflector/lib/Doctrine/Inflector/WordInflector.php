<?php

declare(strict_types=1);

namespace AIMuseVendor\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
