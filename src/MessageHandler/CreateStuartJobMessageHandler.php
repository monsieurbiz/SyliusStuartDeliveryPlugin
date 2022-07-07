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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\MessageHandler;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use MonsieurBiz\SyliusSettingsPlugin\Settings\SettingsInterface;
use MonsieurBiz\SyliusShippingSlotPlugin\Entity\OrderInterface as MonsieurBizOrderInterface;
use MonsieurBiz\SyliusShippingSlotPlugin\Entity\SlotInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Calculator\StuartCalculator;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Entity\StuartJobIdInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Exception\StuartDeliveryException;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Helper\LoggerAwareTrait;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Message\ExportableOrderMessage;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Provider\DeliveryTypeProviderInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateStuartJobMessageHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ClientInterface $client;

    private SettingsInterface $stuartDeliverySettings;

    private ChannelContextInterface $channelContext;

    private LocaleContextInterface $localeContext;

    private DeliveryTypeProviderInterface $deliveryTypeProvider;

    private RepositoryInterface $orderRepository;

    private EntityManagerInterface $orderManager;

    private ManagerRegistry $managerRegistry;

    public function __construct(
        ClientInterface $client,
        SettingsInterface $stuartDeliverySettings,
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext,
        DeliveryTypeProviderInterface $deliveryTypeProvider,
        RepositoryInterface $orderRepository,
        EntityManagerInterface $orderManager,
        ManagerRegistry $managerRegistry
    ) {
        $this->client = $client;
        $this->stuartDeliverySettings = $stuartDeliverySettings;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
        $this->deliveryTypeProvider = $deliveryTypeProvider;
        $this->orderRepository = $orderRepository;
        $this->logger = new NullLogger();
        $this->orderManager = $orderManager;
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ExportableOrderMessage $orderMessage): void
    {
        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->find($orderMessage->getId());
        if (null === $order) {
            $this->logger->alert(sprintf('Order %d can\'t be found', $orderMessage->getId()));

            return;
        }

        $this->sendOrderAsJob($order);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function sendOrderAsJob(OrderInterface $order): void
    {
        $shippingAddress = $order->getShippingAddress();
        /** @var CustomerInterface|null $customer */
        $customer = $order->getCustomer();
        $slot = $this->getSlot($order);

        if (!$this->isStuartDelivery($order)) {
            return;
        }

        if (null === $shippingAddress || null === $customer || null === $slot) {
            throw new StuartDeliveryException('Incomplete order');
        }

        try {
            $stuartJobId = $this->getStuartId($order, $shippingAddress, $customer, $slot);
            /** @var StuartJobIdInterface $order */
            $order->setStuartJobId($stuartJobId);
            $this->orderManager->persist($order);
            $this->orderManager->flush();
        } catch (\Exception $exception) {
            /** @var OrderInterface $order */
            $this->logger->error('stuart_job_sender', ['order_id' => $order->getId(), 'exception' => (string) $exception]);
            $this->orderManager->rollback();
            $this->managerRegistry->resetManager();

            throw $exception;
        }
    }

    private function isStuartDelivery(OrderInterface $order): bool
    {
        /** @var false|ShipmentInterface $shipment */
        $shipment = $order->getShipments()->last();
        if (!$shipment) {
            return false;
        }

        return null !== ($shippingMethod = $shipment->getMethod()) && StuartCalculator::TYPE === $shippingMethod->getCalculator();
    }

    private function getPickupAddress(): string
    {
        return $this->client->getOnlineAddress(
            $this->getSettingValue('address') ?? '',
            $this->getSettingValue('postcode') ?? '',
            $this->getSettingValue('city') ?? ''
        );
    }

    private function getDropOffAddress(AddressInterface $shippingAddress): string
    {
        return $this->client->getOnlineAddress(
            $shippingAddress->getStreet() ?? '',
            $shippingAddress->getPostcode() ?? '',
            $shippingAddress->getCity() ?? ''
        );
    }

    private function getSettingValue(string $path): ?string
    {
        return $this->stuartDeliverySettings->getCurrentValue(
            $this->channelContext->getChannel(),
            $this->localeContext->getLocaleCode(),
            $path
        );
    }

    private function getSlot(OrderInterface $order): ?SlotInterface
    {
        if (!$order instanceof MonsieurBizOrderInterface) {
            return null;
        }

        $slots = $order->getSlots();
        if (0 < \count($slots)) {
            return $slots[0];
        }

        return null;
    }

    private function getPickupAt(SlotInterface $slot): ?DateTime
    {
        /** @var DateTime|null $time */
        $time = $slot->getTimestamp();
        $pickupDelay = $slot->getPickupDelay();
        if (null === $time || null === $pickupDelay) {
            return null;
        }
        $time->sub(new DateInterval(sprintf('PT%sM', $pickupDelay)));

        return $time;
    }

    private function getStuartId(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        SlotInterface $slot
    ): ?int {
        $pickupAddress = $this->getPickupAddress();
        $dropOffAddress = $this->getDropOffAddress($shippingAddress);
        $deliveryType = $this->deliveryTypeProvider->getType($order);
        $pickupAt = $this->getPickupAt($slot);

        return $this->client->createJob(
            $pickupAddress,
            $dropOffAddress,
            $order->getNumber() ?? '',
            $shippingAddress->getFirstName() ?? '',
            $shippingAddress->getLastName() ?? '',
            $customer->getEmail() ?? '',
            $shippingAddress->getPhoneNumber(),
            $pickupAt,
            $deliveryType->getTransportType(),
            $deliveryType->getPackageType(),
        );
    }
}
