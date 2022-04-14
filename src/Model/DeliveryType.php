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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Model;

final class DeliveryType implements DeliveryTypeInterface
{
    private ?string $packageType = null;

    private ?string $transportType = null;

    public function __construct(?string $packageType, ?string $transportType)
    {
        $this->packageType = $packageType;
        $this->transportType = $transportType;
    }

    public function getPackageType(): ?string
    {
        return $this->packageType;
    }

    public function getTransportType(): ?string
    {
        return $this->transportType;
    }
}
