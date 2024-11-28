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

namespace AIMuseVendor\League\CommonMark\Extension\FrontMatter\Output;

use AIMuseVendor\League\CommonMark\Extension\FrontMatter\FrontMatterProviderInterface;
use AIMuseVendor\League\CommonMark\Node\Block\Document;
use AIMuseVendor\League\CommonMark\Output\RenderedContent;

/**
 * @psalm-immutable
 */
final class RenderedContentWithFrontMatter extends RenderedContent implements FrontMatterProviderInterface
{
    /**
     * @var mixed
     *
     * @psalm-readonly
     */
    private $frontMatter;

    /**
     * @param Document   $document    The parsed Document object
     * @param string     $content     The final HTML
     * @param mixed|null $frontMatter Any parsed front matter
     */
    public function __construct(Document $document, string $content, $frontMatter)
    {
        parent::__construct($document, $content);

        $this->frontMatter = $frontMatter;
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontMatter()
    {
        return $this->frontMatter;
    }
}
