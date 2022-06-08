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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Message;

use Sylius\Component\Order\Model\OrderInterface;

final class ExportableOrderMessage implements ExportableEntityInterface
{
    private int $orderId;

    public function __construct(OrderInterface $order)
    {
        $this->orderId = $order->getId();
    }

    public function getId(): int
    {
        return $this->orderId;
    }
}
