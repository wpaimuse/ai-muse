<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 * (c) Rezo Zero / Ambroise Maupate
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace AIMuseVendor\League\CommonMark\Extension\Footnote\Parser;

use AIMuseVendor\League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use AIMuseVendor\League\CommonMark\Parser\Inline\InlineParserInterface;
use AIMuseVendor\League\CommonMark\Parser\Inline\InlineParserMatch;
use AIMuseVendor\League\CommonMark\Parser\InlineParserContext;
use AIMuseVendor\League\CommonMark\Reference\Reference;
use AIMuseVendor\League\Config\ConfigurationAwareInterface;
use AIMuseVendor\League\Config\ConfigurationInterface;

final class FootnoteRefParser implements InlineParserInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\[\^([^\s\]]+)\]');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());

        [$label] = $inlineContext->getSubMatches();
        $inlineContext->getContainer()->appendChild(new FootnoteRef($this->createReference($label)));

        return true;
    }

    private function createReference(string $label): Reference
    {
        return new Reference(
            $label,
            '#' . $this->config->get('footnote/footnote_id_prefix') . $label,
            $label
        );
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
