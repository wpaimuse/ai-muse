<?php

declare(strict_types=1);

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AIMuseVendor\League\CommonMark\Extension\Mention\Generator;

use AIMuseVendor\League\CommonMark\Extension\Mention\Mention;
use AIMuseVendor\League\CommonMark\Node\Inline\AbstractInline;

interface MentionGeneratorInterface
{
    public function generateMention(Mention $mention): ?AbstractInline;
}
