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

namespace MonsieurBiz\SyliusStuartDeliveryPlugin\Form\Type;

use MonsieurBiz\SyliusStuartDeliveryPlugin\Stuart\ClientInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class PackageAndDeliveryType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transportType', ChoiceType::class, [
                'required' => false,
                'label' => 'monsieurbiz_stuart_delivery_plugin.ui.transport_type',
                'choices' => [
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.bike' => ClientInterface::TRANSPORT_TYPE_BIKE,
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.cargo_bike' => ClientInterface::TRANSPORT_TYPE_CARGO_BIKE,
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.cargo_bike_xl' => ClientInterface::TRANSPORT_TYPE_CARGO_BIKE_XL,
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.motorbike' => ClientInterface::TRANSPORT_TYPE_MOTORBIKE,
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.motorbike_xl' => ClientInterface::TRANSPORT_TYPE_MOTORBIKE_XL,
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.car' => ClientInterface::TRANSPORT_TYPE_CAR,
                    'monsieurbiz_stuart_delivery_plugin.ui.transport_types.van' => ClientInterface::TRANSPORT_TYPE_VAN,
                ],
                'validation_groups' => ['stuart'],
                'help' => 'monsieurbiz_stuart_delivery_plugin.ui.transport_type_help',
            ])
            ->add('packageType', ChoiceType::class, [
                'required' => false,
                'label' => 'monsieurbiz_stuart_delivery_plugin.ui.package_type',
                'choices' => [
                    'monsieurbiz_stuart_delivery_plugin.ui.package_types.xs' => ClientInterface::PACKAGE_TYPE_XS,
                    'monsieurbiz_stuart_delivery_plugin.ui.package_types.s' => ClientInterface::PACKAGE_TYPE_S,
                    'monsieurbiz_stuart_delivery_plugin.ui.package_types.m' => ClientInterface::PACKAGE_TYPE_M,
                    'monsieurbiz_stuart_delivery_plugin.ui.package_types.l' => ClientInterface::PACKAGE_TYPE_L,
                    'monsieurbiz_stuart_delivery_plugin.ui.package_types.xl' => ClientInterface::PACKAGE_TYPE_XL,
                ],
                'validation_groups' => ['stuart'],
                'help' => 'monsieurbiz_stuart_delivery_plugin.ui.package_type_help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $validator = function (array $configuratation, ExecutionContextInterface $context): void {
            if (empty($configuratation['transportType']) && empty($configuratation['packageType'])) {
                $context
                    ->buildViolation('monsieurbiz_stuart_delivery_plugin.no_package_type_and_no_transport_type')
                    ->atPath('data.translations[en_US].name')
                    ->addViolation()
                ;
            }
        };

        $resolver->setDefault('error_blubbling', false);
        $resolver->setDefaults([
            'validation_groups' => ['stuart'],
            'constraints' => [
                new Assert\Callback([
                    'callback' => $validator,
                    'groups' => ['stuart'],
                ]),
            ],
            'error_mapping' => [
                '.' => 'transportType',
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'monsieurbiz_stuart_delivery_package_and_delivery';
    }
}
