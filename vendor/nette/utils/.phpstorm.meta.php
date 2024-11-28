<?php

declare(strict_types=1);

namespace PHPSTORM_META;

override(\AIMuseVendor\Nette\Utils\Arrays::get(0), elementType(0));
override(\AIMuseVendor\Nette\Utils\Arrays::getRef(0), elementType(0));
override(\AIMuseVendor\Nette\Utils\Arrays::grep(0), type(0));
override(\AIMuseVendor\Nette\Utils\Arrays::toObject(0), type(1));

expectedArguments(\AIMuseVendor\Nette\Utils\Arrays::grep(), 2, PREG_GREP_INVERT);
expectedArguments(\AIMuseVendor\Nette\Utils\Image::resize(), 2, \AIMuseVendor\Nette\Utils\Image::SHRINK_ONLY, \AIMuseVendor\Nette\Utils\Image::STRETCH, \AIMuseVendor\Nette\Utils\Image::FIT, \AIMuseVendor\Nette\Utils\Image::FILL, \AIMuseVendor\Nette\Utils\Image::EXACT);
expectedArguments(\AIMuseVendor\Nette\Utils\Image::calculateSize(), 4, \AIMuseVendor\Nette\Utils\Image::SHRINK_ONLY, \AIMuseVendor\Nette\Utils\Image::STRETCH, \AIMuseVendor\Nette\Utils\Image::FIT, \AIMuseVendor\Nette\Utils\Image::FILL, \AIMuseVendor\Nette\Utils\Image::EXACT);
expectedArguments(\AIMuseVendor\Nette\Utils\Json::encode(), 1, \AIMuseVendor\Nette\Utils\Json::PRETTY);
expectedArguments(\AIMuseVendor\Nette\Utils\Json::decode(), 1, \AIMuseVendor\Nette\Utils\Json::FORCE_ARRAY);
expectedArguments(\AIMuseVendor\Nette\Utils\Strings::split(), 2, \PREG_SPLIT_NO_EMPTY | \PREG_OFFSET_CAPTURE);
expectedArguments(\AIMuseVendor\Nette\Utils\Strings::match(), 2, \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL);
expectedArguments(\AIMuseVendor\Nette\Utils\Strings::matchAll(), 2, \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL | \PREG_PATTERN_ORDER);
