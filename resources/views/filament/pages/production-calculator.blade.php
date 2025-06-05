<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                {{-- Form Utama --}}
                <form wire:submit="calculateFinalPrice" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex gap-4 pt-4 border-t dark:border-gray-700">
                        {{-- Tombol Hitung Estimasi Harga --}}
                        <x-filament::button type="submit" color="success">
                            <x-heroicon-o-play-circle class="w-5 h-5 mr-2"/>
                            Hitung Estimasi Harga
                        </x-filament::button>

                        {{-- Tombol Simpan Perhitungan --}}
                        <x-filament::button type="button" color="warning" wire:click="saveFullCalculation" wire:loading.attr="disabled">
                            <span wire:loading wire:target="saveFullCalculation" class="mr-2">
                                <x-filament::loading-indicator class="h-5 w-5"/>
                            </span>
                            <x-heroicon-o-document-check wire:loading.remove wire:target="saveFullCalculation" class="w-5 h-5 mr-2"/>
                            Simpan Perhitungan
                        </x-filament::button>

                        {{-- Tombol Reset --}}
                        <x-filament::button type="button" color="gray" wire:click="resetCalculation">
                            <x-heroicon-o-arrow-path class="w-5 h-5 mr-2"/>
                            Reset
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bagian untuk Menampilkan Hasil Perhitungan --}}
        @if($this->summaryTotalPrice !== null)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Ringkasan Estimasi Biaya Produksi</h2>

                {{-- Total Estimasi Keseluruhan --}}
                <div class="bg-primary-50 dark:bg-primary-700/20 p-4 rounded-lg">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Estimasi Biaya Keseluruhan</dt>
                    <dd class="mt-1 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $this->calculationResult }}</dd>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Kolom Kiri: Ringkasan Biaya Umum --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Detail Biaya (Total untuk {{ $this->data['quantity'] ?? 1 }} pcs)</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Jumlah Pesanan:</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($this->data['quantity'] ?? 1, 0, ',', '.') }} pcs</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Total Biaya Material:</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->summaryTotalMaterialCost ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Total Ongkos Produksi:</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->summaryTotalProductionWorkCost ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            @if(($this->summaryTotalPolyCost ?? 0) > 0)
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Total Ongkos Poly ({{ $this->data['poly_dimension'] ?? 'N/A' }}):</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->summaryTotalPolyCost ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            @endif
                            @if(($this->summaryActualKnifeCost ?? 0) > 0 && ($this->data['include_knife_cost'] ?? 'tidak_ada') === 'ada')
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Total Ongkos Pisau:</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->summaryActualKnifeCost ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t dark:border-gray-700">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Total Profit:</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($this->summaryProfitAmount ?? 0, 0, ',', '.') }}</dd>
                            </div>
                             <div class="flex justify-between pt-2 border-t dark:border-gray-700">
                                <dt class="text-sm font-medium text-gray-700 dark:text-gray-200">Harga Jual per Item:</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-white">Rp {{ number_format($this->summarySellingPricePerItem ?? 0, 0, ',', '.') }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Kolom Kanan: Rincian Harga Satuan Komponen --}}
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Rincian Harga Satuan Komponen (per pcs)</h3>
                        <div class="space-y-3">
                            @php
                                $boxType = $this->data['box_type_selection'] ?? null;
                                $getDynamicLabel = function($part, $defaultPrefix = "Box") use ($boxType) {
                                    if ($part === 'atas') {
                                        if ($boxType === 'JENDELA') return "Kuping";
                                        if ($boxType === 'SELONGSONG') return "Selongsong";
                                        return $defaultPrefix . " Atas";
                                    }
                                    if ($part === 'lidah' && in_array($boxType, ['BUKU PITA', 'BUKU MAGNET'])) return "Lidah";
                                    if ($part === 'selongsong_part' && $boxType === 'SELONGSONG') return "Selongsong"; // Untuk membedakan dengan $getDynamicLabel('atas') untuk selongsong
                                    return $defaultPrefix . " " . Str::title($part);
                                };
                            @endphp

                            {{-- Harga Satuan Board --}}
                            @if($this->includeBoard && ($this->unitPriceBoardAtas > 0 || $this->unitPriceBoardBawah > 0 || $this->unitPriceBoardKuping > 0 || $this->unitPriceBoardLidah > 0 || $this->unitPriceBoardSelongsong > 0))
                                <div class="p-3 border rounded-md dark:border-gray-700">
                                    <p class="font-medium text-gray-700 dark:text-gray-200 mb-1">Board:</p>
                                    @if($this->unitPriceBoardAtas > 0 && in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury']))
                                        <div class="flex justify-between text-xs"><span>{{ $getDynamicLabel('atas', 'Box') }} (Board):</span> <span>Rp {{ number_format($this->unitPriceBoardAtas, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceBoardKuping > 0 && $boxType === 'JENDELA')
                                        <div class="flex justify-between text-xs"><span>Kuping (Board):</span> <span>Rp {{ number_format($this->unitPriceBoardKuping, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceBoardSelongsong > 0 && $boxType === 'SELONGSONG')
                                        <div class="flex justify-between text-xs"><span>Selongsong (Board):</span> <span>Rp {{ number_format($this->unitPriceBoardSelongsong, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceBoardBawah > 0 && in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']))
                                        <div class="flex justify-between text-xs"><span>Box Bawah (Board):</span> <span>Rp {{ number_format($this->unitPriceBoardBawah, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceBoardLidah > 0 && in_array($boxType, ['BUKU PITA', 'BUKU MAGNET']))
                                        <div class="flex justify-between text-xs"><span>Lidah (Board):</span> <span>Rp {{ number_format($this->unitPriceBoardLidah, 0, ',', '.') }}</span></div>
                                    @endif
                                </div>
                            @endif

                            {{-- Harga Satuan Cover Luar --}}
                             @if($this->includeCoverLuar && ($this->unitPriceClAtas > 0 || $this->unitPriceClBawah > 0 || $this->unitPriceClKuping > 0 || $this->unitPriceClLidah > 0 || $this->unitPriceClSelongsong > 0))
                                <div class="p-3 border rounded-md dark:border-gray-700">
                                    <p class="font-medium text-gray-700 dark:text-gray-200 mb-1">Cover Luar (CL):</p>
                                    @if($this->unitPriceClAtas > 0 && in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury']))
                                        <div class="flex justify-between text-xs"><span>{{ $getDynamicLabel('atas', 'Box') }} (CL):</span> <span>Rp {{ number_format($this->unitPriceClAtas, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceClKuping > 0 && $boxType === 'JENDELA')
                                        <div class="flex justify-between text-xs"><span>Kuping (CL):</span> <span>Rp {{ number_format($this->unitPriceClKuping, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceClSelongsong > 0 && $boxType === 'SELONGSONG')
                                        <div class="flex justify-between text-xs"><span>Selongsong (CL):</span> <span>Rp {{ number_format($this->unitPriceClSelongsong, 0, ',', '.') }}</span></div>
                                    @endif
                                     @if($this->unitPriceClBawah > 0 && in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']))
                                        <div class="flex justify-between text-xs"><span>Box Bawah (CL):</span> <span>Rp {{ number_format($this->unitPriceClBawah, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceClLidah > 0 && in_array($boxType, ['BUKU PITA', 'BUKU MAGNET']))
                                        <div class="flex justify-between text-xs"><span>Lidah (CL):</span> <span>Rp {{ number_format($this->unitPriceClLidah, 0, ',', '.') }}</span></div>
                                    @endif
                                </div>
                            @endif

                            {{-- Harga Satuan Cover Dalam --}}
                             @if($this->includeCoverDalam && ($this->unitPriceCdAtas > 0 || $this->unitPriceCdBawah > 0 || $this->unitPriceCdLidah > 0 || $this->unitPriceCdSelongsong > 0))
                                <div class="p-3 border rounded-md dark:border-gray-700">
                                     <p class="font-medium text-gray-700 dark:text-gray-200 mb-1">Cover Dalam (CD):</p>
                                     @if($this->unitPriceCdAtas > 0 && in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA']))
                                        <div class="flex justify-between text-xs"><span>{{ $getDynamicLabel('atas', 'Box') }} (CD):</span> <span>Rp {{ number_format($this->unitPriceCdAtas, 0, ',', '.') }}</span></div>
                                    @endif
                                     @if($this->unitPriceCdSelongsong > 0 && $boxType === 'SELONGSONG')
                                        <div class="flex justify-between text-xs"><span>Selongsong (CD):</span> <span>Rp {{ number_format($this->unitPriceCdSelongsong, 0, ',', '.') }}</span></div>
                                    @endif
                                     @if($this->unitPriceCdBawah > 0 && in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']))
                                        <div class="flex justify-between text-xs"><span>Box Bawah (CD):</span> <span>Rp {{ number_format($this->unitPriceCdBawah, 0, ',', '.') }}</span></div>
                                    @endif
                                    @if($this->unitPriceCdLidah > 0 && in_array($boxType, ['BUKU PITA', 'BUKU MAGNET']))
                                        <div class="flex justify-between text-xs"><span>Lidah (CD):</span> <span>Rp {{ number_format($this->unitPriceCdLidah, 0, ',', '.') }}</span></div>
                                    @endif
                                </div>
                            @endif
                            
                            {{-- Harga Satuan Busa --}}
                            @if($this->includeBusa && $this->unitPriceBusa > 0)
                                <div class="p-3 border rounded-md dark:border-gray-700">
                                    <p class="font-medium text-gray-700 dark:text-gray-200 mb-1">Busa:</p>
                                    <div class="flex justify-between text-xs"><span>Busa:</span> <span>Rp {{ number_format($this->unitPriceBusa, 0, ',', '.') }}</span></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Hapus atau koreksi @if yang lama --}}
        {{-- @if($calculationResult && is_array($calculationResult))
        @endif --}}
    </div>
</x-filament-panels::page>
