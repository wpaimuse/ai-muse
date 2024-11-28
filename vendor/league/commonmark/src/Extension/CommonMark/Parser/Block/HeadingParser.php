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

namespace AIMuseVendor\League\CommonMark\Extension\CommonMark\Parser\Block;

use AIMuseVendor\League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use AIMuseVendor\League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use AIMuseVendor\League\CommonMark\Parser\Block\BlockContinue;
use AIMuseVendor\League\CommonMark\Parser\Block\BlockContinueParserInterface;
use AIMuseVendor\League\CommonMark\Parser\Block\BlockContinueParserWithInlinesInterface;
use AIMuseVendor\League\CommonMark\Parser\Cursor;
use AIMuseVendor\League\CommonMark\Parser\InlineParserEngineInterface;

final class HeadingParser extends AbstractBlockContinueParser implements BlockContinueParserWithInlinesInterface
{
    /** @psalm-readonly */
    private Heading $block;

    private string $content;

    public function __construct(int $level, string $content)
    {
        $this->block   = new Heading($level);
        $this->content = $content;
    }

    public function getBlock(): Heading
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return BlockContinue::none();
    }

    public function parseInlines(InlineParserEngineInterface $inlineParser): void
    {
        $inlineParser->parse($this->content, $this->block);
    }
}
