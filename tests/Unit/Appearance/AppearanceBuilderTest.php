<?php

declare(strict_types=1);

namespace Tests\FluxSE\SyliusStripePlugin\Unit\Appearance;

use FluxSE\SyliusStripePlugin\Appearance\AppearanceBuilder;
use PHPUnit\Framework\TestCase;

final class AppearanceBuilderTest extends TestCase
{
    private AppearanceBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new AppearanceBuilder();
    }

    public function test_maps_top_level_keys(): void
    {
        $result = $this->builder->build(['theme' => 'night', 'inputs' => 'condensed', 'labels' => 'floating']);

        self::assertSame(['theme' => 'night', 'inputs' => 'condensed', 'labels' => 'floating'], $result);
    }

    public function test_maps_color_variables(): void
    {
        $result = $this->builder->build(['colorPrimary' => '#ff0000', 'colorText' => '#000000']);

        self::assertSame(['variables' => ['colorPrimary' => '#ff0000', 'colorText' => '#000000'], 'theme' => 'stripe'], $result);
    }

    public function test_maps_font_and_border_radius_to_variables(): void
    {
        $result = $this->builder->build(['fontFamily' => 'Arial', 'borderRadius' => '4px']);

        self::assertSame(['variables' => ['fontFamily' => 'Arial', 'borderRadius' => '4px'], 'theme' => 'stripe'], $result);
    }

    public function test_strips_empty_string_values(): void
    {
        $result = $this->builder->build(['theme' => 'night', 'colorPrimary' => '', 'fontFamily' => '']);

        self::assertSame(['theme' => 'night'], $result);
    }

    public function test_strips_null_values(): void
    {
        $result = $this->builder->build(['theme' => null, 'colorPrimary' => null]);

        self::assertSame([], $result);
    }

    public function test_omits_variables_key_when_all_variable_fields_empty(): void
    {
        $result = $this->builder->build(['theme' => 'flat', 'colorPrimary' => '', 'borderRadius' => '']);

        self::assertArrayNotHasKey('variables', $result);
    }

    public function test_builds_full_appearance_object(): void
    {
        $config = [
            'theme' => 'night',
            'inputs' => 'condensed',
            'labels' => 'auto',
            'colorPrimary' => '#ff0000',
            'colorBackground' => '',
            'fontFamily' => 'Inter',
            'borderRadius' => '4px',
        ];

        $result = $this->builder->build($config);

        self::assertSame([
            'theme' => 'night',
            'inputs' => 'condensed',
            'labels' => 'auto',
            'variables' => [
                'colorPrimary' => '#ff0000',
                'fontFamily' => 'Inter',
                'borderRadius' => '4px',
            ],
        ], $result);
    }

    public function test_returns_empty_array_for_empty_config(): void
    {
        $result = $this->builder->build([]);

        self::assertSame([], $result);
    }
}
