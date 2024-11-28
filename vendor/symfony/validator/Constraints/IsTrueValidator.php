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

use AIMuseVendor\Symfony\Component\Validator\Constraint;
use AIMuseVendor\Symfony\Component\Validator\ConstraintValidator;
use AIMuseVendor\Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IsTrueValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsTrue) {
            throw new UnexpectedTypeException($constraint, IsTrue::class);
        }

        if (null === $value) {
            return;
        }

        if (true !== $value && 1 !== $value && '1' !== $value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(IsTrue::NOT_TRUE_ERROR)
                ->addViolation();
        }
    }
}
