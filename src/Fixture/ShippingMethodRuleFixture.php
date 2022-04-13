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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Bundle\FixturesBundle\Fixture\FixtureInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethodRuleInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class ShippingMethodRuleFixture extends AbstractFixture implements FixtureInterface
{
    private RepositoryInterface $shippingMethodRepository;

    private EntityManagerInterface $shippingMethodManager;

    private EntityManagerInterface $shippingMethodRuleManager;

    private FactoryInterface $shippingMethodRuleFactory;

    public function __construct(
        RepositoryInterface $shippingMethodRepository,
        EntityManagerInterface $shippingMethodManager,
        EntityManagerInterface $shippingMethodRuleManager,
        FactoryInterface $shippingMethodRuleFactory
    ) {
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->shippingMethodRuleManager = $shippingMethodRuleManager;
        $this->shippingMethodRuleFactory = $shippingMethodRuleFactory;
    }

    public function load(array $options): void
    {
        $associations = [];
        foreach ($options['shipping_method_rule'] as $data) {
            /** @var ShippingMethodRuleInterface $shippingMethodRule */
            $shippingMethodRule = $this->shippingMethodRuleFactory->createNew();
            $associations = $this->prepareShippingMethodRule($shippingMethodRule, $data, $associations);
        }

        $this->associateToShippingMethods($associations);
    }

    public function getName(): string
    {
        return 'monsieurbiz_stuart_delivery';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
        /** @phpstan-ignore-next-line */
        $optionsNode
            ->children()
                ->arrayNode('shipping_method_rule')
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('rule')->cannotBeEmpty()->end()
                        ->variableNode('configuration')->end()
                        ->arrayNode('shipping_methods')->scalarPrototype()->end()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function prepareShippingMethodRule(ShippingMethodRuleInterface $shippingMethodRule, array $data, array $associations): array
    {
        $shippingMethodRule->setType($data['rule']);
        $shippingMethodRule->setConfiguration($data['configuration']);

        $this->shippingMethodRuleManager->persist($shippingMethodRule);

        if (true === isset($data['shipping_methods']) && true === \is_array($data['shipping_methods'])) {
            foreach ($data['shipping_methods'] as $methodCode) {
                $associations[$methodCode]['shipping_method_rules'][] = $shippingMethodRule;
            }
        }

        return $associations;
    }

    private function associateToShippingMethods(array $associations): void
    {
        foreach ($associations as $methodCode => $configurations) {
            /** @var ShippingMethodInterface|null $method */
            $method = $this->shippingMethodRepository->findOneBy(['code' => $methodCode]);
            if (null === $method) {
                continue;
            }
            $this->associateWithRules($method, $configurations['shipping_method_rules'] ?? []);
            $this->shippingMethodManager->persist($method);
        }

        $this->shippingMethodManager->flush();
    }

    private function associateWithRules(ShippingMethodInterface $method, array $rules): void
    {
        foreach ($rules as $shippingMethodRule) {
            $method->addRule($shippingMethodRule);
        }
    }
}
