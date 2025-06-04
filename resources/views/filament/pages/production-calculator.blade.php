<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <form wire:submit="calculateFinalPrice" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex gap-4 pt-4">

                        <x-filament::button type="button" color="warning" wire:click="saveFullCalculation">
                             <x-heroicon-o-calculator class="w-5 h-5 mr-2"/>
                             Simpan Perhitungan
                        </x-filament::button>

                        <x-filament::button type="button" color="gray" wire:click="resetCalculation">
                            <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"/>
                            Reset
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        @if($calculationResult && is_array($calculationResult))
        @endif
    </div>
</x-filament-panels::page>