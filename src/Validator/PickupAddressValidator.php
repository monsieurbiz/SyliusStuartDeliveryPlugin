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

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MonsieurBiz\SyliusSettingsPlugin\Settings\Settings;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\ClientInterface;
use Symfony\Component\Validator\Constraint;

final class PickupAddressValidator extends AbstractSettingValidator
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Add violation if pickup address is not validated by API.
     *
     * @param array $value
     * @param PickupAddress $constraint
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

            // API info
            $apiMode = $this->getFieldValue('api_mode', $values, $defaultValues, $isDefault);
            $apiClientId = $this->getFieldValue('api_client_id', $values, $defaultValues, $isDefault);
            $apiClientSecret = $this->getFieldValue('api_client_secret', $values, $defaultValues, $isDefault);

            // Address info
            $address = (string) $this->getFieldValue('address', $values, $defaultValues, $isDefault);
            $postcode = (string) $this->getFieldValue('postcode', $values, $defaultValues, $isDefault);
            $city = (string) $this->getFieldValue('city', $values, $defaultValues, $isDefault);
            $phoneNumber = $this->getFieldValue('phone_number', $values, $defaultValues, $isDefault);

            try {
                // Use form information to init the Stuart Client
                $this->client->init($apiMode, $apiClientId, $apiClientSecret);
                $valid = $this->client->validatePickupAddress($address, $postcode, $city, $phoneNumber);
            } catch (IdentityProviderException $exception) {
                $this->context->buildViolation($constraint->apiMessage)->atPath('[api_mode]')->addViolation();
                $this->context->buildViolation($constraint->apiMessage)->atPath('[api_client_id]')->addViolation();
                $this->context->buildViolation($constraint->apiMessage)->atPath('[api_client_secret]')->addViolation();

                return;
            }
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
}
