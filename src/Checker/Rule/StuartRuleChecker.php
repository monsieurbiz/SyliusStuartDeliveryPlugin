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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Checker\Rule;

use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\AbstractServiceInteraction;
use Sylius\Component\Shipping\Checker\Rule\RuleCheckerInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingSubjectInterface;

final class StuartRuleChecker extends AbstractServiceInteraction implements RuleCheckerInterface
{
    public const TYPE = 'stuart';

    public function isEligible(ShippingSubjectInterface $subject, array $configuration): bool
    {
        /** @var ShipmentInterface $subject */
        $shippingAddress = $this->getShippingAddress($subject);
        if (null === $shippingAddress) {
            return false;
        }

        $pickupAddress = $this->getPickupAddress();
        $dropOffAddress = $this->getDropOffAddress($shippingAddress);

        try {
            return $this->getClient()->validateJob(
                $pickupAddress,
                $dropOffAddress,
                $configuration['transportType'] ?? null,
                $configuration['packageType'] ?? null
            );
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());

            return false;
        }
    }
}
