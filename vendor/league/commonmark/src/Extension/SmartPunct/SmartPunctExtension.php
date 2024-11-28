<?php

declare(strict_types=1);

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (http://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AIMuseVendor\League\CommonMark\Extension\SmartPunct;

use AIMuseVendor\League\CommonMark\Environment\EnvironmentBuilderInterface;
use AIMuseVendor\League\CommonMark\Event\DocumentParsedEvent;
use AIMuseVendor\League\CommonMark\Extension\ConfigurableExtensionInterface;
use AIMuseVendor\League\CommonMark\Node\Block\Document;
use AIMuseVendor\League\CommonMark\Node\Block\Paragraph;
use AIMuseVendor\League\CommonMark\Node\Inline\Text;
use AIMuseVendor\League\CommonMark\Renderer\Block as CoreBlockRenderer;
use AIMuseVendor\League\CommonMark\Renderer\Inline as CoreInlineRenderer;
use AIMuseVendor\League\Config\ConfigurationBuilderInterface;
use AIMuseVendor\Nette\Schema\Expect;

final class SmartPunctExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('smartpunct', Expect::structure([
            'double_quote_opener' => Expect::string(Quote::DOUBLE_QUOTE_OPENER),
            'double_quote_closer' => Expect::string(Quote::DOUBLE_QUOTE_CLOSER),
            'single_quote_opener' => Expect::string(Quote::SINGLE_QUOTE_OPENER),
            'single_quote_closer' => Expect::string(Quote::SINGLE_QUOTE_CLOSER),
        ]));
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addInlineParser(new QuoteParser(), 10)
            ->addInlineParser(new DashParser(), 0)
            ->addInlineParser(new EllipsesParser(), 0)

            ->addDelimiterProcessor(QuoteProcessor::createDoubleQuoteProcessor(
                $environment->getConfiguration()->get('smartpunct/double_quote_opener'),
                $environment->getConfiguration()->get('smartpunct/double_quote_closer')
            ))
            ->addDelimiterProcessor(QuoteProcessor::createSingleQuoteProcessor(
                $environment->getConfiguration()->get('smartpunct/single_quote_opener'),
                $environment->getConfiguration()->get('smartpunct/single_quote_closer')
            ))

            ->addEventListener(DocumentParsedEvent::class, new ReplaceUnpairedQuotesListener())

            ->addRenderer(Document::class, new CoreBlockRenderer\DocumentRenderer(), 0)
            ->addRenderer(Paragraph::class, new CoreBlockRenderer\ParagraphRenderer(), 0)
            ->addRenderer(Text::class, new CoreInlineRenderer\TextRenderer(), 0);
    }
}
