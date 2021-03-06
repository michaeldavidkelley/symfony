<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates whether a value is a valid timezone identifier.
 *
 * @author Javier Spagnoletti <phansys@gmail.com>
 * @author Hugo Hamon <hugohamon@neuf.fr>
 */
class TimezoneValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Timezone) {
            throw new UnexpectedTypeException($constraint, Timezone::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        // @see: https://bugs.php.net/bug.php?id=75928
        if ($constraint->countryCode) {
            $timezoneIds = \DateTimeZone::listIdentifiers($constraint->zone, $constraint->countryCode);
        } else {
            $timezoneIds = \DateTimeZone::listIdentifiers($constraint->zone);
        }

        if ($timezoneIds && \in_array($value, $timezoneIds, true)) {
            return;
        }

        if ($constraint->countryCode) {
            $code = Timezone::TIMEZONE_IDENTIFIER_IN_COUNTRY_ERROR;
        } elseif (\DateTimeZone::ALL !== $constraint->zone) {
            $code = Timezone::TIMEZONE_IDENTIFIER_IN_ZONE_ERROR;
        } else {
            $code = Timezone::TIMEZONE_IDENTIFIER_ERROR;
        }

        $this->context->buildViolation($constraint->message)
                      ->setParameter('{{ value }}', $this->formatValue($value))
                      ->setCode($code)
                      ->addViolation();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'zone';
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValue($value, $format = 0)
    {
        $value = parent::formatValue($value, $format);

        if (!$value || \DateTimeZone::PER_COUNTRY === $value) {
            return $value;
        }

        return array_search($value, (new \ReflectionClass(\DateTimeZone::class))->getConstants(), true) ?: $value;
    }
}
