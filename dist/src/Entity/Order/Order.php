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

namespace App\Entity\Order;

use Doctrine\ORM\Mapping as ORM;
use MonsieurBiz\SyliusShippingSlotPlugin\Entity\OrderInterface as MonsieurBizOrderInterface;
use MonsieurBiz\SyliusShippingSlotPlugin\Entity\OrderTrait;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Entity\StuartJobIdInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Entity\StuartJobIdTrait;
use Sylius\Component\Core\Model\Order as SyliusOrder;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order")
 */
class Order extends SyliusOrder implements OrderInterface, MonsieurBizOrderInterface, StuartJobIdInterface
{
    use OrderTrait;

    use StuartJobIdTrait;
}
