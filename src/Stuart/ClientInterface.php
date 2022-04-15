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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart;

use DateTime;
use Stuart\Client as StuartClient;
use Stuart\Job;

interface ClientInterface
{
    public const PACKAGE_TYPE_XS = 'xsmall';

    public const PACKAGE_TYPE_S = 'small';

    public const PACKAGE_TYPE_M = 'medium';

    public const PACKAGE_TYPE_L = 'large';

    public const PACKAGE_TYPE_XL = 'xlarge';

    public const TRANSPORT_TYPE_BIKE = 'bike';

    public const TRANSPORT_TYPE_CARGO_BIKE = 'cargobike';

    public const TRANSPORT_TYPE_CARGO_BIKE_XL = 'cargobikexl';

    public const TRANSPORT_TYPE_MOTORBIKE = 'motorbike';

    public const TRANSPORT_TYPE_MOTORBIKE_XL = 'motorbikexl';

    public const TRANSPORT_TYPE_CAR = 'car';

    public const TRANSPORT_TYPE_VAN = 'van';

    public function init(?string $apiMode = null, ?string $apiClientId = null, ?string $apiClientSecret = null): void;

    public function getStuartClient(): StuartClient;

    public function validatePickupAddress(string $address, string $postcode, string $city, ?string $phoneNumber): bool;

    public function validateDropOffAddress(string $address, string $postcode, string $city, ?string $phoneNumber): bool;

    public function validateJob(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): bool;

    public function getJobETA(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): ?int;

    public function getPricing(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): ?int;

    public function getOnlineAddress(string $address, string $postcode, string $city): string;

    public function getJob(int $jobId): ?Job;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function createJob(
        string $pickupAddress,
        string $dropOffAddress,
        string $reference,
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone = null,
        ?DateTime $pickupAt = null,
        ?string $transportType = null,
        ?string $packageType = null
    ): ?int;
}
