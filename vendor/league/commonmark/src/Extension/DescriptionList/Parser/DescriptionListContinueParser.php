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

namespace AIMuseVendor\League\CommonMark\Extension\DescriptionList\Parser;

use AIMuseVendor\League\CommonMark\Extension\DescriptionList\Node\Description;
use AIMuseVendor\League\CommonMark\Extension\DescriptionList\Node\DescriptionList;
use AIMuseVendor\League\CommonMark\Extension\DescriptionList\Node\DescriptionTerm;
use AIMuseVendor\League\CommonMark\Node\Block\AbstractBlock;
use AIMuseVendor\League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use AIMuseVendor\League\CommonMark\Parser\Block\BlockContinue;
use AIMuseVendor\League\CommonMark\Parser\Block\BlockContinueParserInterface;
use AIMuseVendor\League\CommonMark\Parser\Cursor;

final class DescriptionListContinueParser extends AbstractBlockContinueParser
{
    private DescriptionList $block;

    public function __construct()
    {
        $this->block = new DescriptionList();
    }

    public function getBlock(): DescriptionList
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return BlockContinue::at($cursor);
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return $childBlock instanceof DescriptionTerm || $childBlock instanceof Description;
    }
}
