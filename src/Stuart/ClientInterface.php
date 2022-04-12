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

use Stuart\Client as StuartClient;

interface ClientInterface
{
    public function init(?string $apiMode = null, ?string $apiClientId = null, ?string $apiClientSecret = null): void;

    public function getStuartClient(): StuartClient;

    public function validatePickupAddress(string $address, string $postcode, string $city, ?string $phoneNumber): bool;
}
