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
use Symfony\Component\Validator\Constraint;

final class PackageOrTransportTypeValidator extends AbstractSettingValidator
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        // Retrieve context information
        $propertyPath = $this->context->getPropertyPath();
        $formData = $this->context->getRoot()->getData();

        // Set default information
        $defaultKey = Settings::DEFAULT_KEY . '-' . Settings::DEFAULT_KEY;
        $defaultValues = $formData[$defaultKey] ?? [];

        foreach ($formData as $scope => $values) {
            // Ignore scope which is not the current one
            if (sprintf('children[%s].data', $scope) !== $propertyPath) {
                continue;
            }

            $isDefault = $scope === Settings::DEFAULT_KEY . '-' . Settings::DEFAULT_KEY;

            $transportType = $this->getFieldValue('transport_type', $values, $defaultValues, $isDefault);
            $packageType = $this->getFieldValue('package_type', $values, $defaultValues, $isDefault);
            if (null === $transportType && null === $packageType) {
                $this->context->buildViolation($constraint->message)->atPath('[transport_type]')->addViolation(); // @phpstan-ignore-line
                $this->context->buildViolation($constraint->message)->atPath('[package_type]')->addViolation(); // @phpstan-ignore-line
            }
        }
    }
}
