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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Controller;

use MonsieurBiz\SyliusStuartDeliveryPlugin\Entity\StuartJobIdInterface;
use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\ClientInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class JobController extends AbstractController
{
    private ClientInterface $client;

    private CustomerContextInterface $customerContext;

    private RepositoryInterface $orderRepository;

    public function __construct(
        ClientInterface $client,
        CustomerContextInterface $customerContext,
        RepositoryInterface $orderRepository
    ) {
        $this->client = $client;
        $this->customerContext = $customerContext;
        $this->orderRepository = $orderRepository;
    }

    public function trackingAction(int $orderId): Response
    {
        $order = $this->orderRepository->find($orderId);

        /** @var OrderInterface|null $order */
        if (false === $this->hasAuth($order)) {
            throw new UnauthorizedHttpException('');
        }

        $trackingUrl = null;
        /** @var StuartJobIdInterface $order */
        $jobId = $this->getJobId($order);
        $job = $this->client->getJob($jobId);
        if (null !== $job && 0 < \count($job->getDeliveries())) {
            $delivery = $job->getDeliveries()[0];
            $trackingUrl = $delivery->getTrackingUrl();
        }

        return $this->render(
            '@MonsieurBizSyliusStuartDeliveryPlugin/Shop/Stuart/_tracking.html.twig',
            ['trackingUrl' => $trackingUrl]
        );
    }

    private function getJobId(StuartJobIdInterface $order): int
    {
        if (null === $order->getStuartJobId()) {
            throw new NotFoundHttpException();
        }

        return $order->getStuartJobId();
    }

    private function hasAuth(?OrderInterface $order): bool
    {
        return
            null !== $order &&
            null !== $order->getCustomer() &&
            null !== $this->customerContext->getCustomer() &&
            $order->getCustomer()->getId() === $this->customerContext->getCustomer()->getId()
        ;
    }
}
