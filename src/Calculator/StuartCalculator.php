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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Calculator;

use MonsieurBiz\SyliusStuartDeliveryPlugin\Exception\StuartDeliveryException;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\AbstractServiceInteraction;
use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

final class StuartCalculator extends AbstractServiceInteraction implements CalculatorInterface
{
    public const TYPE = 'stuart';

    /**
     * In case of price calculating error we throw a dedicated exception.
     * But there's no reason we can not calculate price if StuartRuleChecker is chosed
     * in shipping method configuration.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function calculate(ShipmentInterface $subject, array $configuration): int
    {
        $shippingAddress = $this->getShippingAddress($subject);
        $order = $this->getOrder($subject);

        if (null === $shippingAddress || null === $order) {
            throw new StuartDeliveryException('Incomplete or missing order');
        }

        $pickupAddress = $this->getPickupAddress();
        $dropOffAddress = $this->getDropOffAddress($shippingAddress);
        $deliveryType = $this->getDeliveryTypeProvider()->getType($order);

        try {
            $pricing = $this->getClient()->getPricing(
                $pickupAddress,
                $dropOffAddress,
                $deliveryType->getTransportType(),
                $deliveryType->getPackageType()
            );
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());

            throw new StuartDeliveryException('Stuart service can not calculate pricing');
        }

        if (null === $pricing) {
            throw new StuartDeliveryException('Stuart service can not calculate pricing');
        }

        return $pricing;
    }

    public function getType(): string
    {
        return self::TYPE;
    }
}
