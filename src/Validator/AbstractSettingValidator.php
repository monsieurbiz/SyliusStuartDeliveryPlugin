<?php

/*
 * This file is part of Monsieur Biz' Stuart delivery plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Validator;

use MonsieurBiz\SyliusSettingsPlugin\Settings\Settings;
use Symfony\Component\Validator\ConstraintValidator;

abstract class AbstractSettingValidator extends ConstraintValidator
{
    protected function getFieldValue(string $field, array $values, array $defaultValues, bool $isDefault): ?string
    {
        // Default scope
        if ($isDefault) {
            return $values[$field] ?? null;
        }

        // Channel scope but use default
        if ($values[$field . '___' . Settings::DEFAULT_KEY] ?? false) {
            return $defaultValues[$field] ?? null;
        }

        // Channel scope and use channel value
        return $values[$field] ?? null;
    }
}
