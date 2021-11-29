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
use Symfony\Component\Validator\ConstraintValidator;

final class PickupAddressValidator extends ConstraintValidator
{
    /**
     * Add violation if pickup address is not validated by API.
     *
     * @param array $value
     * @param PickupAddress $constraint
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate($value, Constraint $constraint): void
    {
        // Retrieve context information
        $propertyPath = $this->context->getPropertyPath();
        $formData = $this->context->getRoot()->getData();

        // Set default information
        $defaultKey = Settings::DEFAULT_KEY . '-' . Settings::DEFAULT_KEY;
        $defaultValues = $formData[$defaultKey] ?? [];

        $valid = false;
        foreach ($formData as $scope => $values) {
            // Ignore scope which is not the current one
            if (sprintf('children[%s].data', $scope) !== $propertyPath) {
                continue;
            }

            $isDefault = $scope === Settings::DEFAULT_KEY . '-' . Settings::DEFAULT_KEY;

            $address = $this->getFieldValue('address', $values, $defaultValues, $isDefault);
            // $postcode = $this->getFieldValue('postcode', $values, $defaultValues, $isDefault);
            // $city = $this->getFieldValue('city', $values, $defaultValues, $isDefault);
            // $phoneNumber = $this->getFieldValue('phone_number', $values, $defaultValues, $isDefault);

            /** @TODO call API to check address instead of this simple condition */
            $valid = !$address;
        }

        if ($valid) {
            return;
        }

        // Add the error on fields use to check the address
        $this->context->buildViolation($constraint->message)->atPath('[address]')->addViolation();
        $this->context->buildViolation($constraint->message)->atPath('[postcode]')->addViolation();
        $this->context->buildViolation($constraint->message)->atPath('[city]')->addViolation();
        $this->context->buildViolation($constraint->message)->atPath('[phone_number]')->addViolation();
    }

    private function getFieldValue(string $field, array $values, array $defaultValues, bool $isDefault): ?string
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
