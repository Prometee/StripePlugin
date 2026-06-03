<?php

declare(strict_types=1);

namespace FluxSE\SyliusStripePlugin\Appearance;

final class AppearanceBuilder
{
    private const TOP_LEVEL_KEYS = ['theme', 'inputs', 'labels'];

    private const VARIABLE_KEYS = ['colorPrimary', 'colorBackground', 'colorText', 'colorDanger', 'fontFamily', 'borderRadius', 'spacingUnit'];

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function build(array $config): array
    {
        $appearance = [];

        foreach (self::TOP_LEVEL_KEYS as $key) {
            $value = $config[$key] ?? null;
            if (null !== $value && '' !== $value) {
                $appearance[$key] = $value;
            }
        }

        $variables = [];
        foreach (self::VARIABLE_KEYS as $key) {
            $value = $config[$key] ?? null;
            if (null !== $value && '' !== $value) {
                $variables[$key] = $value;
            }
        }

        if ([] !== $variables) {
            $appearance['variables'] = $variables;
        }

        return $appearance;
    }
}
