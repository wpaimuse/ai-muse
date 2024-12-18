<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace AIMuseVendor\League\CommonMark\Extension\FrontMatter\Exception;

use AIMuseVendor\League\CommonMark\Exception\CommonMarkException;

class InvalidFrontMatterException extends \RuntimeException implements CommonMarkException
{
    public static function wrap(\Throwable $t): self
    {
        return new InvalidFrontMatterException('Failed to parse front matter: ' . $t->getMessage(), 0, $t);
    }
}
