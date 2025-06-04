<?php

namespace App\Filament\Pages;

use App\Models\ProductionCategory;
use App\Models\ProductionItem;
use App\Models\PriceCalculation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Illuminate\Support\Arr;
use App\Models\MasterCost;
use App\Models\PolyCost;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Group;
use Illuminate\Support\Str;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;

class ProductionCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Kalkulator Harga';
    protected static string $view = 'filament.pages.production-calculator';

    public ?array $data = [];

    public bool $includeBoard = false;
    public bool $includeCoverLuar = false;
    public bool $includeCoverDalam = false;
    public bool $includeBusa = false;

    public $calculationResult = null;

    private function getFloatVal($value): float
    {
        return is_numeric($value) ? (float)$value : 0;
    }

    public function mount(): void
    {
        $this->form->fill([
            'quantity' => 1,
            'include_knife_cost' => 'tidak_ada',
            'includeBoard' => $this->includeBoard,
            'includeCoverLuar' => $this->includeCoverLuar,
            'includeCoverDalam' => $this->includeCoverDalam,
            'includeBusa' => $this->includeBusa,
            'box_type_selection' => 'TAB', // Default
        ]);
    }

    protected function getItemPriceByQuantity(?ProductionItem $item, int $quantity): float
    {
        if (!$item) { return 0; }
        return (float)($item->price ?? 0);
    }

    // --- START: Fungsi Formula ---

    // BASE BOARD (digunakan oleh banyak jenis box)
    protected function calculateBaseBoardPanjang($panjangBox, $tinggiBox): float {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($panjangBox) + 3;
    }
    protected function calculateBaseBoardLebar($lebarBox, $tinggiBox): float {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3;
    }

    // COVER LUAR (Untuk TAB, BUSA, Double Wall Treasury)
    protected function calculateBaseCoverLuarPanjang($panjangBox, $tinggiBox): float { // Umumnya P + 2T + 3 + 4
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($panjangBox) + 3 + 4;
    }
    // Untuk Cover Luar Lebar Box ATAS (TAB, BUSA, DWT) -> Rumus: L + 2T + 3
    protected function calculateCoverLuarLebarAtasStyle($lebarBox, $tinggiBox): float {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3;
    }
    // Untuk Cover Luar Lebar Box BAWAH (TAB, BUSA, DWT) -> Rumus: L + 2T + 3 + 4
    protected function calculateCoverLuarLebarBawahStyle($lebarBox, $tinggiBox): float {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3 + 4;
    }

    // BASE COVER DALAM
    protected function calculateBaseCoverDalamPanjang($panjangBox, $tinggiBox): float {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($panjangBox) + 3;
    }
    protected function calculateBaseCoverDalamLebar($lebarBox, $tinggiBox): float {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3;
    }

    // BASE BUSA
    protected function calculateBaseBusaPanjang($panjangBoxBawah): float {
        return $this->getFloatVal($panjangBoxBawah) + 3;
    }
    protected function calculateBaseBusaLebar($lebarBoxBawah): float {
        return $this->getFloatVal($lebarBoxBawah) + 3;
    }

    // JENDELA - BOARD KUPING
    protected function calculateJendelaBoardPanjangKuping($lebarBoxAtas, $tinggiBoxAtas): float { // Menggunakan LEBAR atas
        return (2 * $this->getFloatVal($tinggiBoxAtas)) + $this->getFloatVal($lebarBoxAtas) + 3;
    }
    protected function calculateJendelaBoardLebarKuping($panjangBoxAtas, $tinggiBoxAtas): float { // Menggunakan PANJANG atas
        return $this->getFloatVal($panjangBoxAtas) + $this->getFloatVal($tinggiBoxAtas) + 3;
    }

    // JENDELA - COVER LUAR
    protected function calculateJendelaCoverLuarPanjangBoxBawah($panjangBoxBawah, $lebarBoxBawah, $panjangKertasCoverLuar): float {
        $val = (2 * $this->getFloatVal($panjangBoxBawah)) + (2 * $this->getFloatVal($lebarBoxBawah)) + 3 + 2;
        if ($this->getFloatVal($panjangKertasCoverLuar) == 0) return $val;
        return $val > $this->getFloatVal($panjangKertasCoverLuar) ? $val / 2 : ($val - $this->getFloatVal($panjangBoxBawah));
    }
    protected function calculateJendelaCoverLuarLebarBoxBawah($tinggiBoxBawah): float { return $this->getFloatVal($tinggiBoxBawah) + 5 + 3; }
    protected function calculateJendelaCoverLuarPanjangKuping($lebarBoxAtas, $tinggiBoxAtas): float { return (2 * $this->getFloatVal($tinggiBoxAtas)) + $this->getFloatVal($lebarBoxAtas) + 3 + 4; }
    protected function calculateJendelaCoverLuarLebarKuping($panjangBoxAtas, $tinggiBoxAtas): float { return $this->getFloatVal($panjangBoxAtas) + $this->getFloatVal($tinggiBoxAtas) + 3 + 4; }

    // JENDELA - COVER DALAM KUPING (ATAS)
    protected function calculateJendelaCoverDalamPanjangAtas($lebarBoxAtas, $tinggiBoxAtas): float { return (2 * $this->getFloatVal($tinggiBoxAtas)) + $this->getFloatVal($lebarBoxAtas) + 3 + 4; }
    protected function calculateJendelaCoverDalamLebarAtas($panjangBoxAtas, $tinggiBoxAtas): float { return $this->getFloatVal($tinggiBoxAtas) + $this->getFloatVal($panjangBoxAtas) + 3 + 4; }

    // --- FUNGSI HELPER BARU/SPESIFIK UNTUK BUKU MAGNET (Board Box Bawah) ---
    // This function was causing the incorrect calculation for 'BUKU MAGNET' panjang_board_bawah.
    // It is no longer used for 'panjang_board_bawah' in BUKU MAGNET to ensure the correct formula is applied.
    protected function calculateBukuMagnetBoardPanjangBoxBawah($panjangBoxBawah, $lebarBoxBawah): float {
        return (2 * $this->getFloatVal($lebarBoxBawah)) + $this->getFloatVal($panjangBoxBawah) + 3;
    }
    protected function calculateBukuMagnetBoardLebarBoxBawah($lebarBoxBawah, $tinggiBoxBawah): float {
        return (2 * $this->getFloatVal($tinggiBoxBawah)) + $this->getFloatVal($lebarBoxBawah) + 2;
    }

    // --- FUNGSI HELPER UNTUK BUKU MAGNET (Board Lidah - RUMUS BARU) ---
    protected function calculateBukuMagnet_NEW_BoardPanjangLidah($panjangBoxBawah, $tinggiBoxBawah, $panjangKertasBoard = 66): float {
        $val = (2 * $this->getFloatVal($panjangBoxBawah)) + ($this->getFloatVal($tinggiBoxBawah) * 2) + (0.5 * 3) + 3;
        if ($this->getFloatVal($panjangKertasBoard) == 0) return $val;
        return $val > $this->getFloatVal($panjangKertasBoard) ? $val / 2 : $val;
    }
    protected function calculateBukuMagnet_NEW_BoardLebarLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertasBoard = 77): float {
        $val = (2 * $this->getFloatVal($lebarBoxBawah)) + ($this->getFloatVal($tinggiBoxBawah) * 2) + (0.5 * 3) + 3;
        if ($this->getFloatVal($lebarKertasBoard) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertasBoard) ? $val / 2 : $val;
    }

    // --- FUNGSI HELPER UNTUK BUKU PITA (Board Lidah - RUMUS ORIGINAL/LAMA) ---
    protected function calculateBukuPita_OLD_BoardPanjangLidah($panjangBoxBawah): float {
        return $this->getFloatVal($panjangBoxBawah) + 3;
    }
    protected function calculateBukuPita_OLD_BoardLebarLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertasBoard = 77): float {
        $val = (2 * $this->getFloatVal($lebarBoxBawah)) + $this->getFloatVal($tinggiBoxBawah) + (0.5 * 2) + 3;
        if ($this->getFloatVal($lebarKertasBoard) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertasBoard) ? $val / 2 : $val;
    }

    // BUKU PITA - COVER LUAR LIDAH
    protected function calculateBukuPitaCoverLuarPanjangLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertasCoverLuar): float {
        $val = (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3 + 2;
        if ($this->getFloatVal($lebarKertasCoverLuar) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertasCoverLuar) ? $val / 2 : ($val - $this->getFloatVal($lebarBoxBawah));
    }
    protected function calculateBukuPitaCoverLuarLebarLidah($panjangBoxBawah): float { return $this->getFloatVal($panjangBoxBawah) + 5 + 3; }

    // BUKU MAGNET - COVER LUAR LIDAH
    protected function calculateBukuMagnetCoverLuarPanjangLidah($panjangBoxBawah, $tinggiBoxBawah, $panjangKertas): float {
        // Updated formula based on user's input: (2 * LEBAR BOX BAWAH) + (TINGGI BOX BAWAH * 2) + (0.5 * 3) + 5 + 3
        $val = (2 * $this->getFloatVal($panjangBoxBawah)) + ($this->getFloatVal($tinggiBoxBawah) * 2) + (0.5 * 3) + 5 + 3;
        if ($this->getFloatVal($panjangKertas) == 0) return $val;
        return $val > $this->getFloatVal($panjangKertas) ? $val / 2 : $val;
    }
    protected function calculateBukuMagnetCoverLuarLebarLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertas): float {
        // Updated formula based on user's input: PANJANG BOX BAWAH + 5 + 3
        $val = $this->getFloatVal($lebarBoxBawah) + 5 + 3;
        if ($this->getFloatVal($lebarKertas) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertas) ? $val / 2 : $val;
    }

    // BUKU PITA & MAGNET - COVER DALAM LIDAH
    protected function calculateBukuPitaCoverDalamLebarLidah($lebarBoxBawah, $tinggiBoxBawah): float { return $this->getFloatVal($lebarBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 3; }
    protected function calculateBukuMagnetCoverDalamLebarLidah($lebarBoxBawah, $tinggiBoxBawah): float { return $this->getFloatVal($lebarBoxBawah) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3; }

    // SELONGSONG - BOARD
    protected function calculateSelongsongBoardPanjangSelongsong($lebarBoxBawah, $tinggiBoxBawah): float { return (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3; }
    protected function calculateSelongsongBoardLebarSelongsong($panjangBoxBawah, $tinggiBoxBawah): float { return $this->getFloatVal($panjangBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 3; }

    // SELONGSONG - COVER LUAR
    protected function calculateSelongsongCoverLuarPanjangSelongsong($lebarBoxBawah, $tinggiBoxBawah): float { return (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 2 + 4; }
    protected function calculateSelongsongCoverLuarLebarSelongsong($panjangBoxBawah, $tinggiBoxBawah): float { return $this->getFloatVal($panjangBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 4 + 3; }

    // SELONGSONG - COVER DALAM
    protected function calculateSelongsongCoverDalamPanjangSelongsong($lebarBoxBawah, $tinggiBoxBawah): float { return (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3; }
    protected function calculateSelongsongCoverDalamLebarSelongsong($panjangBoxBawah, $tinggiBoxBawah): float { return $this->getFloatVal($panjangBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 3; }
    // --- END: Fungsi Formula ---

    protected function initializeCalculatedFields(): array
    {
        $fields = [
            'panjang_board_atas', 'lebar_board_atas', 'qty1_board_atas', 'qty2_board_atas', 'final_qty_board_atas',
            'panjang_board_bawah', 'lebar_board_bawah', 'qty1_board_bawah', 'qty2_board_bawah', 'final_qty_board_bawah',
            'panjang_board_kuping', 'lebar_board_kuping', 'qty1_board_kuping', 'qty2_board_kuping', 'final_qty_board_kuping',
            'panjang_board_lidah', 'lebar_board_lidah', 'qty1_board_lidah', 'qty2_board_lidah', 'final_qty_board_lidah',
            'panjang_board_selongsong', 'lebar_board_selongsong', 'qty1_board_selongsong', 'qty2_board_selongsong', 'final_qty_board_selongsong',
            'board_panjang_kertas', 'board_lebar_kertas',

            'panjang_cover_luar_atas', 'lebar_cover_luar_atas', 'qty1_cover_luar_atas', 'qty2_cover_luar_atas', 'final_qty_cover_luar_atas',
            'panjang_cover_luar_bawah', 'lebar_cover_luar_bawah', 'qty1_cover_luar_bawah', 'qty2_cover_luar_bawah', 'final_qty_cover_luar_bawah',
            'panjang_cover_luar_kuping', 'lebar_cover_luar_kuping', 'qty1_cover_luar_kuping', 'qty2_cover_luar_kuping', 'final_qty_cover_luar_kuping',
            'panjang_cover_luar_lidah', 'lebar_cover_luar_lidah', 'qty1_cover_luar_lidah', 'qty2_cover_luar_lidah', 'final_qty_cover_luar_lidah',
            'panjang_cover_luar_selongsong', 'lebar_cover_luar_selongsong', 'qty1_cover_luar_selongsong', 'qty2_cover_luar_selongsong', 'final_qty_cover_luar_selongsong',
            'cover_luar_panjang_kertas', 'cover_luar_lebar_kertas',

            'panjang_cover_dalam_atas', 'lebar_cover_dalam_atas', 'qty1_cover_dalam_atas', 'qty2_cover_dalam_atas', 'final_qty_cover_dalam_atas',
            'panjang_cover_dalam_bawah', 'lebar_cover_dalam_bawah', 'qty1_cover_dalam_bawah', 'qty2_cover_dalam_bawah', 'final_qty_cover_dalam_bawah',
            'panjang_cover_dalam_lidah', 'lebar_cover_dalam_lidah', 'qty1_cover_dalam_lidah', 'qty2_cover_dalam_lidah', 'final_qty_cover_dalam_lidah',
            'panjang_cover_dalam_selongsong', 'lebar_cover_dalam_selongsong', 'qty1_cover_dalam_selongsong', 'qty2_cover_dalam_selongsong', 'final_qty_cover_dalam_selongsong',
            'cover_dalam_panjang_kertas', 'cover_dalam_lebar_kertas',

            'panjang_busa', 'lebar_busa', 'qty1_busa', 'qty2_busa', 'final_qty_busa',
            'busa_panjang_kertas', 'busa_lebar_kertas',

            'unit_price_board_atas', 'unit_price_board_bawah', 'unit_price_board_kuping', 'unit_price_board_lidah', 'unit_price_board_selongsong',
            'unit_price_cover_luar_atas', 'unit_price_cover_luar_bawah', 'unit_price_cover_luar_kuping', 'unit_price_cover_luar_lidah', 'unit_price_cover_luar_selongsong',
            'unit_price_cover_dalam_atas', 'unit_price_cover_dalam_bawah', 'unit_price_cover_dalam_lidah', 'unit_price_cover_dalam_selongsong',
            'unit_price_busa',
        ];
        $initialized = [];
        foreach ($fields as $field) { $initialized[$field] = 0; }
        return $initialized;
    }

    protected function calculateAllDimensionsAndQuantities(array $data): array
    {
        $calculations = $this->initializeCalculatedFields();
        $this->includeBoard = (bool)($data['includeBoard'] ?? false);
        $this->includeCoverLuar = (bool)($data['includeCoverLuar'] ?? false);
        $this->includeCoverDalam = (bool)($data['includeCoverDalam'] ?? false);
        $this->includeBusa = (bool)($data['includeBusa'] ?? false);
        $boxType = $data['box_type_selection'] ?? null;

        $atasPanjang = $this->getFloatVal($data['atas_panjang'] ?? 0);
        $atasLebar = $this->getFloatVal($data['atas_lebar'] ?? 0);
        $atasTinggi = $this->getFloatVal($data['atas_tinggi'] ?? 0);
        $bawahPanjang = $this->getFloatVal($data['bawah_panjang'] ?? 0);
        $bawahLebar = $this->getFloatVal($data['bawah_lebar'] ?? 0);
        $bawahTinggi = $this->getFloatVal($data['bawah_tinggi'] ?? 0);

        $itemBoard = !empty($data['selected_item_board']) ? ProductionItem::find($data['selected_item_board']) : null;
        $itemCoverLuar = !empty($data['selected_item_cover_luar']) ? ProductionItem::find($data['selected_item_cover_luar']) : null;
        $itemCoverDalam = !empty($data['selected_item_cover_dalam']) ? ProductionItem::find($data['selected_item_cover_dalam']) : null;
        $itemBusa = !empty($data['selected_item_busa']) ? ProductionItem::find($data['selected_item_busa']) : null;

        $pkBoard = $itemBoard ? $this->getFloatVal($itemBoard->panjang_kertas) : 0;
        $lkBoard = $itemBoard ? $this->getFloatVal($itemBoard->lebar_kertas) : 0;
        $pkCoverLuar = $itemCoverLuar ? $this->getFloatVal($itemCoverLuar->panjang_kertas) : 0;
        $lkCoverLuar = $itemCoverLuar ? $this->getFloatVal($itemCoverLuar->lebar_kertas) : 0;
        $pkCoverDalam = $itemCoverDalam ? $this->getFloatVal($itemCoverDalam->panjang_kertas) : 0;
        $lkCoverDalam = $itemCoverDalam ? $this->getFloatVal($itemCoverDalam->lebar_kertas) : 0;
        $pkBusa = $itemBusa ? $this->getFloatVal($itemBusa->panjang_kertas) : 0;
        $lkBusa = $itemBusa ? $this->getFloatVal($itemBusa->lebar_kertas) : 0;

        if ($this->includeBoard && $itemBoard) { $calculations['board_panjang_kertas'] = $pkBoard; $calculations['board_lebar_kertas'] = $lkBoard; }
        if ($this->includeCoverLuar && $itemCoverLuar) { $calculations['cover_luar_panjang_kertas'] = $pkCoverLuar; $calculations['cover_luar_lebar_kertas'] = $lkCoverLuar; }
        if ($this->includeCoverDalam && $itemCoverDalam) { $calculations['cover_dalam_panjang_kertas'] = $pkCoverDalam; $calculations['cover_dalam_lebar_kertas'] = $lkCoverDalam; }
        if ($this->includeBusa && $itemBusa) { $calculations['busa_panjang_kertas'] = $pkBusa; $calculations['busa_lebar_kertas'] = $lkBusa;}

        switch ($boxType) {
            case 'TAB':
            case 'BUSA':
            case 'Double WallTreasury':
                if ($this->includeBoard && $itemBoard) {
                    $calculations['panjang_board_atas'] = $this->calculateBaseBoardPanjang($atasPanjang, $atasTinggi);
                    $calculations['lebar_board_atas'] = $this->calculateBaseBoardLebar($atasLebar, $atasTinggi);
                    $qtyAtas = $this->calculateSheetQuantities($calculations['panjang_board_atas'], $calculations['lebar_board_atas'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_atas'] = $qtyAtas['qty1']; $calculations['qty2_board_atas'] = $qtyAtas['qty2']; $calculations['final_qty_board_atas'] = $qtyAtas['final_qty'];
                    
                    $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);
                    $qtyBawah = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qtyBawah['qty1']; $calculations['qty2_board_bawah'] = $qtyBawah['qty2']; $calculations['final_qty_board_bawah'] = $qtyBawah['final_qty'];
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    $calculations['panjang_cover_luar_atas'] = $this->calculateBaseCoverLuarPanjang($atasPanjang, $atasTinggi);
                    // Updated formula for Double Wall Treasury Cover Luar Lebar Atas
                    if ($boxType === 'Double WallTreasury') {
                        $calculations['lebar_cover_luar_atas'] = (2 * $this->getFloatVal($atasTinggi)) + $this->getFloatVal($atasPanjang) + 3;
                    } else {
                        $calculations['lebar_cover_luar_atas'] = $this->calculateCoverLuarLebarAtasStyle($atasLebar, $atasTinggi); // Rumus: L + 2T + 3
                    }
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_atas'], $calculations['lebar_cover_luar_atas'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_atas'] = $qty['qty1']; $calculations['qty2_cover_luar_atas'] = $qty['qty2']; $calculations['final_qty_cover_luar_atas'] = $qty['final_qty'];
                    
                    $calculations['panjang_cover_luar_bawah'] = $this->calculateBaseCoverLuarPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_luar_bawah'] = $this->calculateCoverLuarLebarBawahStyle($bawahLebar, $bawahTinggi); // Rumus: L + 2T + 3 + 4
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qty['qty1']; $calculations['qty2_cover_luar_bawah'] = $qty['qty2']; $calculations['final_qty_cover_luar_bawah'] = $qty['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_atas'] = $this->calculateBaseCoverDalamPanjang($atasPanjang, $atasTinggi);
                    $calculations['lebar_cover_dalam_atas'] = $this->calculateBaseCoverDalamLebar($atasLebar, $atasTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_atas'], $calculations['lebar_cover_dalam_atas'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_atas'] = $qty['qty1']; $calculations['qty2_cover_dalam_atas'] = $qty['qty2']; $calculations['final_qty_cover_dalam_atas'] = $qty['final_qty'];

                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1']; $calculations['qty2_cover_dalam_bawah'] = $qty['qty2']; $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1']; $calculations['qty2_busa'] = $qty['qty2']; $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;

            case 'JENDELA':
                if ($this->includeBoard && $itemBoard) {
                    $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qty['qty1']; $calculations['qty2_board_bawah'] = $qty['qty2']; $calculations['final_qty_board_bawah'] = $qty['final_qty'];
                    
                    $calculations['panjang_board_kuping'] = $this->calculateJendelaBoardPanjangKuping($atasLebar, $atasTinggi); // Menggunakan $atasLebar
                    $calculations['lebar_board_kuping'] = $this->calculateJendelaBoardLebarKuping($atasPanjang, $atasTinggi);    
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_kuping'], $calculations['lebar_board_kuping'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_kuping'] = $qty['qty1']; $calculations['qty2_board_kuping'] = $qty['qty2']; $calculations['final_qty_board_kuping'] = $qty['final_qty'];
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    $calculations['panjang_cover_luar_bawah'] = $this->calculateJendelaCoverLuarPanjangBoxBawah($bawahPanjang, $bawahLebar, $pkCoverLuar);
                    $calculations['lebar_cover_luar_bawah'] = $this->calculateJendelaCoverLuarLebarBoxBawah($bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qty['qty1']; $calculations['qty2_cover_luar_bawah'] = $qty['qty2']; $calculations['final_qty_cover_luar_bawah'] = $qty['final_qty'];
                    
                    $calculations['panjang_cover_luar_kuping'] = $this->calculateJendelaCoverLuarPanjangKuping($atasLebar, $atasTinggi); 
                    $calculations['lebar_cover_luar_kuping'] = $this->calculateJendelaCoverLuarLebarKuping($atasPanjang, $atasTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_kuping'], $calculations['lebar_cover_luar_kuping'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_kuping'] = $qty['qty1']; $calculations['qty2_cover_luar_kuping'] = $qty['qty2']; $calculations['final_qty_cover_luar_kuping'] = $qty['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1']; $calculations['qty2_cover_dalam_bawah'] = $qty['qty2']; $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];
                    
                    $calculations['panjang_cover_dalam_atas'] = $this->calculateJendelaCoverDalamPanjangAtas($atasLebar, $atasTinggi); 
                    $calculations['lebar_cover_dalam_atas'] = $this->calculateJendelaCoverDalamLebarAtas($atasPanjang, $atasTinggi);    
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_atas'], $calculations['lebar_cover_dalam_atas'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_atas'] = $qty['qty1']; $calculations['qty2_cover_dalam_atas'] = $qty['qty2']; $calculations['final_qty_cover_dalam_atas'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1']; $calculations['qty2_busa'] = $qty['qty2']; $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;

            case 'BUKU PITA':
            case 'BUKU MAGNET':
                if ($this->includeBoard && $itemBoard) {
                    if ($boxType === 'BUKU MAGNET') {
                        // --- KHUSUS UNTUK BUKU MAGNET ---
                        // Board Box Bawah - CHANGED to use calculateBaseBoardPanjang for correct formula
                        $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                        $calculations['lebar_board_bawah'] = $this->calculateBukuMagnetBoardLebarBoxBawah($bawahLebar, $bawahTinggi);
                        
                        // Board Lidah (menggunakan fungsi yang telah diubah/baru untuk BUKU MAGNET)
                        $calculations['panjang_board_lidah'] = $this->calculateBukuMagnet_NEW_BoardPanjangLidah($bawahPanjang, $bawahTinggi, $pkBoard);
                        $calculations['lebar_board_lidah'] = $this->calculateBukuMagnet_NEW_BoardLebarLidah($bawahLebar, $bawahTinggi, $lkBoard);

                    } else { // Ini berarti $boxType === 'BUKU PITA'
                        // --- UNTUK BUKU PITA (menggunakan rumus standar/original) ---
                        // Board Box Bawah (menggunakan fungsi base standar)
                        $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                        $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);

                        // Board Lidah (menggunakan fungsi original/lama untuk BUKU PITA)
                        $calculations['panjang_board_lidah'] = $this->calculateBukuPita_OLD_BoardPanjangLidah($bawahPanjang);
                        $calculations['lebar_board_lidah'] = $this->calculateBukuPita_OLD_BoardLebarLidah($bawahLebar, $bawahTinggi, $lkBoard);
                    }

                    // Kalkulasi kuantitas (qty) untuk board bawah - berlaku untuk keduanya
                    $qtyBawah = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qtyBawah['qty1']; 
                    $calculations['qty2_board_bawah'] = $qtyBawah['qty2']; 
                    $calculations['final_qty_board_bawah'] = $qtyBawah['final_qty'];
                    
                    // Kalkulasi kuantitas (qty) untuk board lidah - berlaku untuk keduanya (jika ada dimensi lidah)
                    if (($calculations['panjang_board_lidah'] ?? 0) > 0 && ($calculations['lebar_board_lidah'] ?? 0) > 0) {
                        $qtyLidah = $this->calculateSheetQuantities($calculations['panjang_board_lidah'], $calculations['lebar_board_lidah'], $pkBoard, $lkBoard);
                        $calculations['qty1_board_lidah'] = $qtyLidah['qty1']; 
                        $calculations['qty2_board_lidah'] = $qtyLidah['qty2']; 
                        $calculations['final_qty_board_lidah'] = $qtyLidah['final_qty'];
                    } else {
                        $calculations['panjang_board_lidah'] = 0;
                        $calculations['lebar_board_lidah'] = 0;   
                        $calculations['qty1_board_lidah'] = 0; 
                        $calculations['qty2_board_lidah'] = 0; 
                        $calculations['final_qty_board_lidah'] = 0;
                    }
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    if($boxType === 'BUKU PITA'){
                        $calculations['panjang_cover_luar_bawah'] = $this->calculateJendelaCoverLuarPanjangBoxBawah($bawahPanjang, $bawahLebar, $pkCoverLuar);
                        $calculations['lebar_cover_luar_bawah'] = $this->calculateJendelaCoverLuarLebarBoxBawah($bawahTinggi);
                        $calculations['panjang_cover_luar_lidah'] = $this->calculateBukuPitaCoverLuarPanjangLidah($bawahLebar, $bawahTinggi, $lkCoverLuar);
                        $calculations['lebar_cover_luar_lidah'] = $this->calculateBukuPitaCoverLuarLebarLidah($bawahPanjang);
                    } else { // BUKU MAGNET
                        // The user's formula for Cover luar Panjang box bawah is already implemented in calculateJendelaCoverLuarPanjangBoxBawah
                        // So, we will use that for BUKU MAGNET as well.
                        $calculations['panjang_cover_luar_bawah'] = $this->calculateJendelaCoverLuarPanjangBoxBawah($bawahPanjang, $bawahLebar, $pkCoverLuar);
                        // The user's formula for Cover luar lebar box bawah is already implemented in calculateJendelaCoverLuarLebarBoxBawah
                        // So, we will use that for BUKU MAGNET as well.
                        $calculations['lebar_cover_luar_bawah'] = $this->calculateJendelaCoverLuarLebarBoxBawah($bawahTinggi);
                        
                        // New formula for Cover luar panjang lidah for BUKU MAGNET
                        $calculations['panjang_cover_luar_lidah'] = (2 * $this->getFloatVal($bawahLebar)) + (2 * $this->getFloatVal($bawahTinggi)) + (0.5 * 3) + 5 + 3;
                        // New formula for Cover luar lebar lidah for BUKU MAGNET
                        $calculations['lebar_cover_luar_lidah'] = $this->getFloatVal($bawahPanjang) + 5 + 3;
                    }
                    $qtyBawah = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qtyBawah['qty1']; $calculations['qty2_cover_luar_bawah'] = $qtyBawah['qty2']; $calculations['final_qty_cover_luar_bawah'] = $qtyBawah['final_qty'];
                    
                    $qtyLidah = $this->calculateSheetQuantities($calculations['panjang_cover_luar_lidah'], $calculations['lebar_cover_luar_lidah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_lidah'] = $qtyLidah['qty1']; $calculations['qty2_cover_luar_lidah'] = $qtyLidah['qty2']; $calculations['final_qty_cover_luar_lidah'] = $qtyLidah['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1']; $calculations['qty2_cover_dalam_bawah'] = $qty['qty2']; $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];
                    
                    // Update for BUKU MAGNET Cover Dalam Lidah Panjang
                    if ($boxType === 'BUKU MAGNET') {
                        // Formula: panjang box bawah + 3
                        $calculations['panjang_cover_dalam_lidah'] = $this->getFloatVal($bawahPanjang) + 3;
                    } else {
                        // Mengambil panjang_board_lidah yang sudah dihitung secara kondisional
                        $calculations['panjang_cover_dalam_lidah'] = $calculations['panjang_board_lidah']; 
                    }
                    $calculations['lebar_cover_dalam_lidah'] = ($boxType === 'BUKU PITA') ? $this->calculateBukuPitaCoverDalamLebarLidah($bawahLebar, $bawahTinggi) : $this->calculateBukuMagnetCoverDalamLebarLidah($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_lidah'], $calculations['lebar_cover_dalam_lidah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_lidah'] = $qty['qty1']; $calculations['qty2_cover_dalam_lidah'] = $qty['qty2']; $calculations['final_qty_cover_dalam_lidah'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1']; $calculations['qty2_busa'] = $qty['qty2']; $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;

            case 'SELONGSONG':
                if ($this->includeBoard && $itemBoard) {
                    $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qty['qty1']; $calculations['qty2_board_bawah'] = $qty['qty2']; $calculations['final_qty_board_bawah'] = $qty['final_qty'];
                    
                    $calculations['panjang_board_selongsong'] = $this->calculateSelongsongBoardPanjangSelongsong($bawahLebar, $bawahTinggi);
                    $calculations['lebar_board_selongsong'] = $this->calculateSelongsongBoardLebarSelongsong($bawahPanjang, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_selongsong'], $calculations['lebar_board_selongsong'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_selongsong'] = $qty['qty1']; $calculations['qty2_board_selongsong'] = $qty['qty2']; $calculations['final_qty_board_selongsong'] = $qty['final_qty'];
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    $calculations['panjang_cover_luar_bawah'] = $this->calculateBaseCoverLuarPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_luar_bawah'] = $this->calculateCoverLuarLebarBawahStyle($bawahLebar, $bawahTinggi); // Menggunakan L+2T+3+4
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qty['qty1']; $calculations['qty2_cover_luar_bawah'] = $qty['qty2']; $calculations['final_qty_cover_luar_bawah'] = $qty['final_qty'];
                    
                    $calculations['panjang_cover_luar_selongsong'] = $this->calculateSelongsongCoverLuarPanjangSelongsong($bawahLebar, $bawahTinggi);
                    $calculations['lebar_cover_luar_selongsong'] = $this->calculateSelongsongCoverLuarLebarSelongsong($bawahPanjang, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_selongsong'], $calculations['lebar_cover_luar_selongsong'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_selongsong'] = $qty['qty1']; $calculations['qty2_cover_luar_selongsong'] = $qty['qty2']; $calculations['final_qty_cover_luar_selongsong'] = $qty['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1']; $calculations['qty2_cover_dalam_bawah'] = $qty['qty2']; $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];
                    
                    $calculations['panjang_cover_dalam_selongsong'] = $this->calculateSelongsongCoverDalamPanjangSelongsong($bawahLebar, $bawahTinggi);
                    $calculations['lebar_cover_dalam_selongsong'] = $this->calculateSelongsongCoverDalamLebarSelongsong($bawahPanjang, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_selongsong'], $calculations['lebar_cover_dalam_selongsong'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_selongsong'] = $qty['qty1']; $calculations['qty2_cover_dalam_selongsong'] = $qty['qty2']; $calculations['final_qty_cover_dalam_selongsong'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1']; $calculations['qty2_busa'] = $qty['qty2']; $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;
        }
        return $calculations;
    }

    protected function calculateAndStoreUnitPrices(array &$allData, int $orderQuantity): void
    {
        $componentConfigs = [
            'board_atas' => ['item_key' => 'selected_item_board', 'final_qty_key' => 'final_qty_board_atas', 'unit_price_key' => 'unit_price_board_atas', 'toggle_key' => 'includeBoard', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury']],
            'board_bawah' => ['item_key' => 'selected_item_board', 'final_qty_key' => 'final_qty_board_bawah', 'unit_price_key' => 'unit_price_board_bawah', 'toggle_key' => 'includeBoard', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']],
            'board_kuping' => ['item_key' => 'selected_item_board', 'final_qty_key' => 'final_qty_board_kuping', 'unit_price_key' => 'unit_price_board_kuping', 'toggle_key' => 'includeBoard', 'relevant_box_types' => ['JENDELA']],
            'board_lidah' => ['item_key' => 'selected_item_board', 'final_qty_key' => 'final_qty_board_lidah', 'unit_price_key' => 'unit_price_board_lidah', 'toggle_key' => 'includeBoard', 'relevant_box_types' => ['BUKU PITA', 'BUKU MAGNET']],
            'board_selongsong' => ['item_key' => 'selected_item_board', 'final_qty_key' => 'final_qty_board_selongsong', 'unit_price_key' => 'unit_price_board_selongsong', 'toggle_key' => 'includeBoard', 'relevant_box_types' => ['SELONGSONG']],
            
            'cover_luar_atas' => ['item_key' => 'selected_item_cover_luar', 'final_qty_key' => 'final_qty_cover_luar_atas', 'unit_price_key' => 'unit_price_cover_luar_atas', 'toggle_key' => 'includeCoverLuar', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury']],
            'cover_luar_bawah' => ['item_key' => 'selected_item_cover_luar', 'final_qty_key' => 'final_qty_cover_luar_bawah', 'unit_price_key' => 'unit_price_cover_luar_bawah', 'toggle_key' => 'includeCoverLuar', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']],
            'cover_luar_kuping' => ['item_key' => 'selected_item_cover_luar', 'final_qty_key' => 'final_qty_cover_luar_kuping', 'unit_price_key' => 'unit_price_cover_luar_kuping', 'toggle_key' => 'includeCoverLuar', 'relevant_box_types' => ['JENDELA']],
            'cover_luar_lidah' => ['item_key' => 'selected_item_cover_luar', 'final_qty_key' => 'final_qty_cover_luar_lidah', 'unit_price_key' => 'unit_price_cover_luar_lidah', 'toggle_key' => 'includeCoverLuar', 'relevant_box_types' => ['BUKU PITA', 'BUKU MAGNET']],
            'cover_luar_selongsong' => ['item_key' => 'selected_item_cover_luar', 'final_qty_key' => 'final_qty_cover_luar_selongsong', 'unit_price_key' => 'unit_price_cover_luar_selongsong', 'toggle_key' => 'includeCoverLuar', 'relevant_box_types' => ['SELONGSONG']],

            'cover_dalam_atas' => ['item_key' => 'selected_item_cover_dalam', 'final_qty_key' => 'final_qty_cover_dalam_atas', 'unit_price_key' => 'unit_price_cover_dalam_atas', 'toggle_key' => 'includeCoverDalam', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA']],
            'cover_dalam_bawah' => ['item_key' => 'selected_item_cover_dalam', 'final_qty_key' => 'final_qty_cover_dalam_bawah', 'unit_price_key' => 'unit_price_cover_dalam_bawah', 'toggle_key' => 'includeCoverDalam', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']],
            'cover_dalam_lidah' => ['item_key' => 'selected_item_cover_dalam', 'final_qty_key' => 'final_qty_cover_dalam_lidah', 'unit_price_key' => 'unit_price_cover_dalam_lidah', 'toggle_key' => 'includeCoverDalam', 'relevant_box_types' => ['BUKU PITA', 'BUKU MAGNET']],
            'cover_dalam_selongsong' => ['item_key' => 'selected_item_cover_dalam', 'final_qty_key' => 'final_qty_cover_dalam_selongsong', 'unit_price_key' => 'unit_price_cover_dalam_selongsong', 'toggle_key' => 'includeCoverDalam', 'relevant_box_types' => ['SELONGSONG']],
            
            'busa' => ['item_key' => 'selected_item_busa', 'final_qty_key' => 'final_qty_busa', 'unit_price_key' => 'unit_price_busa', 'toggle_key' => 'includeBusa', 'relevant_box_types' => ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']],
        ];
        $currentBoxType = $allData['box_type_selection'] ?? null;

        foreach ($componentConfigs as $partName => $config) {
            $isToggledOn = (bool)($allData[$config['toggle_key']] ?? false);
            $isItemSelected = !empty($allData[$config['item_key']]);
            $isRelevantForBoxType = in_array($currentBoxType, $config['relevant_box_types']);
            $finalQty = $this->getFloatVal($allData[$config['final_qty_key']] ?? 0); 

            if ($isToggledOn && $isItemSelected && $isRelevantForBoxType && $finalQty > 0) {
                $item = ProductionItem::find($allData[$config['item_key']]);
                if ($item) {
                    $pricePerSheet = $this->getItemPriceByQuantity($item, $orderQuantity);
                    $allData[$config['unit_price_key']] = $pricePerSheet / $finalQty;
                } else {
                    $allData[$config['unit_price_key']] = 0;
                }
            } else {
                $allData[$config['unit_price_key']] = 0;
            }
        }
    }

    public function updateAllCalculations(): void
    {
        $currentState = $this->form->getState();
        $calculatedValues = $this->calculateAllDimensionsAndQuantities($currentState);
        $allData = array_merge($currentState, $calculatedValues);
        $quantity = (int)($allData['quantity'] ?? 1);
        $this->calculateAndStoreUnitPrices($allData, $quantity); 
        $this->form->fill($allData);
    }

    public function form(Form $form): Form
    {
        // ... (Definisi form schema tetap sama seperti sebelumnya)
        // Helper untuk membuat TextInput dimensi input
        $dimensionInputSchema = function (string $name, string|\Closure $label): TextInput {
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

        // Helper untuk membuat TextInput dimensi display (disabled)
        $dimensionDisplaySchema = function (string $name, string|\Closure $label, string $suffix = 'cm'): TextInput {
            return TextInput::make($name)
                ->label($label)
                ->disabled()
                ->suffix($suffix)
                ->placeholder('0');
        };
        
        // Helper untuk Toggle komponen
        $componentToggleSchema = function (string $name, string $label) {
            return Forms\Components\Toggle::make($name)
                ->label($label)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, $livewire) use ($name) {
                    if (!$get($name)) {
                        $itemSelectKey = 'selected_item_' . strtolower(str_replace('include', '', $name));
                        $set($itemSelectKey, null);
                    }
                    $livewire->updateAllCalculations();
                });
        };

        // Helper untuk Select Item Produksi per komponen
        $componentItemSelectSchema = function(string $name, string $label, string $categoryName, string $mainToggleName) {
            return Forms\Components\Select::make($name)
                ->label($label)
                ->options(function() use ($categoryName) {
                    return ProductionItem::whereHas('category', fn ($q) => $q->where('name', $categoryName))->pluck('name', 'id');
                })
                ->nullable()
                ->live()
                ->searchable()
                ->placeholder('Pilih Item '. $label)
                ->hidden(fn (Get $get): bool => !$get($mainToggleName) )
                ->afterStateUpdated(fn ($livewire) => $livewire->updateAllCalculations());
        };

        $boxTypeOptions = [
            'TAB' => 'TAB',
            'BUSA' => 'BUSA',
            'JENDELA' => 'JENDELA',
            'BUKU PITA' => 'BUKU PITA',
            'BUKU MAGNET' => 'BUKU MAGNET',
            'SELONGSONG' => 'SELONGSONG',
            'Double WallTreasury' => 'Double Wall Treasury'
        ];

        // Helper untuk label dinamis berdasarkan jenis box
        $getDynamicLabel = function(Get $get, string $part, string $defaultPrefix = "Box"): string {
            $boxType = $get('box_type_selection');
            if ($part === 'atas') {
                if ($boxType === 'JENDELA') return "Kuping";
                if ($boxType === 'SELONGSONG') return "Selongsong";
                return $defaultPrefix . " Atas";
            }
            if ($part === 'lidah' && in_array($boxType, ['BUKU PITA', 'BUKU MAGNET'])) return "Lidah";
            if ($part === 'selongsong' && $boxType === 'SELONGSONG') return "Selongsong";

            return $defaultPrefix . " " . Str::title($part);
        };
        
        // Helper untuk visibility bagian form berdasarkan jenis box
        $isPartVisible = function(Get $get, string $part): bool {
            $boxType = $get('box_type_selection');
            if ($part === 'atas') return in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'SELONGSONG']);
            if ($part === 'bawah') return in_array($boxType, ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA', 'BUKU PITA', 'BUKU MAGNET', 'SELONGSONG']);
            if ($part === 'lidah') return in_array($boxType, ['BUKU PITA', 'BUKU MAGNET']);
            if ($part === 'kuping_display') return $boxType === 'JENDELA'; 
            if ($part === 'selongsong_display') return $boxType === 'SELONGSONG';
            return false;
        };

        $sections = [
            Section::make('Informasi Produk')
                ->schema([
                    Select::make('box_type_selection')->label('Jenis Box')->options($boxTypeOptions)->default('TAB')->live()->afterStateUpdated(fn ($livewire) => $livewire->updateAllCalculations())->columnSpan(2),
                    TextInput::make('product_name')->label('Nama Produk')->required()->live(onBlur: true)->columnSpan(1),
                    Select::make('size')->label('Ukuran Box (Master Cost)')->options(MasterCost::pluck('size', 'size')->toArray())->required()->live()->columnSpan(1),
                    Select::make('poly_dimension')->label('Dimensi Poly')->options(fn () => PolyCost::all()->pluck('dimension', 'dimension'))->nullable()->live()->columnSpan(1),
                    Select::make('include_knife_cost')->label('Termasuk Ongkos Pisau')->options(['ada' => 'Ada','tidak_ada' => 'Tidak Ada'])->required()->default('tidak_ada')->columnSpan(1),
                    TextInput::make('quantity')->label('Jumlah Pesan')->numeric()->default(1)->minValue(1)->required()->live(onBlur: true)->afterStateUpdated(fn ($livewire) => $livewire->updateAllCalculations())->columnSpan(1),
                ])->columns(3),

            Section::make('Dimensi Box Input')
                ->description("Masukkan dimensi box dalam satuan centimeter.")
                ->schema([
                    Section::make()->label(fn(Get $get) => "Dimensi " . $getDynamicLabel($get, "atas"))->columns(3)->collapsible()
                        ->hidden(fn(Get $get): bool => !$isPartVisible($get, 'atas') )
                        ->schema([
                            $dimensionInputSchema('atas_panjang', fn(Get $get) => "Panjang " . $getDynamicLabel($get, "atas")),
                            $dimensionInputSchema('atas_lebar', fn(Get $get) => "Lebar " . $getDynamicLabel($get, "atas")),
                            $dimensionInputSchema('atas_tinggi', fn(Get $get) => "Tinggi " . $getDynamicLabel($get, "atas")),
                        ]),
                    Section::make('Dimensi Box Bawah')->columns(3)->collapsible()
                        ->hidden(fn(Get $get): bool => !$isPartVisible($get, 'bawah') )
                        ->schema([
                            $dimensionInputSchema('bawah_panjang', 'Panjang Box Bawah'),
                            $dimensionInputSchema('bawah_lebar', 'Lebar Box Bawah'),
                            $dimensionInputSchema('bawah_tinggi', 'Tinggi Box Bawah'),
                        ]),
                ])->columns(1)->icon('heroicon-o-cube'),

            Section::make('Pilihan Komponen Material')
                ->columns(2)->collapsible()->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    $componentToggleSchema('includeBoard', 'Sertakan Board'),
                    $componentItemSelectSchema('selected_item_board', 'Board', 'Board', 'includeBoard'),
                    $componentToggleSchema('includeCoverLuar', 'Sertakan Cover Luar'),
                    $componentItemSelectSchema('selected_item_cover_luar', 'Cover Luar', 'Cover Luar', 'includeCoverLuar'),
                    $componentToggleSchema('includeCoverDalam', 'Sertakan Cover Dalam'),
                    $componentItemSelectSchema('selected_item_cover_dalam', 'Cover Dalam', 'Cover Dalam', 'includeCoverDalam'),
                    $componentToggleSchema('includeBusa', 'Sertakan Busa'),
                    $componentItemSelectSchema('selected_item_busa', 'Busa', 'Busa', 'includeBusa'),
                ]),
            
            Section::make('Hasil Perhitungan Dimensi Komponen (Potongan Jadi)')
                ->columns(1)->collapsible()->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Fieldset::make('Dimensi Board')->columns(2)->hidden(fn(Get $get) => !$get('includeBoard') || empty($get('selected_item_board')))->schema([
                        Group::make([$dimensionDisplaySchema('panjang_board_atas', fn(Get $get) => "Pjg. ".$getDynamicLabel($get, 'atas', 'Box')), $dimensionDisplaySchema('lebar_board_atas', fn(Get $get) => "Lbr. ".$getDynamicLabel($get, 'atas', 'Box'))])->columns(2)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('panjang_board_kuping', "Pjg. Kuping"), $dimensionDisplaySchema('lebar_board_kuping', "Lbr. Kuping")])->columns(2)->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('panjang_board_selongsong', "Pjg. Selongsong"), $dimensionDisplaySchema('lebar_board_selongsong', "Lbr. Selongsong")])->columns(2)->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('panjang_board_bawah', "Pjg. Box Bawah"), $dimensionDisplaySchema('lebar_board_bawah', "Lbr. Box Bawah")])->columns(2)->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('panjang_board_lidah', "Pjg. Lidah"), $dimensionDisplaySchema('lebar_board_lidah', "Lbr. Lidah")])->columns(2)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Dimensi Cover Luar')->columns(2)->hidden(fn(Get $get) => !$get('includeCoverLuar') || empty($get('selected_item_cover_luar')))->schema([
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_atas', fn(Get $get) => "Pjg. ".$getDynamicLabel($get, 'atas', 'Box')), $dimensionDisplaySchema('lebar_cover_luar_atas', fn(Get $get) => "Lbr. ".$getDynamicLabel($get, 'atas', 'Box'))])->columns(2)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_kuping', "Pjg. Kuping"), $dimensionDisplaySchema('lebar_cover_luar_kuping', "Lbr. Kuping")])->columns(2)->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_selongsong', "Pjg. Selongsong"), $dimensionDisplaySchema('lebar_cover_luar_selongsong', "Lbr. Selongsong")])->columns(2)->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_bawah', "Pjg. Box Bawah"), $dimensionDisplaySchema('lebar_cover_luar_bawah', "Lbr. Box Bawah")])->columns(2)->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_lidah', "Pjg. Lidah"), $dimensionDisplaySchema('lebar_cover_luar_lidah', "Lbr. Lidah")])->columns(2)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Dimensi Cover Dalam')->columns(2)->hidden(fn(Get $get) => !$get('includeCoverDalam') || empty($get('selected_item_cover_dalam')))->schema([
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_atas', fn(Get $get) => "Pjg. ".$getDynamicLabel($get, 'atas', 'Box')), $dimensionDisplaySchema('lebar_cover_dalam_atas', fn(Get $get) => "Lbr. ".$getDynamicLabel($get, 'atas', 'Box'))])->columns(2)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_selongsong', "Pjg. Selongsong"), $dimensionDisplaySchema('lebar_cover_dalam_selongsong', "Lbr. Selongsong")])->columns(2)->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_bawah', "Pjg. Box Bawah"), $dimensionDisplaySchema('lebar_cover_dalam_bawah', "Lbr. Box Bawah")])->columns(2)->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_lidah', "Pjg. Lidah"), $dimensionDisplaySchema('lebar_cover_dalam_lidah', "Lbr. Lidah")])->columns(2)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Dimensi Busa')->columns(2)->hidden(fn(Get $get) => !$get('includeBusa') || empty($get('selected_item_busa')) || !$isPartVisible($get, 'bawah') )->schema([
                        $dimensionDisplaySchema('panjang_busa', 'Panjang Busa'), $dimensionDisplaySchema('lebar_busa', 'Lebar Busa')
                    ]),
                ]),
            Section::make('Hasil Perhitungan Kuantitas dari Bahan')
                ->columns(1)->collapsible()->icon('heroicon-o-view-columns')
                ->schema([
                    Fieldset::make('Kuantitas Board')->columns(1)->hidden(fn(Get $get) => !$get('includeBoard') || empty($get('selected_item_board')))->schema([
                        Group::make([$dimensionDisplaySchema('board_panjang_kertas', 'Pjg. Kertas'), $dimensionDisplaySchema('board_lebar_kertas', 'Lbr. Kertas')])->columns(2),
                        Placeholder::make('qty_board_atas_label')->label(fn(Get $get) => "Kuantitas ".$getDynamicLabel($get, 'atas', 'Box')." (Board)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('qty1_board_atas', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_atas', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_atas', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Placeholder::make('qty_board_kuping_label')->label("Kuantitas Kuping (Board)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('qty1_board_kuping', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_kuping', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_kuping', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Placeholder::make('qty_board_selongsong_label')->label("Kuantitas Selongsong (Board)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('qty1_board_selongsong', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_selongsong', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_selongsong', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Placeholder::make('qty_board_bawah_label')->label("Kuantitas Box Bawah (Board)")->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('qty1_board_bawah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_bawah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_bawah', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Placeholder::make('qty_board_lidah_label')->label("Kuantitas Lidah (Board)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                        Group::make([$dimensionDisplaySchema('qty1_board_lidah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_lidah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_lidah', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Kuantitas Cover Luar')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverLuar') || empty($get('selected_item_cover_luar')))->schema([
                        Group::make([$dimensionDisplaySchema('cover_luar_panjang_kertas', 'Pjg. Kertas'), $dimensionDisplaySchema('cover_luar_lebar_kertas', 'Lbr. Kertas')])->columns(2),
                        Placeholder::make('qty_cl_atas_label')->label(fn(Get $get) => "Kuantitas ".$getDynamicLabel($get, 'atas', 'Box')." (CL)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_atas', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_atas', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_atas', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Placeholder::make('qty_cl_kuping_label')->label("Kuantitas Kuping (CL)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_kuping', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_kuping', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_kuping', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Placeholder::make('qty_cl_selongsong_label')->label("Kuantitas Selongsong (CL)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_selongsong', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_selongsong', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_selongsong', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Placeholder::make('qty_cl_bawah_label')->label("Kuantitas Box Bawah (CL)")->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_bawah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_bawah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_bawah', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Placeholder::make('qty_cl_lidah_label')->label("Kuantitas Lidah (CL)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_lidah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_lidah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_lidah', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Kuantitas Cover Dalam')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverDalam') || empty($get('selected_item_cover_dalam')))->schema([
                        Group::make([$dimensionDisplaySchema('cover_dalam_panjang_kertas', 'Pjg. Kertas'), $dimensionDisplaySchema('cover_dalam_lebar_kertas', 'Lbr. Kertas')])->columns(2),
                        Placeholder::make('qty_cd_atas_label')->label(fn(Get $get) => "Kuantitas ".$getDynamicLabel($get, 'atas', 'Box')." (CD)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])), 
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_atas', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_atas', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_atas', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
                        Placeholder::make('qty_cd_selongsong_label')->label("Kuantitas Selongsong (CD)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_selongsong', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_selongsong', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_selongsong', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Placeholder::make('qty_cd_bawah_label')->label("Kuantitas Box Bawah (CD)")->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_bawah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_bawah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_bawah', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Placeholder::make('qty_cd_lidah_label')->label("Kuantitas Lidah (CD)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_lidah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_lidah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_lidah', 'Final', 'pcs')])->columns(3)->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Kuantitas Busa')->columns(1)->hidden(fn(Get $get) => !$get('includeBusa') || empty($get('selected_item_busa')) || !$isPartVisible($get, 'bawah'))->schema([
                        Group::make([$dimensionDisplaySchema('busa_panjang_kertas', 'Pjg. Material'), $dimensionDisplaySchema('busa_lebar_kertas', 'Lbr. Material')])->columns(2),
                        Group::make([$dimensionDisplaySchema('qty1_busa', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_busa', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_busa', 'Final', 'pcs')])->columns(3),
                    ]),
                ]),
            Section::make('Hasil Perhitungan Harga Satuan Komponen')
                ->columns(1)->collapsible()->icon('heroicon-o-currency-dollar')
                ->schema([
                    Fieldset::make('Harga Satuan Board')->columns(1)->hidden(fn(Get $get) => !$get('includeBoard') || empty($get('selected_item_board')))->schema([
                        $dimensionDisplaySchema('unit_price_board_atas', fn(Get $get) => $getDynamicLabel($get, 'atas', 'Box')." (Board)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        $dimensionDisplaySchema('unit_price_board_kuping', "Kuping (Board)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        $dimensionDisplaySchema('unit_price_board_selongsong', "Selongsong (Board)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        $dimensionDisplaySchema('unit_price_board_bawah', "Box Bawah (Board)", 'Rp/pcs')->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        $dimensionDisplaySchema('unit_price_board_lidah', "Lidah (Board)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Harga Satuan Cover Luar')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverLuar') || empty($get('selected_item_cover_luar')))->schema([
                        $dimensionDisplaySchema('unit_price_cover_luar_atas', fn(Get $get) => $getDynamicLabel($get, 'atas', 'Box')." (CL)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        $dimensionDisplaySchema('unit_price_cover_luar_kuping', "Kuping (CL)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        $dimensionDisplaySchema('unit_price_cover_luar_selongsong', "Selongsong (CL)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        $dimensionDisplaySchema('unit_price_cover_luar_bawah', "Box Bawah (CL)", 'Rp/pcs')->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        $dimensionDisplaySchema('unit_price_cover_luar_lidah', "Lidah (CL)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Harga Satuan Cover Dalam')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverDalam') || empty($get('selected_item_cover_dalam')))->schema([
                        $dimensionDisplaySchema('unit_price_cover_dalam_atas', fn(Get $get) => $getDynamicLabel($get, 'atas', 'Box')." (CD)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
                        $dimensionDisplaySchema('unit_price_cover_dalam_selongsong', "Selongsong (CD)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        $dimensionDisplaySchema('unit_price_cover_dalam_bawah', "Box Bawah (CD)", 'Rp/pcs')->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        $dimensionDisplaySchema('unit_price_cover_dalam_lidah', "Lidah (CD)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Harga Satuan Busa')->columns(1)->hidden(fn(Get $get) => !$get('includeBusa') || empty($get('selected_item_busa')) || !$isPartVisible($get, 'bawah'))->schema([
                        $dimensionDisplaySchema('unit_price_busa', 'Busa', 'Rp/pcs')
                    ]),
                ]),
            Placeholder::make('calculation_result_display')
                ->label('Estimasi Total Biaya Produksi')
                ->content(fn (): string => $this->calculationResult ?? 'Rp 0')
                ->visible(fn (): bool => $this->calculationResult !== null)
                ->columnSpanFull(),
        ];
        return $form->schema($sections)->statePath('data');
    }

    public function calculateFinalPrice(): void
    {
        $allData = $this->form->getState(); 
        $totalMaterialCostPerUnit = 0;
        $quantity = (int)($allData['quantity'] ?? 1);
        $currentBoxType = $allData['box_type_selection'] ?? null;

        $activePriceKeysMap = [
            'TAB' => ['unit_price_board_atas', 'unit_price_board_bawah', 'unit_price_cover_luar_atas', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_atas', 'unit_price_cover_dalam_bawah', 'unit_price_busa'],
            'BUSA' => ['unit_price_board_atas', 'unit_price_board_bawah', 'unit_price_cover_luar_atas', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_atas', 'unit_price_cover_dalam_bawah', 'unit_price_busa'],
            'Double WallTreasury' => ['unit_price_board_atas', 'unit_price_board_bawah', 'unit_price_cover_luar_atas', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_atas', 'unit_price_cover_dalam_bawah', 'unit_price_busa'],
            'JENDELA' => ['unit_price_board_kuping', 'unit_price_board_bawah', 'unit_price_cover_luar_kuping', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_atas', 'unit_price_cover_dalam_bawah', 'unit_price_busa'], 
            'BUKU PITA' => ['unit_price_board_lidah', 'unit_price_board_bawah', 'unit_price_cover_luar_lidah', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_lidah', 'unit_price_cover_dalam_bawah', 'unit_price_busa'],
            'BUKU MAGNET' => ['unit_price_board_lidah', 'unit_price_board_bawah', 'unit_price_cover_luar_lidah', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_lidah', 'unit_price_cover_dalam_bawah', 'unit_price_busa'],
            'SELONGSONG' => ['unit_price_board_selongsong', 'unit_price_board_bawah', 'unit_price_cover_luar_selongsong', 'unit_price_cover_luar_bawah', 'unit_price_cover_dalam_selongsong', 'unit_price_cover_dalam_bawah', 'unit_price_busa'],
        ];
        
        $activeUnitPricesForCurrentType = $activePriceKeysMap[$currentBoxType] ?? [];

        foreach ($activeUnitPricesForCurrentType as $unitPriceKey) {
            $materialToggle = null; $itemSelectKey = null;
            if (str_contains($unitPriceKey, '_board_')) { $materialToggle = 'includeBoard'; $itemSelectKey = 'selected_item_board';}
            elseif (str_contains($unitPriceKey, '_cover_luar_')) { $materialToggle = 'includeCoverLuar'; $itemSelectKey = 'selected_item_cover_luar';}
            elseif (str_contains($unitPriceKey, '_cover_dalam_')) { $materialToggle = 'includeCoverDalam'; $itemSelectKey = 'selected_item_cover_dalam';}
            elseif (str_contains($unitPriceKey, '_busa')) { $materialToggle = 'includeBusa'; $itemSelectKey = 'selected_item_busa';}

            $isMaterialIncluded = $materialToggle ? ($allData[$materialToggle] ?? false) : true; 
            $isItemSelected = $itemSelectKey ? !empty($allData[$itemSelectKey]) : true; 

            if ($isMaterialIncluded && $isItemSelected && !empty($allData[$unitPriceKey]) && is_numeric($allData[$unitPriceKey])) {
                $totalMaterialCostPerUnit += (float)$allData[$unitPriceKey];
            }
        }

        $totalMaterialCost = $totalMaterialCostPerUnit * $quantity;
        $otherCosts = 0;
        if (!empty($allData['size'])) {
            $masterCostData = MasterCost::where('size', $allData['size'])->first();
            if ($masterCostData) $otherCosts += (float)($masterCostData->cost_per_unit ?? 0) * $quantity; 
        }
        if (!empty($allData['poly_dimension'])) {
            $polyCostData = PolyCost::where('dimension', $allData['poly_dimension'])->first();
            if ($polyCostData) $otherCosts += (float)($polyCostData->cost ?? 0) * $quantity; 
        }
        // if (($allData['include_knife_cost'] ?? 'tidak_ada') === 'ada') {
        //     if (!defined('KNIFE_COST')) { define('KNIFE_COST', 50000); } // Contoh biaya pisau
        //     $otherCosts += KNIFE_COST;
        // }
        
        $finalTotalCost = $totalMaterialCost + $otherCosts;
        $this->calculationResult = "Rp " . number_format($finalTotalCost, 0, ',', '.');
        
        $boxTypeLabel = $allData['box_type_selection'] ? Str::title(str_replace('_', ' ', $allData['box_type_selection'])) : 'Tidak Diketahui';
        Notification::make()->title('Estimasi Harga Dihitung')->body($this->calculationResult . ' (Jenis Box: ' . $boxTypeLabel . ')')->success()->send();
    }

    public function saveFullCalculation(): void
    {
        $dataToSave = $this->form->getState();
        $priceNumeric = $this->calculationResult ? (float)preg_replace('/[Rp. ]/', '', $this->calculationResult) : 0;
        $dataToSave['total_price_estimate_numeric'] = $priceNumeric;
        $dataToSave['total_price_estimate_display'] = $this->calculationResult;
        
        // PriceCalculation::create($dataToSave); 
        
        Notification::make()
            ->title('Kalkulasi Siap Disimpan')
            ->body('Data kalkulasi (sebagian): ' . json_encode(Arr::only($dataToSave, [
                'box_type_selection', 'quantity', 'atas_panjang', 'atas_lebar', 'atas_tinggi', 
                'bawah_panjang', 'bawah_lebar', 'bawah_tinggi', 
                'total_price_estimate_display' 
            ])))
            ->success()
            ->send();
    }

    protected function calculateSheetQuantities($itemPanjang, $itemLebar, $panjangKertas, $lebarKertas): array
    {
        $itemPanjang = $this->getFloatVal($itemPanjang);
        $itemLebar = $this->getFloatVal($itemLebar);
        $panjangKertas = $this->getFloatVal($panjangKertas);
        $lebarKertas = $this->getFloatVal($lebarKertas);

        if ($itemPanjang <= 0 || $itemLebar <= 0 || $panjangKertas <= 0 || $lebarKertas <= 0) {
            return ['qty1' => 0, 'qty2' => 0, 'final_qty' => 0];
        }

        $qty1_panjang_wise = floor($panjangKertas / $itemPanjang);
        $qty1_lebar_wise = floor($lebarKertas / $itemLebar);
        $qty1 = $qty1_panjang_wise * $qty1_lebar_wise;

        $qty2_panjang_wise = floor($panjangKertas / $itemLebar); 
        $qty2_lebar_wise = floor($lebarKertas / $itemPanjang); 
        $qty2 = $qty2_panjang_wise * $qty2_lebar_wise;
        
        $finalQty = max($qty1, $qty2);

        return ['qty1' => (int)$qty1, 'qty2' => (int)$qty2, 'final_qty' => (int)$finalQty];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('calculateFinalPrice')
                ->label('Hitung Estimasi Harga')
                ->action('calculateFinalPrice') 
                ->color('success'),
            Action::make('saveFullCalculation')
                ->label('Simpan Perhitungan')
                ->action('saveFullCalculation')
                ->color('warning')
                ->requiresConfirmation() 
        ];
    }
}