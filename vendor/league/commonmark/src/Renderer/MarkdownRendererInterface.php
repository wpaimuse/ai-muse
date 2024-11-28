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

namespace AIMuseVendor\League\CommonMark\Renderer;

use AIMuseVendor\League\CommonMark\Node\Block\Document;
use AIMuseVendor\League\CommonMark\Output\RenderedContentInterface;

/**
 * Renders a parsed Document AST
 *
 * @deprecated since 2.3; use {@link DocumentRendererInterface} instead
 */
interface MarkdownRendererInterface
{
    /**
     * Render the given Document node (and all of its children)
     */
    public function renderDocument(Document $document): RenderedContentInterface;
}
