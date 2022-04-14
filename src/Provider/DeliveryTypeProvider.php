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

use MonsieurBiz\SyliusSettingsPlugin\Settings\SettingsInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Model\DeliveryType;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Model\DeliveryTypeInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;

final class DeliveryTypeProvider implements DeliveryTypeProviderInterface
{
    private SettingsInterface $stuartDeliverySettings;

    private ChannelContextInterface $channelContext;

    private LocaleContextInterface $localeContext;

    public function __construct(
        SettingsInterface $stuartDeliverySettings,
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext
    ) {
        $this->stuartDeliverySettings = $stuartDeliverySettings;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
    }

    /**
     * This method wil return package type based on order contains (item size)
     *  and delivery country.
     *  For the moment we only use values in settings.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getType(OrderInterface $order): DeliveryTypeInterface
    {
        $packageType = $this->getSettingValue('package_type');
        $transportType = $this->getSettingValue('transport_type');

        return new DeliveryType($packageType, $transportType);
    }

    private function getSettingValue(string $path): ?string
    {
        return $this->stuartDeliverySettings->getCurrentValue(
            $this->channelContext->getChannel(),
            $this->localeContext->getLocaleCode(),
            $path
        );
    }
}
