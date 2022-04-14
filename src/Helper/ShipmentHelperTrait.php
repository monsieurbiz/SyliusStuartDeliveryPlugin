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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Helper;

use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface as CoreShipmentInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

trait ShipmentHelperTrait
{
    public function getShippingAddress(ShipmentInterface $subject): ?AddressInterface
    {
        /** @var CoreShipmentInterface $subject */
        $order = $subject->getOrder();
        if (null === $order || null === ($shippingAddress = $order->getShippingAddress())) {
            return null;
        }

        return null !== $shippingAddress->getPostcode() ? $shippingAddress : null;
    }

    public function getOrder(ShipmentInterface $subject): ?OrderInterface
    {
        /** @var CoreShipmentInterface $subject */
        $order = $subject->getOrder();
        if (null === $order) {
            return null;
        }

        return $order;
    }
}
