<?php

namespace App\Filament\Pages;

use App\Models\ProductionCategory;
use App\Models\ProductionItem;
use App\Models\PriceCalculation; // Jika Anda akan menyimpan hasil kalkulasi
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Models\MasterCost;
use App\Models\PolyCost;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;

class ProductionCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Kalkulator Harga';
    protected static string $view = 'filament.pages.production-calculator';

    public ?array $data = [];

    // Properties to control visibility and hold state for calculations
    public bool $includeBoard = false;
    public bool $includeCoverLuar = false;
    public bool $includeCoverDalam = false;
    public bool $includeCoverLuarLidah = false;
    public bool $includeBusa = false;

    public $calculationResult = null; // Untuk menyimpan hasil akhir kalkulasi harga

    public function mount(): void
    {
        // Inisialisasi form dengan state awal, termasuk nilai default untuk toggle
        $this->form->fill([
            'quantity' => 1,
            'include_knife_cost' => 'tidak_ada',
            'includeBoard' => $this->includeBoard,
            'includeCoverLuar' => $this->includeCoverLuar,
            'includeCoverDalam' => $this->includeCoverDalam,
            'includeCoverLuarLidah' => $this->includeCoverLuarLidah,
            'includeBusa' => $this->includeBusa,
            // Anda bisa menambahkan nilai default lain di sini
        ]);
        // Panggil update kalkulasi jika ingin ada nilai awal yang dihitung saat halaman dimuat
        // $this->updateAllCalculations();
    }

    // Method untuk mendapatkan harga item berdasarkan kuantitas (jika ada tier pricing)
    protected function getItemPriceByQuantity(?ProductionItem $item, int $quantity): float
    {
        if (!$item) {
            return 0;
        }
        // Implementasi logika tier pricing Anda di sini
        // Contoh sederhana:
        // $price = $item->price;
        // $tierPrices = $item->prices()->where('min_quantity', '<=', $quantity)->orderBy('min_quantity', 'desc')->first();
        // if ($tierPrices) {
        //     $price = $tierPrices->price;
        // }
        // return (float)$price;
        return (float)($item->price ?? 0); // Gunakan harga dasar jika tidak ada tier pricing
    }

    /**
     * Menghitung dimensi potongan board.
     * RUMUS INI PERLU DIVERIFIKASI SESUAI KEBUTUHAN PRODUKSI ANDA.
     * Saat ini: (2 * tinggi_input) + sisi_panjang_input + konstanta_allowance.
     * @param float|null $panjangInput Dimensi sisi utama (panjang atau lebar box)
     * @param float|null $lebarInput Dimensi sisi sekunder (tidak digunakan di rumus saat ini)
     * @param float|null $tinggiInput Dimensi tinggi box
     * @return float
     */
    protected function calculateBoardDimension(?float $panjangInput, ?float $lebarInput, ?float $tinggiInput): float
    {
        if ($panjangInput === null || $tinggiInput === null || !is_numeric($panjangInput) || !is_numeric($tinggiInput)) {
            return 0;
        }
        // FORMULA UTAMA UNTUK BOARD - Sesuaikan konstanta '3' jika perlu
        return (2 * $tinggiInput) + $panjangInput + 3;
    }

    /**
     * Menghitung dimensi potongan cover (luar, dalam, lidah).
     * RUMUS INI PERLU DIVERIFIKASI SESUAI KEBUTUHAN PRODUKSI ANDA.
     * @param float|null $panjangInput Dimensi sisi utama (panjang atau lebar box)
     * @param float|null $tinggiInput Dimensi tinggi box
     * @param float $allowance Konstanta tambahan (misal: 3+4 untuk cover luar, 3 untuk cover dalam)
     * @return float
     */
    protected function calculateCoverDimension(?float $panjangInput, ?float $tinggiInput, float $allowance): float
    {
        if ($panjangInput === null || $tinggiInput === null || !is_numeric($panjangInput) || !is_numeric($tinggiInput)) {
            return 0;
        }
        return (2 * $tinggiInput) + $panjangInput + $allowance;
    }

    /**
     * Menghitung dimensi potongan busa.
     * RUMUS INI PERLU DIVERIFIKASI SESUAI KEBUTUHAN PRODUKSI ANDA.
     * @param float|null $panjangInput Dimensi panjang box
     * @param float $allowance Konstanta tambahan
     * @return float
     */
    protected function calculateBusaDimension(?float $panjangInput, float $allowance): float
    {
        if ($panjangInput === null || !is_numeric($panjangInput)) {
            return 0;
        }
        return $panjangInput + $allowance;
    }


    // Helper function to calculate Qty1, Qty2, Final Qty for a given item type
    protected function calculateSheetQuantities($itemPanjang, $itemLebar, $panjangKertas, $lebarKertas): array
    {
        if (!is_numeric($itemPanjang) || !is_numeric($itemLebar) || !is_numeric($panjangKertas) || !is_numeric($lebarKertas) ||
            $itemPanjang <= 0 || $itemLebar <= 0 || $panjangKertas <= 0 || $lebarKertas <= 0) {
            return ['qty1' => 0, 'qty2' => 0, 'final_qty' => 0];
        }

        $qty1 = floor($panjangKertas / $itemPanjang) * floor($lebarKertas / $itemLebar);
        $qty2 = floor($lebarKertas / $itemPanjang) * floor($panjangKertas / $itemLebar);
        $finalQty = max($qty1, $qty2);

        return ['qty1' => (int)$qty1, 'qty2' => (int)$qty2, 'final_qty' => (int)$finalQty];
    }


    // Method untuk menghitung semua dimensi dan kuantitas berdasarkan input form
    protected function calculateAllDimensionsAndQuantities(array $data): array
    {
        $calculations = [];
        $this->includeBoard = (bool)($data['includeBoard'] ?? false);
        $this->includeCoverLuar = (bool)($data['includeCoverLuar'] ?? false);
        $this->includeCoverDalam = (bool)($data['includeCoverDalam'] ?? false);
        $this->includeCoverLuarLidah = (bool)($data['includeCoverLuarLidah'] ?? false);
        $this->includeBusa = (bool)($data['includeBusa'] ?? false);

        $atasPanjang = (float)($data['atas_panjang'] ?? 0);
        $atasLebar = (float)($data['atas_lebar'] ?? 0);
        $atasTinggi = (float)($data['atas_tinggi'] ?? 0);
        $bawahPanjang = (float)($data['bawah_panjang'] ?? 0);
        $bawahLebar = (float)($data['bawah_lebar'] ?? 0);
        $bawahTinggi = (float)($data['bawah_tinggi'] ?? 0);

        // Board Calculations
        if ($this->includeBoard) {
            $item = ProductionItem::find($data['selected_item_board'] ?? null);
            $sheetPanjang = (float)($item->panjang_kertas ?? 0);
            $sheetLebar = (float)($item->lebar_kertas ?? 0);
            $calculations['board_panjang_kertas'] = $sheetPanjang;
            $calculations['board_lebar_kertas'] = $sheetLebar;

            $calculations['board_panjang_atas'] = $this->calculateBoardDimension($atasPanjang, $atasLebar, $atasTinggi);
            $calculations['board_lebar_atas'] = $this->calculateBoardDimension($atasLebar, $atasPanjang, $atasTinggi); // Sisi lebar box jadi input panjang
            $calculations['board_panjang_bawah'] = $this->calculateBoardDimension($bawahPanjang, $bawahLebar, $bawahTinggi);
            $calculations['board_lebar_bawah'] = $this->calculateBoardDimension($bawahLebar, $bawahPanjang, $bawahTinggi); // Sisi lebar box jadi input panjang

            $qtyAtas = $this->calculateSheetQuantities($calculations['board_panjang_atas'], $calculations['board_lebar_atas'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_board_atas'] = $qtyAtas['qty1'];
            $calculations['qty2_board_atas'] = $qtyAtas['qty2'];
            $calculations['final_qty_board_atas'] = $qtyAtas['final_qty'];

            $qtyBawah = $this->calculateSheetQuantities($calculations['board_panjang_bawah'], $calculations['board_lebar_bawah'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_board_bawah'] = $qtyBawah['qty1'];
            $calculations['qty2_board_bawah'] = $qtyBawah['qty2'];
            $calculations['final_qty_board_bawah'] = $qtyBawah['final_qty'];
        }

        // Cover Luar Calculations (ALLOWANCE = 3 + 4 = 7, SESUAIKAN!)
        $coverLuarAllowance = 7.0; // Misal: 3cm untuk lem, 4cm untuk lipatan. SESUAIKAN!
        if ($this->includeCoverLuar) {
            $item = ProductionItem::find($data['selected_item_cover_luar'] ?? null);
            $sheetPanjang = (float)($item->panjang_kertas ?? 0);
            $sheetLebar = (float)($item->lebar_kertas ?? 0);
            $calculations['cover_luar_panjang_kertas'] = $sheetPanjang;
            $calculations['cover_luar_lebar_kertas'] = $sheetLebar;

            $calculations['cover_luar_panjang_box_atas'] = $this->calculateCoverDimension($atasPanjang, $atasTinggi, $coverLuarAllowance);
            $calculations['cover_luar_lebar_box_atas'] = $this->calculateCoverDimension($atasLebar, $atasTinggi, $coverLuarAllowance);
            $calculations['cover_luar_panjang_box_bawah'] = $this->calculateCoverDimension($bawahPanjang, $bawahTinggi, $coverLuarAllowance);
            $calculations['cover_luar_lebar_box_bawah'] = $this->calculateCoverDimension($bawahLebar, $bawahTinggi, $coverLuarAllowance);

            $qtyAtas = $this->calculateSheetQuantities($calculations['cover_luar_panjang_box_atas'], $calculations['cover_luar_lebar_box_atas'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_cover_luar_atas'] = $qtyAtas['qty1'];
            $calculations['qty2_cover_luar_atas'] = $qtyAtas['qty2'];
            $calculations['final_qty_cover_luar_atas'] = $qtyAtas['final_qty'];

            $qtyBawah = $this->calculateSheetQuantities($calculations['cover_luar_panjang_box_bawah'], $calculations['cover_luar_lebar_box_bawah'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_cover_luar_bawah'] = $qtyBawah['qty1'];
            $calculations['qty2_cover_luar_bawah'] = $qtyBawah['qty2'];
            $calculations['final_qty_cover_luar_bawah'] = $qtyBawah['final_qty'];
        }

        // Cover Dalam Calculations (ALLOWANCE = 3, SESUAIKAN!)
        $coverDalamAllowance = 3.0; // Misal: 3cm untuk lem/lipatan. SESUAIKAN!
        if ($this->includeCoverDalam) {
            $item = ProductionItem::find($data['selected_item_cover_dalam'] ?? null);
            $sheetPanjang = (float)($item->panjang_kertas ?? 0);
            $sheetLebar = (float)($item->lebar_kertas ?? 0);
            $calculations['cover_dalam_panjang_kertas'] = $sheetPanjang;
            $calculations['cover_dalam_lebar_kertas'] = $sheetLebar;

            $calculations['cover_dalam_panjang_box_atas'] = $this->calculateCoverDimension($atasPanjang, $atasTinggi, $coverDalamAllowance);
            $calculations['cover_dalam_lebar_box_atas'] = $this->calculateCoverDimension($atasLebar, $atasTinggi, $coverDalamAllowance);
            $calculations['cover_dalam_panjang_box_bawah'] = $this->calculateCoverDimension($bawahPanjang, $bawahTinggi, $coverDalamAllowance);
            $calculations['cover_dalam_lebar_box_bawah'] = $this->calculateCoverDimension($bawahLebar, $bawahTinggi, $coverDalamAllowance);

            $qtyAtas = $this->calculateSheetQuantities($calculations['cover_dalam_panjang_box_atas'], $calculations['cover_dalam_lebar_box_atas'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_cover_dalam_atas'] = $qtyAtas['qty1'];
            $calculations['qty2_cover_dalam_atas'] = $qtyAtas['qty2'];
            $calculations['final_qty_cover_dalam_atas'] = $qtyAtas['final_qty'];

            $qtyBawah = $this->calculateSheetQuantities($calculations['cover_dalam_panjang_box_bawah'], $calculations['cover_dalam_lebar_box_bawah'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_cover_dalam_bawah'] = $qtyBawah['qty1'];
            $calculations['qty2_cover_dalam_bawah'] = $qtyBawah['qty2'];
            $calculations['final_qty_cover_dalam_bawah'] = $qtyBawah['final_qty'];
        }
        
        // Cover Luar Lidah Calculations (ALLOWANCE SAMA DENGAN COVER LUAR YAITU 7, SESUAIKAN!)
        // Asumsi formula dan allowance sama dengan Cover Luar, jika beda, buat fungsi/allowance baru.
        $coverLuarLidahAllowance = $coverLuarAllowance; // atau nilai spesifik lain
        if ($this->includeCoverLuarLidah) {
            $item = ProductionItem::find($data['selected_item_cover_luar_lidah'] ?? null);
            $sheetPanjang = (float)($item->panjang_kertas ?? 0);
            $sheetLebar = (float)($item->lebar_kertas ?? 0);
            $calculations['cover_luar_lidah_panjang_kertas'] = $sheetPanjang;
            $calculations['cover_luar_lidah_lebar_kertas'] = $sheetLebar;

            $calculations['cover_luar_lidah_panjang_box_atas'] = $this->calculateCoverDimension($atasPanjang, $atasTinggi, $coverLuarLidahAllowance);
            $calculations['cover_luar_lidah_lebar_box_atas'] = $this->calculateCoverDimension($atasLebar, $atasTinggi, $coverLuarLidahAllowance);
            $calculations['cover_luar_lidah_panjang_box_bawah'] = $this->calculateCoverDimension($bawahPanjang, $bawahTinggi, $coverLuarLidahAllowance);
            $calculations['cover_luar_lidah_lebar_box_bawah'] = $this->calculateCoverDimension($bawahLebar, $bawahTinggi, $coverLuarLidahAllowance);

            $qtyAtas = $this->calculateSheetQuantities($calculations['cover_luar_lidah_panjang_box_atas'], $calculations['cover_luar_lidah_lebar_box_atas'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_cover_luar_lidah_atas'] = $qtyAtas['qty1'];
            $calculations['qty2_cover_luar_lidah_atas'] = $qtyAtas['qty2'];
            $calculations['final_qty_cover_luar_lidah_atas'] = $qtyAtas['final_qty'];

            $qtyBawah = $this->calculateSheetQuantities($calculations['cover_luar_lidah_panjang_box_bawah'], $calculations['cover_luar_lidah_lebar_box_bawah'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_cover_luar_lidah_bawah'] = $qtyBawah['qty1'];
            $calculations['qty2_cover_luar_lidah_bawah'] = $qtyBawah['qty2'];
            $calculations['final_qty_cover_luar_lidah_bawah'] = $qtyBawah['final_qty'];
        }

        // Busa Calculations (ALLOWANCE = 3, SESUAIKAN!)
        // Umumnya busa mengikuti dimensi dalam box bawah.
        $busaAllowance = 3.0; // Misal: tambahan kelonggaran/ketebalan. SESUAIKAN!
        if ($this->includeBusa) {
            $item = ProductionItem::find($data['selected_item_busa'] ?? null);
            $sheetPanjang = (float)($item->panjang_kertas ?? 0); // Ini dimensi bahan busa per lembar
            $sheetLebar = (float)($item->lebar_kertas ?? 0);
            $calculations['busa_panjang_kertas'] = $sheetPanjang;
            $calculations['busa_lebar_kertas'] = $sheetLebar;

            // Dimensi potongan busa biasanya adalah P Box Bawah + allowance dan L Box Bawah + allowance
            // Bukan (2*Tinggi)+Panjang. Sesuaikan jika berbeda.
            $calculations['panjang_busa'] = $this->calculateBusaDimension($bawahPanjang, $busaAllowance);
            $calculations['lebar_busa'] = $this->calculateBusaDimension($bawahLebar, $busaAllowance); // asumsikan allowance sama

            $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $sheetPanjang, $sheetLebar);
            $calculations['qty1_busa'] = $qty['qty1'];
            $calculations['qty2_busa'] = $qty['qty2'];
            $calculations['final_qty_busa'] = $qty['final_qty'];
        }

        return $calculations;
    }

    // Fungsi ini dipanggil setiap kali ada perubahan state yang relevan
    public function updateAllCalculations(): void
    {
        $currentState = $this->form->getState();
        $calculatedValues = $this->calculateAllDimensionsAndQuantities($currentState);
        $this->form->fill(array_merge($currentState, $calculatedValues));
    }

    // Fungsi untuk membersihkan field terkait jika toggle dimatikan
    private function clearComponentFields(Set $set, string $componentPrefix, array $fields): void
    {
        foreach ($fields as $field) {
            $set("{$componentPrefix}_{$field}", null); // Untuk item pilihan
            // Untuk dimensi dan kuantitas
            foreach (['_atas', '_bawah', ''] as $suffix) { // Mencakup atas, bawah, dan non-suffix (busa)
                 $set("{$field}{$suffix}", null); // e.g. board_panjang_atas
                 $set("qty1_{$field}{$suffix}", null); // e.g. qty1_board_atas
                 $set("qty2_{$field}{$suffix}", null);
                 $set("final_qty_{$field}{$suffix}", null);
                 $set("{$field}_kertas", null); // e.g. board_panjang_kertas
            }
             // Membersihkan field spesifik busa (panjang_busa, lebar_busa)
            if ($componentPrefix === 'busa') {
                $set('panjang_busa', null);
                $set('lebar_busa', null);
            }
        }
         // Khusus untuk item pilihan
        $set("selected_item_{$componentPrefix}", null);

        // Membersihkan field dimensi box komponen yang spesifik, jika ada
        // (Contoh: cover_luar_panjang_box_atas)
        $dimensionFields = [
            "{$componentPrefix}_panjang_box_atas", "{$componentPrefix}_lebar_box_atas",
            "{$componentPrefix}_panjang_box_bawah", "{$componentPrefix}_lebar_box_bawah"
        ];
        if ($componentPrefix === 'board') { // board punya nama field berbeda
             $dimensionFields = [
                "board_panjang_atas", "board_lebar_atas",
                "board_panjang_bawah", "board_lebar_bawah"
            ];
        }

        foreach($dimensionFields as $dimField) {
            $set($dimField, null);
        }
    }


    public function form(Form $form): Form
    {
        $dimensionInputSchema = function (string $name, string $label): TextInput {
            return TextInput::make($name)
                ->label($label)
                ->numeric()
                ->nullable()
                ->suffix('cm')
                ->minValue(0)
                ->step(0.1)
                ->placeholder('0.0')
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($livewire) => $livewire->updateAllCalculations())
                ->columnSpan(1);
        };

        $dimensionDisplaySchema = function (string $name, string $label, string $suffix = 'cm'): TextInput {
            return TextInput::make($name)
                ->label($label)
                ->disabled()
                ->suffix($suffix)
                ->placeholder('Auto');
        };

        $componentToggleSchema = function (string $name, string $label, string $componentPrefix, array $relatedFields) {
            return Forms\Components\Toggle::make($name)
                ->label($label)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, $livewire) use ($name, $componentPrefix, $relatedFields) {
                    if (!$get($name)) {
                        $this->clearComponentFields($set, $componentPrefix, $relatedFields);
                    }
                    $livewire->updateAllCalculations();
                });
        };
        
        $componentItemSelectSchema = function(string $name, string $label, string $categoryName, string $visibilityFlag) {
            return Forms\Components\Select::make($name)
                ->label($label)
                ->options(ProductionItem::whereHas('category', fn ($q) => $q->where('name', $categoryName))->pluck('name', 'id'))
                ->nullable()
                ->live()
                ->hidden(fn (Get $get): bool => !$get($visibilityFlag))
                ->afterStateUpdated(fn ($livewire) => $livewire->updateAllCalculations());
        };


        // Definisi Sections
        $sections = [
            Section::make('Informasi Produk')
                ->schema([
                    TextInput::make('product_name')->label('Nama Produk')->required()->live(onBlur: true)->columnSpan(1),
                    Select::make('size')->label('Ukuran Box (Master Cost)')->options(MasterCost::pluck('size', 'size')->toArray())->required()->live()->columnSpan(1),
                    Select::make('poly_dimension')->label('Dimensi Poly')->options(fn () => PolyCost::all()->pluck('dimension', 'dimension'))->nullable()->live()->columnSpan(1),
                    Select::make('include_knife_cost')->label('Termasuk Ongkos Pisau')->options(['ada' => 'Ada','tidak_ada' => 'Tidak Ada'])->required()->default('tidak_ada')->columnSpan(1),
                    TextInput::make('quantity')->label('Jumlah Pesan')->numeric()->default(1)->minValue(1)->required()->live(onBlur: true)
                        ->afterStateUpdated(fn ($livewire) => $livewire->updateAllCalculations())->columnSpan(1),
                ])->columns(5),

            Section::make('Dimensi Box Input')
                ->description("Masukkan dimensi box dalam satuan centimeter.")
                ->schema([
                    Section::make('Dimensi Box Atas')
                        ->schema([
                            $dimensionInputSchema('atas_panjang', 'Panjang Atas'),
                            $dimensionInputSchema('atas_lebar', 'Lebar Atas'),
                            $dimensionInputSchema('atas_tinggi', 'Tinggi Atas'),
                        ])->columns(3)->collapsible(),
                    Section::make('Dimensi Box Bawah')
                        ->schema([
                            $dimensionInputSchema('bawah_panjang', 'Panjang Bawah'),
                            $dimensionInputSchema('bawah_lebar', 'Lebar Bawah'),
                            $dimensionInputSchema('bawah_tinggi', 'Tinggi Bawah'),
                        ])->columns(3)->collapsible(),
                ])->columns(2)->icon('heroicon-o-cube'),

            Section::make('Pilihan Komponen Material')
                ->schema([
                    $componentToggleSchema('includeBoard', 'Sertakan Board', 'board', ['board_panjang', 'board_lebar']), // field dasar
                    $componentToggleSchema('includeCoverLuar', 'Sertakan Cover Luar', 'cover_luar', ['cover_luar_panjang_box', 'cover_luar_lebar_box']),
                    $componentToggleSchema('includeCoverDalam', 'Sertakan Cover Dalam', 'cover_dalam', ['cover_dalam_panjang_box', 'cover_dalam_lebar_box']),
                    $componentToggleSchema('includeCoverLuarLidah', 'Sertakan Cover Luar Lidah', 'cover_luar_lidah', ['cover_luar_lidah_panjang_box', 'cover_luar_lidah_lebar_box']),
                    $componentToggleSchema('includeBusa', 'Sertakan Busa', 'busa', ['panjang_busa', 'lebar_busa']),
                    
                    $componentItemSelectSchema('selected_item_board', 'Pilih Item Board', 'Board', 'includeBoard'),
                    $componentItemSelectSchema('selected_item_cover_luar', 'Pilih Item Cover Luar', 'Cover Luar', 'includeCoverLuar'),
                    $componentItemSelectSchema('selected_item_cover_dalam', 'Pilih Item Cover Dalam', 'Cover Dalam', 'includeCoverDalam'),
                    $componentItemSelectSchema('selected_item_cover_luar_lidah', 'Pilih Item Cover Luar Lidah', 'Cover Luar Lidah', 'includeCoverLuarLidah'),
                    $componentItemSelectSchema('selected_item_busa', 'Pilih Item Busa', 'Busa', 'includeBusa'),
                ])->columns(2)->collapsible()->icon('heroicon-o-adjustments-horizontal'),

            Section::make('Hasil Perhitungan Dimensi Komponen (Potongan Jadi)')
                ->schema([
                    Forms\Components\Fieldset::make('Dimensi Board')->columns(2)->hidden(fn (Get $get): bool => !$get('includeBoard'))
                        ->schema([
                            $dimensionDisplaySchema('board_panjang_atas', 'Board P Atas'), $dimensionDisplaySchema('board_lebar_atas', 'Board L Atas'),
                            $dimensionDisplaySchema('board_panjang_bawah', 'Board P Bawah'), $dimensionDisplaySchema('board_lebar_bawah', 'Board L Bawah'),
                        ]),
                    Forms\Components\Fieldset::make('Dimensi Cover Luar')->columns(2)->hidden(fn (Get $get): bool => !$get('includeCoverLuar'))
                        ->schema([
                            $dimensionDisplaySchema('cover_luar_panjang_box_atas', 'CoverLuar P Atas'), $dimensionDisplaySchema('cover_luar_lebar_box_atas', 'CoverLuar L Atas'),
                            $dimensionDisplaySchema('cover_luar_panjang_box_bawah', 'CoverLuar P Bawah'), $dimensionDisplaySchema('cover_luar_lebar_box_bawah', 'CoverLuar L Bawah'),
                        ]),
                    Forms\Components\Fieldset::make('Dimensi Cover Dalam')->columns(2)->hidden(fn (Get $get): bool => !$get('includeCoverDalam'))
                         ->schema([
                            $dimensionDisplaySchema('cover_dalam_panjang_box_atas', 'CoverDalam P Atas'), $dimensionDisplaySchema('cover_dalam_lebar_box_atas', 'CoverDalam L Atas'),
                            $dimensionDisplaySchema('cover_dalam_panjang_box_bawah', 'CoverDalam P Bawah'), $dimensionDisplaySchema('cover_dalam_lebar_box_bawah', 'CoverDalam L Bawah'),
                        ]),
                    Forms\Components\Fieldset::make('Dimensi Cover Luar Lidah')->columns(2)->hidden(fn (Get $get): bool => !$get('includeCoverLuarLidah'))
                         ->schema([
                            $dimensionDisplaySchema('cover_luar_lidah_panjang_box_atas', 'CoverLuarLidah P Atas'), $dimensionDisplaySchema('cover_luar_lidah_lebar_box_atas', 'CoverLuarLidah L Atas'),
                            $dimensionDisplaySchema('cover_luar_lidah_panjang_box_bawah', 'CoverLuarLidah P Bawah'), $dimensionDisplaySchema('cover_luar_lidah_lebar_box_bawah', 'CoverLuarLidah L Bawah'),
                        ]),
                    Forms\Components\Fieldset::make('Dimensi Busa')->columns(2)->hidden(fn (Get $get): bool => !$get('includeBusa'))
                        ->schema([
                            $dimensionDisplaySchema('panjang_busa', 'Panjang Busa'), $dimensionDisplaySchema('lebar_busa', 'Lebar Busa'),
                        ]),
                ])->columns(1)->collapsible()->icon('heroicon-o-clipboard-document-list'),

            Section::make('Hasil Perhitungan Kuantitas dari Bahan')
                ->schema([
                    Forms\Components\Fieldset::make('Kuantitas Board')->columns(2)->hidden(fn (Get $get): bool => !$get('includeBoard'))
                        ->schema([
                            $dimensionDisplaySchema('board_panjang_kertas', 'P Kertas Board'), $dimensionDisplaySchema('board_lebar_kertas', 'L Kertas Board'),
                            $dimensionDisplaySchema('qty1_board_atas', 'Qty1 Board Atas', 'pcs'), $dimensionDisplaySchema('qty2_board_atas', 'Qty2 Board Atas', 'pcs'),
                            $dimensionDisplaySchema('final_qty_board_atas', 'Final Qty Board Atas', 'pcs'),
                            $dimensionDisplaySchema('qty1_board_bawah', 'Qty1 Board Bawah', 'pcs'), $dimensionDisplaySchema('qty2_board_bawah', 'Qty2 Board Bawah', 'pcs'),
                            $dimensionDisplaySchema('final_qty_board_bawah', 'Final Qty Board Bawah', 'pcs'),
                        ]),
                    Forms\Components\Fieldset::make('Kuantitas Cover Luar')->columns(2)->hidden(fn (Get $get): bool => !$get('includeCoverLuar'))
                        ->schema([
                            $dimensionDisplaySchema('cover_luar_panjang_kertas', 'P Kertas CoverLuar'), $dimensionDisplaySchema('cover_luar_lebar_kertas', 'L Kertas CoverLuar'),
                            $dimensionDisplaySchema('qty1_cover_luar_atas', 'Qty1 CL Atas', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_atas', 'Qty2 CL Atas', 'pcs'),
                            $dimensionDisplaySchema('final_qty_cover_luar_atas', 'Final Qty CL Atas', 'pcs'),
                            $dimensionDisplaySchema('qty1_cover_luar_bawah', 'Qty1 CL Bawah', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_bawah', 'Qty2 CL Bawah', 'pcs'),
                            $dimensionDisplaySchema('final_qty_cover_luar_bawah', 'Final Qty CL Bawah', 'pcs'),
                        ]),
                     Forms\Components\Fieldset::make('Kuantitas Cover Dalam')->columns(2)->hidden(fn (Get $get): bool => !$get('includeCoverDalam'))
                        ->schema([
                            $dimensionDisplaySchema('cover_dalam_panjang_kertas', 'P Kertas CoverDalam'), $dimensionDisplaySchema('cover_dalam_lebar_kertas', 'L Kertas CoverDalam'),
                            $dimensionDisplaySchema('qty1_cover_dalam_atas', 'Qty1 CD Atas', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_atas', 'Qty2 CD Atas', 'pcs'),
                            $dimensionDisplaySchema('final_qty_cover_dalam_atas', 'Final Qty CD Atas', 'pcs'),
                            $dimensionDisplaySchema('qty1_cover_dalam_bawah', 'Qty1 CD Bawah', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_bawah', 'Qty2 CD Bawah', 'pcs'),
                            $dimensionDisplaySchema('final_qty_cover_dalam_bawah', 'Final Qty CD Bawah', 'pcs'),
                        ]),
                    Forms\Components\Fieldset::make('Kuantitas Cover Luar Lidah')->columns(2)->hidden(fn (Get $get): bool => !$get('includeCoverLuarLidah'))
                        ->schema([
                            $dimensionDisplaySchema('cover_luar_lidah_panjang_kertas', 'P Kertas CoverLuarLidah'), $dimensionDisplaySchema('cover_luar_lidah_lebar_kertas', 'L Kertas CoverLuarLidah'),
                            $dimensionDisplaySchema('qty1_cover_luar_lidah_atas', 'Qty1 CLL Atas', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_lidah_atas', 'Qty2 CLL Atas', 'pcs'),
                            $dimensionDisplaySchema('final_qty_cover_luar_lidah_atas', 'Final Qty CLL Atas', 'pcs'),
                            $dimensionDisplaySchema('qty1_cover_luar_lidah_bawah', 'Qty1 CLL Bawah', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_lidah_bawah', 'Qty2 CLL Bawah', 'pcs'),
                            $dimensionDisplaySchema('final_qty_cover_luar_lidah_bawah', 'Final Qty CLL Bawah', 'pcs'),
                        ]),
                    Forms\Components\Fieldset::make('Kuantitas Busa')->columns(2)->hidden(fn (Get $get): bool => !$get('includeBusa'))
                        ->schema([
                            $dimensionDisplaySchema('busa_panjang_kertas', 'P Material Busa'), $dimensionDisplaySchema('busa_lebar_kertas', 'L Material Busa'),
                            $dimensionDisplaySchema('qty1_busa', 'Qty1 Busa', 'pcs'), $dimensionDisplaySchema('qty2_busa', 'Qty2 Busa', 'pcs'),
                            $dimensionDisplaySchema('final_qty_busa', 'Final Qty Busa', 'pcs'),
                        ]),
                ])->columns(1)->collapsible()->icon('heroicon-o-view-columns'),
        ];

        return $form->schema($sections)->statePath('data');
    }

    // --- Tombol Aksi ---
    // Anda perlu menambahkan bagian ini di file blade view Anda, misalnya:
    // <div class="flex justify-end space-x-2">
    //     <x-filament::button wire:click="calculateFinalPrice">Hitung Harga Total</x-filament::button>
    //     <x-filament::button wire:click="saveFullCalculation" color="primary">Simpan Kalkulasi</x-filament::button>
    // </div>
    // Dan jika ada $calculationResult: {{ $calculationResult }}

    public function calculateFinalPrice(): void
    {
        $data = $this->form->getState();
        $calculatedDimensions = $this->calculateAllDimensionsAndQuantities($data); // Dapatkan semua hasil hitungan
        $allData = array_merge($data, $calculatedDimensions); // Gabungkan input & hasil hitungan

        // --- MULAI LOGIKA PERHITUNGAN HARGA TOTAL ---
        // Ini adalah contoh yang sangat SANGAT disederhanakan.
        // Anda HARUS membangun logika ini sesuai dengan cara Anda menghitung harga.
        $totalCost = 0;
        $quantity = (int)($allData['quantity'] ?? 1);

        // 1. Biaya Material (Board, Cover, Busa, dll.)
        // Contoh untuk Board Atas
        if ($allData['includeBoard'] ?? false) {
            $itemBoard = ProductionItem::find($allData['selected_item_board'] ?? null);
            if ($itemBoard && ($allData['final_qty_board_atas'] ?? 0) > 0) {
                $pricePerSheet = $this->getItemPriceByQuantity($itemBoard, $quantity); // Harga per lembar bahan board
                $sheetsNeededForAtas = ceil($quantity / $allData['final_qty_board_atas']);
                $totalCost += $sheetsNeededForAtas * $pricePerSheet;
            }
            if ($itemBoard && ($allData['final_qty_board_bawah'] ?? 0) > 0) {
                 $pricePerSheet = $this->getItemPriceByQuantity($itemBoard, $quantity);
                 $sheetsNeededForBawah = ceil($quantity / $allData['final_qty_board_bawah']);
                 $totalCost += $sheetsNeededForBawah * $pricePerSheet; // Ini mungkin perlu disatukan dengan atas jika bahan sama
            }
            // Tambahkan logika serupa untuk Cover Luar, Cover Dalam, Lidah, Busa
            // PERHATIKAN: Jika item sama (misal board atas & bawah pakai item yg sama), optimalkan pengambilan harga.
        }
        
        // 2. Biaya Master (berdasarkan ukuran)
        $masterCostData = MasterCost::where('size', $allData['size'] ?? '')->first();
        if ($masterCostData) {
            // Asumsi master_cost adalah per pcs produk jadi
            $totalCost += (float)($masterCostData->cost_per_unit ?? 0) * $quantity; 
        }

        // 3. Biaya Poly (jika ada)
        if ($allData['poly_dimension'] ?? false) {
            $polyCostData = PolyCost::where('dimension', $allData['poly_dimension'])->first();
            if ($polyCostData) {
                // Asumsi poly_cost adalah per pcs produk jadi atau per batch
                $totalCost += (float)($polyCostData->cost ?? 0) * $quantity; // Atau logika lain
            }
        }

        // 4. Ongkos Pisau (jika ada)
        if (($allData['include_knife_cost'] ?? 'tidak_ada') === 'ada') {
            // Tambahkan logika biaya pisau. Bisa jadi fixed, atau tergantung kompleksitas.
            // $totalCost += ONGKOS_PISAU_ANDA;
        }

        // 5. Biaya Lain-lain, Overhead, Profit Margin, dll.
        // $totalCost = $totalCost * FAKTOR_OVERHEAD_PROFIT;

        // --- AKHIR LOGIKA PERHITUNGAN HARGA TOTAL ---

        $this->calculationResult = "Rp " . number_format($totalCost, 2, ',', '.'); // Simpan hasil untuk ditampilkan

        Notification::make()
            ->title('Harga Total Dihitung (Contoh)')
            ->body($this->calculationResult . ' - Pastikan logika harga sudah sesuai!')
            ->success()
            ->send();
    }

    public function saveFullCalculation(): void
    {
        $dataToSave = $this->form->getState();
        // Tambahkan juga hasil kalkulasi dimensi & kuantitas jika perlu disimpan
        $calculatedDimensions = $this->calculateAllDimensionsAndQuantities($dataToSave);
        $allDataToSave = array_merge($dataToSave, $calculatedDimensions);
        $allDataToSave['total_price_estimate'] = $this->calculationResult; // Simpan juga estimasi harga

        // Logika penyimpanan ke database, misal ke model PriceCalculation
        // PriceCalculation::create($allDataToSave);

        Notification::make()
            ->title('Kalkulasi Disimpan (Implementasi Database Diperlukan)')
            ->success()
            ->send();
    }
}