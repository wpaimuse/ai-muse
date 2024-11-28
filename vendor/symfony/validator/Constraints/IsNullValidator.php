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
class IsNullValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsNull) {
            throw new UnexpectedTypeException($constraint, IsNull::class);
        }

        if (null !== $value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(IsNull::NOT_NULL_ERROR)
                ->addViolation();
        }
    }
}
