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

namespace AIMuseVendor\League\CommonMark\Environment;

use AIMuseVendor\League\CommonMark\Delimiter\Processor\DelimiterProcessorCollection;
use AIMuseVendor\League\CommonMark\Extension\ExtensionInterface;
use AIMuseVendor\League\CommonMark\Node\Node;
use AIMuseVendor\League\CommonMark\Normalizer\TextNormalizerInterface;
use AIMuseVendor\League\CommonMark\Parser\Block\BlockStartParserInterface;
use AIMuseVendor\League\CommonMark\Parser\Inline\InlineParserInterface;
use AIMuseVendor\League\CommonMark\Renderer\NodeRendererInterface;
use AIMuseVendor\League\Config\ConfigurationProviderInterface;
use AIMuseVendor\Psr\EventDispatcher\EventDispatcherInterface;

interface EnvironmentInterface extends ConfigurationProviderInterface, EventDispatcherInterface
{
    /**
     * Get all registered extensions
     *
     * @return ExtensionInterface[]
     */
    public function getExtensions(): iterable;

    /**
     * @return iterable<BlockStartParserInterface>
     */
    public function getBlockStartParsers(): iterable;

    /**
     * @return iterable<InlineParserInterface>
     */
    public function getInlineParsers(): iterable;

    public function getDelimiterProcessors(): DelimiterProcessorCollection;

    /**
     * @psalm-param class-string<Node> $nodeClass
     *
     * @return iterable<NodeRendererInterface>
     */
    public function getRenderersForClass(string $nodeClass): iterable;

    public function getSlugNormalizer(): TextNormalizerInterface;
}
