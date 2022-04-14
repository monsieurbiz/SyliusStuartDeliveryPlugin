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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Provider;

use MonsieurBiz\SyliusStuartDeliveryPlugin\Model\DeliveryTypeInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface DeliveryTypeProviderInterface
{
    public function getType(OrderInterface $order): DeliveryTypeInterface;
}
