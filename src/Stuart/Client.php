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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart;

use DateTime;
use MonsieurBiz\SyliusSettingsPlugin\Settings\SettingsInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Form\Type\SettingsType;
use Stuart\Client as StuartClient;
use Stuart\DropOff;
use Stuart\Infrastructure\Authenticator;
use Stuart\Infrastructure\Environment;
use Stuart\Infrastructure\HttpClient;
use Stuart\Job;
use Stuart\Pickup;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Webmozart\Assert\Assert;

final class Client implements ClientInterface
{
    private array $settings = [];

    private StuartClient $stuartClient;

    private string $monsieurbizStuartDeliveryApiMode;

    private string $monsieurbizStuartDeliveryApiClientId;

    private string $monsieurbizStuartDeliveryApiClientSecret;

    public function __construct(
        SettingsInterface $stuartDeliverySettings,
        string $monsieurbizStuartDeliveryApiMode,
        string $monsieurbizStuartDeliveryApiClientId,
        string $monsieurbizStuartDeliveryApiClientSecret
    ) {
        $this->settings = $stuartDeliverySettings->getSettingsValuesByChannelAndLocale();
        $this->monsieurbizStuartDeliveryApiMode = $monsieurbizStuartDeliveryApiMode;
        $this->monsieurbizStuartDeliveryApiClientId = $monsieurbizStuartDeliveryApiClientId;
        $this->monsieurbizStuartDeliveryApiClientSecret = $monsieurbizStuartDeliveryApiClientSecret;
    }

    public function init(?string $apiMode = null, ?string $apiClientId = null, ?string $apiClientSecret = null): void
    {
        // For variables we take in order :
        // - Parameters on this methods (It can comes from a form for example)
        // - Settings value (In database)
        // - Env vars value
        $environment = ($apiMode ?? $this->settings['api_mode'] ?? $this->monsieurbizStuartDeliveryApiMode) === SettingsType::API_MODE_PRODUCTION ? Environment::PRODUCTION : Environment::SANDBOX;
        $apiClientId = $apiClientId ?? $this->settings['api_client_id'] ?? $this->monsieurbizStuartDeliveryApiClientId;
        $apiClientSecret = $apiClientSecret ?? $this->settings['api_client_secret'] ?? $this->monsieurbizStuartDeliveryApiClientSecret;
        $cache = new Psr16Cache(new FilesystemAdapter());
        $authenticator = new Authenticator($environment, $apiClientId, $apiClientSecret, $cache);
        $this->stuartClient = new StuartClient(new HttpClient($authenticator));
    }

    public function getStuartClient(): StuartClient
    {
        if (!isset($this->stuartClient)) {
            $this->init();
        }

        return $this->stuartClient;
    }

    public function validatePickupAddress(string $address, string $postcode, string $city, ?string $phoneNumber): bool
    {
        $result = $this->getStuartClient()->validatePickupAddress(
            $this->getOnlineAddress($address, $postcode, $city),
            $phoneNumber
        );

        return $result->success ?? false;
    }

    public function validateDropOffAddress(string $address, string $postcode, string $city, ?string $phoneNumber): bool
    {
        $result = $this->getStuartClient()->validateDropoffAddress(
            $this->getOnlineAddress($address, $postcode, $city),
            $phoneNumber
        );

        return $result->success ?? false;
    }

    public function validateJob(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): bool
    {
        $job = $this->buildJob($pickupAddress, $dropOffAddress, $transportType, $packageType);
        $result = $this->getStuartClient()->validateJob($job);

        return true === $result;
    }

    public function getJobETA(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): ?int
    {
        $job = $this->buildJob($pickupAddress, $dropOffAddress, $transportType, $packageType);
        $result = $this->getStuartClient()->getEta($job);

        return $result->eta ?? null;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function createJob(
        string $pickupAddress,
        string $dropOffAddress,
        string $reference,
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone = null,
        ?DateTime $pickupAt = null,
        ?string $transportType = null,
        ?string $packageType = null
    ): ?int {
        $job = $this->buildJob($pickupAddress, $dropOffAddress, $transportType, $packageType);
        $job->setAssignmentCode($reference);

        if (null !== $pickupAt) {
            $pickup = $job->getPickups()[0] ?? null;
            Assert::isInstanceOf($pickup, Pickup::class);
            $pickup->setPickupAt($pickupAt);
        }

        $dropOff = $job->getDropOffs()[0] ?? null;
        Assert::isInstanceOf($dropOff, DropOff::class);
        $dropOff->setClientReference($reference);
        $dropOff->setContactFirstName($firstName);
        $dropOff->setContactLastName($lastName);
        $dropOff->setContactEmail($email);
        if (null !== $phone) {
            $dropOff->setContactPhone($phone);
        }

        $result = $this->getStuartClient()->createJob($job);

        return isset($result->error) ? null : $result->getId();
    }

    public function getPricing(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): ?int
    {
        $job = $this->buildJob($pickupAddress, $dropOffAddress, $transportType, $packageType);
        $result = $this->getStuartClient()->getPricing($job);
        if (isset($result->error)) {
            return null;
        }

        return (int) ($result->amount * 100);
    }

    public function getJob(int $jobId): ?Job
    {
        $result = $this->getStuartClient()->getJob($jobId);
        if (isset($result->error)) {
            return null;
        }

        return isset($result->error) ? null : $result;
    }

    public function getOnlineAddress(string $address, string $postcode, string $city): string
    {
        return sprintf('%s, %s %s', $address, $postcode, $city);
    }

    private function buildJob(string $pickupAddress, string $dropOffAddress, ?string $transportType = null, ?string $packageType = null): Job
    {
        $job = new Job();
        $job->addPickup($pickupAddress);
        $dropOff = $job->addDropOff($dropOffAddress);

        if (null !== $transportType) {
            $job->setTransportType($transportType);
        }

        if (null !== $packageType) {
            $dropOff->setPackageType($packageType);
        }

        return $job;
    }
}
