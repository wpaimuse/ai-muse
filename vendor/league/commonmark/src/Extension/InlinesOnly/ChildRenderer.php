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

namespace AIMuseVendor\League\CommonMark\Extension\InlinesOnly;

use AIMuseVendor\League\CommonMark\Node\Block\Document;
use AIMuseVendor\League\CommonMark\Node\Node;
use AIMuseVendor\League\CommonMark\Renderer\ChildNodeRendererInterface;
use AIMuseVendor\League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Simply renders child elements as-is, adding newlines as needed.
 */
final class ChildRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        $out = $childRenderer->renderNodes($node->children());
        if (! $node instanceof Document) {
            $out .= $childRenderer->getBlockSeparator();
        }

        return $out;
    }
}
