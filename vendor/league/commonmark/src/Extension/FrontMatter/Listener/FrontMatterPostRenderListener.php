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

namespace AIMuseVendor\League\CommonMark\Extension\FrontMatter\Listener;

use AIMuseVendor\League\CommonMark\Event\DocumentRenderedEvent;
use AIMuseVendor\League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;

final class FrontMatterPostRenderListener
{
    public function __invoke(DocumentRenderedEvent $event): void
    {
        if ($event->getOutput()->getDocument()->data->get('front_matter', null) === null) {
            return;
        }

        $frontMatter = $event->getOutput()->getDocument()->data->get('front_matter');

        $event->replaceOutput(new RenderedContentWithFrontMatter(
            $event->getOutput()->getDocument(),
            $event->getOutput()->getContent(),
            $frontMatter
        ));
    }
}
