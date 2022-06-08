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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Transfer;

use MonsieurBiz\SyliusStuartDeliveryPlugin\Message\ExportableOrderMessage;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class JobTransfer
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function sendFromOrder(OrderInterface $order): void
    {
        if (null !== $order->getId()) {
            $this->bus->dispatch(new ExportableOrderMessage($order));
        }
    }
}
