<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AIMuseVendor\Symfony\Component\Validator\Constraints;

use Symfony\Component\Intl\Currencies;
use AIMuseVendor\Symfony\Component\Validator\Constraint;
use AIMuseVendor\Symfony\Component\Validator\ConstraintValidator;
use AIMuseVendor\Symfony\Component\Validator\Exception\UnexpectedTypeException;
use AIMuseVendor\Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates whether a value is a valid currency.
 *
 * @author Miha Vrhovnik <miha.vrhovnik@pagein.si>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CurrencyValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Currency) {
            throw new UnexpectedTypeException($constraint, Currency::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if (!Currencies::exists($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Currency::NO_SUCH_CURRENCY_ERROR)
                ->addViolation();
        }
    }
}
