<?php

namespace App\Filament\Components;

use Filament\Forms\Components\TextInput;

class CurrencyInput
{
    /**
     * Create a currency input field with real-time formatting
     * 
     * @param string $name Field name
     * @return TextInput
     */
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->placeholder('0')
            ->extraInputAttributes([
                'x-data' => '{}',
                'x-on:input' => '$el.value = $el.value.replace(/[^0-9]/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                'inputmode' => 'numeric',
            ])
            ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
            ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0);
    }
}
