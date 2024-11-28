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

namespace AIMuseVendor\League\CommonMark\Extension\Footnote\Event;

use AIMuseVendor\League\CommonMark\Event\DocumentParsedEvent;
use AIMuseVendor\League\CommonMark\Extension\Footnote\Node\Footnote;
use AIMuseVendor\League\CommonMark\Extension\Footnote\Node\FootnoteBackref;
use AIMuseVendor\League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use AIMuseVendor\League\CommonMark\Node\Block\Paragraph;
use AIMuseVendor\League\CommonMark\Node\Inline\Text;
use AIMuseVendor\League\CommonMark\Reference\Reference;
use AIMuseVendor\League\Config\ConfigurationAwareInterface;
use AIMuseVendor\League\Config\ConfigurationInterface;

final class AnonymousFootnotesListener implements ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();
        foreach ($document->iterator() as $node) {
            if (! $node instanceof FootnoteRef || ($text = $node->getContent()) === null) {
                continue;
            }

            // Anonymous footnote needs to create a footnote from its content
            $existingReference = $node->getReference();
            $newReference      = new Reference(
                $existingReference->getLabel(),
                '#' . $this->config->get('footnote/ref_id_prefix') . $existingReference->getLabel(),
                $existingReference->getTitle()
            );

            $paragraph = new Paragraph();
            $paragraph->appendChild(new Text($text));
            $paragraph->appendChild(new FootnoteBackref($newReference));

            $footnote = new Footnote($newReference);
            $footnote->appendChild($paragraph);

            $document->appendChild($footnote);
        }
    }

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }
}
