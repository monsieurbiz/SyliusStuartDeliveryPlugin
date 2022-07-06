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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\EventSubscriber;

use DateInterval;
use DateTime;
use MonsieurBiz\SyliusShippingSlotPlugin\Event\RecurrenceGenerationEvent;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Calculator\StuartCalculator;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\AbstractServiceInteraction;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RecurrenceGenerationEventSubscriber extends AbstractServiceInteraction implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RecurrenceGenerationEvent::class => 'onRecurrenceGeneration',
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onRecurrenceGeneration(RecurrenceGenerationEvent $event): void
    {
        $recurrences = [];

        $shippingMethod = $event->getShippingMethod();
        if (null === $shippingMethod || StuartCalculator::TYPE !== $shippingMethod->getCalculator()) {
            return;
        }

        /** @var OrderInterface $cart */
        $cart = $this->getCart();
        $shippingAddress = $cart->getShippingAddress();
        if (null === $shippingAddress) {
            $event->setRecurrences([]);

            return;
        }

        $pickupAddress = $this->getPickupAddress();
        $dropOffAddress = $this->getDropOffAddress($shippingAddress);
        $deliveryType = $this->getDeliveryTypeProvider()->getType($cart);

        try {
            $eta = $this->getClient()->getJobETA(
                $pickupAddress,
                $dropOffAddress,
                $deliveryType->getTransportType(),
                $deliveryType->getPackageType()
            );

            if (null === $eta) {
                $event->setRecurrences([]);

                return;
            }

            $interval = new DateInterval(sprintf('PT%sS', $eta));

            foreach ($event->getRecurrences() as $recurrence) {
                /** @var DateTime $start */
                $start = $recurrence->getStart();
                $start->add($interval);
                /** @var DateTime $end */
                $end = $recurrence->getStart();
                $end->add($interval);
                $recurrences[] = $recurrence;
            }

            $event->setRecurrences($recurrences);
        } catch (\Exception $e) {
            return;
        }
    }
}
