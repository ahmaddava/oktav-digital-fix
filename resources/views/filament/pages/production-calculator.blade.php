<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <form wire:submit="calculate" class="space-y-6">
                    {{ $this->form }}
                    
                    <div class="flex gap-4 pt-4">
                        <x-filament::button type="submit" size="lg">
                            <x-heroicon-o-calculator class="w-5 h-5 mr-2"/>
                            Hitung Harga
                        </x-filament::button>
                        
                        <x-filament::button type="button" color="gray" wire:click="resetCalculation">
                            <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"/>
                            Reset
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Result Section -->
        @if($calculationResult)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-document-text class="w-6 h-6 inline mr-2"/>
                    Hasil Kalkulasi Harga
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Product Info -->
                    <div class="space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Informasi Produk</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Nama Produk:</span>
                                    <span class="font-medium">{{ $calculationResult['product_name'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ukuran:</span>
                                    <span class="font-medium">{{ $calculationResult['size'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Quantity:</span>
                                    <span class="font-medium">{{ $calculationResult['quantity'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Items -->
                        @if(!empty($calculationResult['selected_items']))
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">Material Terpilih</h4>
                            <div class="space-y-2 text-sm">
                                @foreach($calculationResult['selected_items'] as $item)
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $item['category'] }}</span>
                                        <span class="font-medium">{{ $item['name'] }}</span>
                                    </div>
                                    <span class="text-green-700 dark:text-green-300 font-medium">
                                        Rp {{ number_format($item['price'], 0, ',', '.') }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Cost Breakdown -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Rincian Biaya</h4>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Total Biaya Material:</span>
                                    <span class="font-medium dark:text-gray-200">Rp {{ number_format($calculationResult['total_material_cost'], 0, ',', '.') }}</span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Ongkos Produksi:</span>
                                    <span class="font-medium dark:text-gray-200">Rp {{ number_format($calculationResult['production_cost'], 0, ',', '.') }}</span>
                                </div>
                                
                                @if($calculationResult['poly_cost'] > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Ongkos Poly:</span>
                                    <span class="font-medium dark:text-gray-200">Rp {{ number_format($calculationResult['poly_cost'], 0, ',', '.') }}</span>
                                </div>
                                @endif
                                
                                @if($calculationResult['knife_cost'] > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Ongkos Pisau:</span>
                                    <span class="font-medium dark:text-gray-200">Rp {{ number_format($calculationResult['knife_cost'], 0, ',', '.') }}</span>
                                </div>
                                @endif
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Profit:</span>
                                    <span class="font-medium dark:text-gray-200">Rp {{ number_format($calculationResult['profit'], 0, ',', '.') }}</span>
                                </div>
                                
                                <hr class="border-gray-300 dark:border-gray-600">
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-900 dark:text-gray-100">Harga per Item:</span>
                                    <span class="font-medium text-blue-600 dark:text-blue-400">
                                        Rp {{ number_format($calculationResult['total_price_per_item'], 0, ',', '.') }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between text-lg font-bold">
                                    <span class="text-gray-900 dark:text-gray-100">TOTAL HARGA:</span>
                                    <span class="text-blue-600 dark:text-blue-400">
                                        Rp {{ number_format($calculationResult['total_price'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    
                        @if($calculationResult['notes'])
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-lg">
                            <h4 class="font-medium text-yellow-900 dark:text-yellow-100 mb-2">Catatan</h4>
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ $calculationResult['notes'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>