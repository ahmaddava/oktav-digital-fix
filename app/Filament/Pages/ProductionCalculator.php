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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Kalkulator Harga';
    protected static string $view = 'filament.pages.production-calculator';

    public static function canAccess(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()->can('page_ProductionCalculator');
    }

    public bool $showSummaryAfterSave = false;

    // Properti publik untuk binding form
    public ?array $data = [];

    // Properti untuk toggle include
    public bool $includeBoard = false;
    public bool $includeCoverLuar = false;
    public bool $includeCoverDalam = false;
    public bool $includeBusa = false;

    public $calculationResult = null; // Total estimasi harga dalam format display

    // Properti publik untuk menampilkan ringkasan hasil perhitungan
    public ?float $summaryTotalMaterialCost = null;
    public ?float $summaryTotalProductionWorkCost = null;
    public ?float $summaryTotalPolyCost = null;
    public ?float $summaryActualKnifeCost = null;
    public ?float $summaryProfitAmount = null;
    public ?float $summarySellingPricePerItem = null;
    public ?float $summaryTotalPrice = null;
    public ?float $displayAppliedProfitPercentage = null; // Untuk menampilkan profit yg diaplikasikan di Blade

    // Properti publik untuk menampilkan harga satuan per komponen jadi
    public ?float $unitPriceBoardAtas = null;
    public ?float $unitPriceBoardBawah = null;
    public ?float $unitPriceBoardKuping = null;
    public ?float $unitPriceBoardLidah = null;
    public ?float $unitPriceBoardSelongsong = null;
    public ?float $unitPriceClAtas = null;
    public ?float $unitPriceClBawah = null;
    public ?float $unitPriceClKuping = null;
    public ?float $unitPriceClLidah = null;
    public ?float $unitPriceClSelongsong = null;
    public ?float $unitPriceCdAtas = null;
    public ?float $unitPriceCdBawah = null;
    public ?float $unitPriceCdLidah = null;
    public ?float $unitPriceCdSelongsong = null;
    public ?float $unitPriceBusa = null;

    private function getFloatVal($value): float
    {
        $cleanedValue = preg_replace('/[^\d.,]/', '', (string)$value);
        $cleanedValue = str_replace(',', '.', $cleanedValue);
        return is_numeric($cleanedValue) ? (float)$cleanedValue : 0.0;
    }

    public function mount(): void
    {
        $this->form->fill([
            'quantity' => 1,
            'custom_profit_percentage' => null, // Field baru untuk profit kustom
            'include_knife_cost' => 'tidak_ada',
            'includeBoard' => $this->includeBoard,
            'includeCoverLuar' => $this->includeCoverLuar,
            'includeCoverDalam' => $this->includeCoverDalam,
            'includeBusa' => $this->includeBusa,
            'box_type_selection' => 'TAB',
            'product_name' => null,
            'size' => null,
            'poly_dimension' => null,
            'atas_panjang' => null,
            'atas_lebar' => null,
            'atas_tinggi' => null,
            'bawah_panjang' => null,
            'bawah_lebar' => null,
            'bawah_tinggi' => null,
            'selected_item_board' => null,
            'selected_item_cover_luar' => null,
            'selected_item_cover_dalam' => null,
            'selected_item_busa' => null,
        ]);
        $this->clearCalculationResults();
    }

    protected function getItemPriceByQuantity(?ProductionItem $item, int $quantity): float
    {
        if (!$item) {
            return 0.0;
        }
        return (float)($item->price ?? 0.0);
    }

    // --- START: Fungsi Formula (tidak berubah dari kode Anda) ---
    protected function calculateBaseBoardPanjang($panjangBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($panjangBox) + 3;
    }
    protected function calculateBaseBoardLebar($lebarBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3;
    }
    protected function calculateBaseCoverLuarPanjang($panjangBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($panjangBox) + 3 + 4;
    }
    protected function calculateCoverLuarLebarAtasStyle($lebarBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3;
    }
    protected function calculateCoverLuarLebarBawahStyle($lebarBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3 + 4;
    }
    protected function calculateBaseCoverDalamPanjang($panjangBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($panjangBox) + 3;
    }
    protected function calculateBaseCoverDalamLebar($lebarBox, $tinggiBox): float
    {
        return (2 * $this->getFloatVal($tinggiBox)) + $this->getFloatVal($lebarBox) + 3;
    }
    protected function calculateBaseBusaPanjang($panjangBoxBawah): float
    {
        return $this->getFloatVal($panjangBoxBawah) + 3;
    }
    protected function calculateBaseBusaLebar($lebarBoxBawah): float
    {
        return $this->getFloatVal($lebarBoxBawah) + 3;
    }
    protected function calculateJendelaBoardPanjangKuping($lebarBoxAtas, $tinggiBoxAtas): float
    {
        return (2 * $this->getFloatVal($tinggiBoxAtas)) + $this->getFloatVal($lebarBoxAtas) + 3;
    }
    protected function calculateJendelaBoardLebarKuping($panjangBoxAtas, $tinggiBoxAtas): float
    {
        return $this->getFloatVal($panjangBoxAtas) + $this->getFloatVal($tinggiBoxAtas) + 3;
    }
    protected function calculateJendelaCoverLuarPanjangBoxBawah($panjangBoxBawah, $lebarBoxBawah, $panjangKertasCoverLuar): float
    {
        $val = (2 * $this->getFloatVal($panjangBoxBawah)) + (2 * $this->getFloatVal($lebarBoxBawah)) + 3 + 2;
        if ($this->getFloatVal($panjangKertasCoverLuar) == 0) return $val;
        return $val > $this->getFloatVal($panjangKertasCoverLuar) ? $val / 2 : ($val - $this->getFloatVal($panjangBoxBawah));
    }
    protected function calculateJendelaCoverLuarLebarBoxBawah($tinggiBoxBawah): float
    {
        return $this->getFloatVal($tinggiBoxBawah) + 5 + 3;
    }
    protected function calculateJendelaCoverLuarPanjangKuping($lebarBoxAtas, $tinggiBoxAtas): float
    {
        return (2 * $this->getFloatVal($tinggiBoxAtas)) + $this->getFloatVal($lebarBoxAtas) + 3 + 4;
    }
    protected function calculateJendelaCoverLuarLebarKuping($panjangBoxAtas, $tinggiBoxAtas): float
    {
        return $this->getFloatVal($panjangBoxAtas) + $this->getFloatVal($tinggiBoxAtas) + 3 + 4;
    }
    protected function calculateJendelaCoverDalamPanjangAtas($lebarBoxAtas, $tinggiBoxAtas): float
    {
        return (2 * $this->getFloatVal($tinggiBoxAtas)) + $this->getFloatVal($lebarBoxAtas) + 3 + 4;
    }
    protected function calculateJendelaCoverDalamLebarAtas($panjangBoxAtas, $tinggiBoxAtas): float
    {
        return $this->getFloatVal($tinggiBoxAtas) + $this->getFloatVal($panjangBoxAtas) + 3 + 4;
    }
    protected function calculateBukuMagnetBoardPanjangBoxBawah($panjangBoxBawah, $lebarBoxBawah): float
    {
        return (2 * $this->getFloatVal($lebarBoxBawah)) + $this->getFloatVal($panjangBoxBawah) + 3;
    }
    protected function calculateBukuMagnetBoardLebarBoxBawah($lebarBoxBawah, $tinggiBoxBawah): float
    {
        return (2 * $this->getFloatVal($tinggiBoxBawah)) + $this->getFloatVal($lebarBoxBawah) + 2;
    }
    protected function calculateBukuMagnet_NEW_BoardPanjangLidah($panjangBoxBawah, $tinggiBoxBawah, $panjangKertasBoard = 66): float
    {
        $val = (2 * $this->getFloatVal($panjangBoxBawah)) + ($this->getFloatVal($tinggiBoxBawah) * 2) + (0.5 * 3) + 3;
        if ($this->getFloatVal($panjangKertasBoard) == 0) return $val;
        return $val > $this->getFloatVal($panjangKertasBoard) ? $val / 2 : $val;
    }
    protected function calculateBukuMagnet_NEW_BoardLebarLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertasBoard = 77): float
    {
        $val = (2 * $this->getFloatVal($lebarBoxBawah)) + ($this->getFloatVal($tinggiBoxBawah) * 2) + (0.5 * 3) + 3;
        if ($this->getFloatVal($lebarKertasBoard) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertasBoard) ? $val / 2 : $val;
    }
    protected function calculateBukuPita_OLD_BoardPanjangLidah($panjangBoxBawah): float
    {
        return $this->getFloatVal($panjangBoxBawah) + 3;
    }
    protected function calculateBukuPita_OLD_BoardLebarLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertasBoard = 77): float
    {
        $val = (2 * $this->getFloatVal($lebarBoxBawah)) + $this->getFloatVal($tinggiBoxBawah) + (0.5 * 2) + 3;
        if ($this->getFloatVal($lebarKertasBoard) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertasBoard) ? $val / 2 : $val;
    }
    protected function calculateBukuPitaCoverLuarPanjangLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertasCoverLuar): float
    {
        $val = (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3 + 2;
        if ($this->getFloatVal($lebarKertasCoverLuar) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertasCoverLuar) ? $val / 2 : ($val - $this->getFloatVal($lebarBoxBawah));
    }
    protected function calculateBukuPitaCoverLuarLebarLidah($panjangBoxBawah): float
    {
        return $this->getFloatVal($panjangBoxBawah) + 5 + 3;
    }
    protected function calculateBukuMagnetCoverLuarPanjangLidah($panjangBoxBawah, $tinggiBoxBawah, $panjangKertas): float
    {
        $val = (2 * $this->getFloatVal($panjangBoxBawah)) + ($this->getFloatVal($tinggiBoxBawah) * 2) + (0.5 * 3) + 5 + 3;
        if ($this->getFloatVal($panjangKertas) == 0) return $val;
        return $val > $this->getFloatVal($panjangKertas) ? $val / 2 : $val;
    }
    protected function calculateBukuMagnetCoverLuarLebarLidah($lebarBoxBawah, $tinggiBoxBawah, $lebarKertas): float
    {
        $val = $this->getFloatVal($lebarBoxBawah) + 5 + 3;
        if ($this->getFloatVal($lebarKertas) == 0) return $val;
        return $val > $this->getFloatVal($lebarKertas) ? $val / 2 : $val;
    }
    protected function calculateBukuPitaCoverDalamLebarLidah($lebarBoxBawah, $tinggiBoxBawah): float
    {
        return $this->getFloatVal($lebarBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 3;
    }
    protected function calculateBukuMagnetCoverDalamLebarLidah($lebarBoxBawah, $tinggiBoxBawah): float
    {
        return $this->getFloatVal($lebarBoxBawah) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3;
    }
    protected function calculateSelongsongBoardPanjangSelongsong($lebarBoxBawah, $tinggiBoxBawah): float
    {
        return (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3;
    }
    protected function calculateSelongsongBoardLebarSelongsong($panjangBoxBawah, $tinggiBoxBawah): float
    {
        return $this->getFloatVal($panjangBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 3;
    }
    protected function calculateSelongsongCoverLuarPanjangSelongsong($lebarBoxBawah, $tinggiBoxBawah): float
    {
        return (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 2 + 4;
    }
    protected function calculateSelongsongCoverLuarLebarSelongsong($panjangBoxBawah, $tinggiBoxBawah): float
    {
        return $this->getFloatVal($panjangBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 4 + 3;
    }
    protected function calculateSelongsongCoverDalamPanjangSelongsong($lebarBoxBawah, $tinggiBoxBawah): float
    {
        return (2 * $this->getFloatVal($lebarBoxBawah)) + (2 * $this->getFloatVal($tinggiBoxBawah)) + 3;
    }
    protected function calculateSelongsongCoverDalamLebarSelongsong($panjangBoxBawah, $tinggiBoxBawah): float
    {
        return $this->getFloatVal($panjangBoxBawah) + $this->getFloatVal($tinggiBoxBawah) + 3;
    }
    // --- END: Fungsi Formula ---

    protected function initializeCalculatedFields(): array
    {
        $fields = [
            'panjang_board_atas',
            'lebar_board_atas',
            'qty1_board_atas',
            'qty2_board_atas',
            'final_qty_board_atas',
            'panjang_board_bawah',
            'lebar_board_bawah',
            'qty1_board_bawah',
            'qty2_board_bawah',
            'final_qty_board_bawah',
            'panjang_board_kuping',
            'lebar_board_kuping',
            'qty1_board_kuping',
            'qty2_board_kuping',
            'final_qty_board_kuping',
            'panjang_board_lidah',
            'lebar_board_lidah',
            'qty1_board_lidah',
            'qty2_board_lidah',
            'final_qty_board_lidah',
            'panjang_board_selongsong',
            'lebar_board_selongsong',
            'qty1_board_selongsong',
            'qty2_board_selongsong',
            'final_qty_board_selongsong',
            'board_panjang_kertas',
            'board_lebar_kertas',

            'panjang_cover_luar_atas',
            'lebar_cover_luar_atas',
            'qty1_cover_luar_atas',
            'qty2_cover_luar_atas',
            'final_qty_cover_luar_atas',
            'panjang_cover_luar_bawah',
            'lebar_cover_luar_bawah',
            'qty1_cover_luar_bawah',
            'qty2_cover_luar_bawah',
            'final_qty_cover_luar_bawah',
            'panjang_cover_luar_kuping',
            'lebar_cover_luar_kuping',
            'qty1_cover_luar_kuping',
            'qty2_cover_luar_kuping',
            'final_qty_cover_luar_kuping',
            'panjang_cover_luar_lidah',
            'lebar_cover_luar_lidah',
            'qty1_cover_luar_lidah',
            'qty2_cover_luar_lidah',
            'final_qty_cover_luar_lidah',
            'panjang_cover_luar_selongsong',
            'lebar_cover_luar_selongsong',
            'qty1_cover_luar_selongsong',
            'qty2_cover_luar_selongsong',
            'final_qty_cover_luar_selongsong',
            'cover_luar_panjang_kertas',
            'cover_luar_lebar_kertas',

            'panjang_cover_dalam_atas',
            'lebar_cover_dalam_atas',
            'qty1_cover_dalam_atas',
            'qty2_cover_dalam_atas',
            'final_qty_cover_dalam_atas',
            'panjang_cover_dalam_bawah',
            'lebar_cover_dalam_bawah',
            'qty1_cover_dalam_bawah',
            'qty2_cover_dalam_bawah',
            'final_qty_cover_dalam_bawah',
            'panjang_cover_dalam_lidah',
            'lebar_cover_dalam_lidah',
            'qty1_cover_dalam_lidah',
            'qty2_cover_dalam_lidah',
            'final_qty_cover_dalam_lidah',
            'panjang_cover_dalam_selongsong',
            'lebar_cover_dalam_selongsong',
            'qty1_cover_dalam_selongsong',
            'qty2_cover_dalam_selongsong',
            'final_qty_cover_dalam_selongsong',
            'cover_dalam_panjang_kertas',
            'cover_dalam_lebar_kertas',

            'panjang_busa',
            'lebar_busa',
            'qty1_busa',
            'qty2_busa',
            'final_qty_busa',
            'busa_panjang_kertas',
            'busa_lebar_kertas',

            'unit_price_board_atas',
            'unit_price_board_bawah',
            'unit_price_board_kuping',
            'unit_price_board_lidah',
            'unit_price_board_selongsong',
            'unit_price_cover_luar_atas',
            'unit_price_cover_luar_bawah',
            'unit_price_cover_luar_kuping',
            'unit_price_cover_luar_lidah',
            'unit_price_cover_luar_selongsong',
            'unit_price_cover_dalam_atas',
            'unit_price_cover_dalam_bawah',
            'unit_price_cover_dalam_lidah',
            'unit_price_cover_dalam_selongsong',
            'unit_price_busa',
        ];
        $initialized = [];
        foreach ($fields as $field) {
            $initialized[$field] = 0.0;
        }
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

        if ($this->includeBoard && $itemBoard) {
            $calculations['board_panjang_kertas'] = $pkBoard;
            $calculations['board_lebar_kertas'] = $lkBoard;
        }
        if ($this->includeCoverLuar && $itemCoverLuar) {
            $calculations['cover_luar_panjang_kertas'] = $pkCoverLuar;
            $calculations['cover_luar_lebar_kertas'] = $lkCoverLuar;
        }
        if ($this->includeCoverDalam && $itemCoverDalam) {
            $calculations['cover_dalam_panjang_kertas'] = $pkCoverDalam;
            $calculations['cover_dalam_lebar_kertas'] = $lkCoverDalam;
        }
        if ($this->includeBusa && $itemBusa) {
            $calculations['busa_panjang_kertas'] = $pkBusa;
            $calculations['busa_lebar_kertas'] = $lkBusa;
        }

        // Logika perhitungan dimensi berdasarkan boxType (tidak diubah dari kode Anda)
        switch ($boxType) {
            case 'TAB':
            case 'BUSA':
            case 'Double WallTreasury':
                if ($this->includeBoard && $itemBoard) {
                    $calculations['panjang_board_atas'] = $this->calculateBaseBoardPanjang($atasPanjang, $atasTinggi);
                    $calculations['lebar_board_atas'] = $this->calculateBaseBoardLebar($atasLebar, $atasTinggi);
                    $qtyAtas = $this->calculateSheetQuantities($calculations['panjang_board_atas'], $calculations['lebar_board_atas'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_atas'] = $qtyAtas['qty1'];
                    $calculations['qty2_board_atas'] = $qtyAtas['qty2'];
                    $calculations['final_qty_board_atas'] = $qtyAtas['final_qty'];

                    $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);
                    $qtyBawah = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qtyBawah['qty1'];
                    $calculations['qty2_board_bawah'] = $qtyBawah['qty2'];
                    $calculations['final_qty_board_bawah'] = $qtyBawah['final_qty'];
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    $calculations['panjang_cover_luar_atas'] = $this->calculateBaseCoverLuarPanjang($atasPanjang, $atasTinggi);
                    if ($boxType === 'Double WallTreasury') {
                        $calculations['lebar_cover_luar_atas'] = (2 * $this->getFloatVal($atasTinggi)) + $this->getFloatVal($atasPanjang) + 3;
                    } else {
                        $calculations['lebar_cover_luar_atas'] = $this->calculateCoverLuarLebarAtasStyle($atasLebar, $atasTinggi);
                    }
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_atas'], $calculations['lebar_cover_luar_atas'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_atas'] = $qty['qty1'];
                    $calculations['qty2_cover_luar_atas'] = $qty['qty2'];
                    $calculations['final_qty_cover_luar_atas'] = $qty['final_qty'];

                    $calculations['panjang_cover_luar_bawah'] = $this->calculateBaseCoverLuarPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_luar_bawah'] = $this->calculateCoverLuarLebarBawahStyle($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_luar_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_luar_bawah'] = $qty['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_atas'] = $this->calculateBaseCoverDalamPanjang($atasPanjang, $atasTinggi);
                    $calculations['lebar_cover_dalam_atas'] = $this->calculateBaseCoverDalamLebar($atasLebar, $atasTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_atas'], $calculations['lebar_cover_dalam_atas'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_atas'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_atas'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_atas'] = $qty['final_qty'];

                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1'];
                    $calculations['qty2_busa'] = $qty['qty2'];
                    $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;

            case 'JENDELA':
                if ($this->includeBoard && $itemBoard) {
                    $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qty['qty1'];
                    $calculations['qty2_board_bawah'] = $qty['qty2'];
                    $calculations['final_qty_board_bawah'] = $qty['final_qty'];

                    $calculations['panjang_board_kuping'] = $this->calculateJendelaBoardPanjangKuping($atasLebar, $atasTinggi);
                    $calculations['lebar_board_kuping'] = $this->calculateJendelaBoardLebarKuping($atasPanjang, $atasTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_kuping'], $calculations['lebar_board_kuping'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_kuping'] = $qty['qty1'];
                    $calculations['qty2_board_kuping'] = $qty['qty2'];
                    $calculations['final_qty_board_kuping'] = $qty['final_qty'];
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    $calculations['panjang_cover_luar_bawah'] = $this->calculateJendelaCoverLuarPanjangBoxBawah($bawahPanjang, $bawahLebar, $pkCoverLuar);
                    $calculations['lebar_cover_luar_bawah'] = $this->calculateJendelaCoverLuarLebarBoxBawah($bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_luar_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_luar_bawah'] = $qty['final_qty'];

                    $calculations['panjang_cover_luar_kuping'] = $this->calculateJendelaCoverLuarPanjangKuping($atasLebar, $atasTinggi);
                    $calculations['lebar_cover_luar_kuping'] = $this->calculateJendelaCoverLuarLebarKuping($atasPanjang, $atasTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_kuping'], $calculations['lebar_cover_luar_kuping'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_kuping'] = $qty['qty1'];
                    $calculations['qty2_cover_luar_kuping'] = $qty['qty2'];
                    $calculations['final_qty_cover_luar_kuping'] = $qty['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];

                    $calculations['panjang_cover_dalam_atas'] = $this->calculateJendelaCoverDalamPanjangAtas($atasLebar, $atasTinggi);
                    $calculations['lebar_cover_dalam_atas'] = $this->calculateJendelaCoverDalamLebarAtas($atasPanjang, $atasTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_atas'], $calculations['lebar_cover_dalam_atas'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_atas'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_atas'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_atas'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1'];
                    $calculations['qty2_busa'] = $qty['qty2'];
                    $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;

            case 'BUKU PITA':
            case 'BUKU MAGNET':
                if ($this->includeBoard && $itemBoard) {
                    if ($boxType === 'BUKU MAGNET') {
                        $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                        $calculations['lebar_board_bawah'] = $this->calculateBukuMagnetBoardLebarBoxBawah($bawahLebar, $bawahTinggi);

                        $calculations['panjang_board_lidah'] = $this->calculateBukuMagnet_NEW_BoardPanjangLidah($bawahPanjang, $bawahTinggi, $pkBoard);
                        $calculations['lebar_board_lidah'] = $this->calculateBukuMagnet_NEW_BoardLebarLidah($bawahLebar, $bawahTinggi, $lkBoard);
                    } else { // 'BUKU PITA'
                        $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                        $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);

                        $calculations['panjang_board_lidah'] = $this->calculateBukuPita_OLD_BoardPanjangLidah($bawahPanjang);
                        $calculations['lebar_board_lidah'] = $this->calculateBukuPita_OLD_BoardLebarLidah($bawahLebar, $bawahTinggi, $lkBoard);
                    }
                    $qtyBawah = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qtyBawah['qty1'];
                    $calculations['qty2_board_bawah'] = $qtyBawah['qty2'];
                    $calculations['final_qty_board_bawah'] = $qtyBawah['final_qty'];

                    if (($calculations['panjang_board_lidah'] ?? 0) > 0 && ($calculations['lebar_board_lidah'] ?? 0) > 0) {
                        $qtyLidah = $this->calculateSheetQuantities($calculations['panjang_board_lidah'], $calculations['lebar_board_lidah'], $pkBoard, $lkBoard);
                        $calculations['qty1_board_lidah'] = $qtyLidah['qty1'];
                        $calculations['qty2_board_lidah'] = $qtyLidah['qty2'];
                        $calculations['final_qty_board_lidah'] = $qtyLidah['final_qty'];
                    } else {
                        $calculations['panjang_board_lidah'] = 0.0;
                        $calculations['lebar_board_lidah'] = 0.0;
                        $calculations['qty1_board_lidah'] = 0;
                        $calculations['qty2_board_lidah'] = 0;
                        $calculations['final_qty_board_lidah'] = 0;
                    }
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    if ($boxType === 'BUKU PITA') {
                        $calculations['panjang_cover_luar_bawah'] = $this->calculateJendelaCoverLuarPanjangBoxBawah($bawahPanjang, $bawahLebar, $pkCoverLuar);
                        $calculations['lebar_cover_luar_bawah'] = $this->calculateJendelaCoverLuarLebarBoxBawah($bawahTinggi);
                        $calculations['panjang_cover_luar_lidah'] = $this->calculateBukuPitaCoverLuarPanjangLidah($bawahLebar, $bawahTinggi, $lkCoverLuar);
                        $calculations['lebar_cover_luar_lidah'] = $this->calculateBukuPitaCoverLuarLebarLidah($bawahPanjang);
                    } else { // BUKU MAGNET
                        $calculations['panjang_cover_luar_bawah'] = $this->calculateJendelaCoverLuarPanjangBoxBawah($bawahPanjang, $bawahLebar, $pkCoverLuar);
                        $calculations['lebar_cover_luar_bawah'] = $this->calculateJendelaCoverLuarLebarBoxBawah($bawahTinggi);

                        $calculations['panjang_cover_luar_lidah'] = (2 * $this->getFloatVal($bawahLebar)) + (2 * $this->getFloatVal($bawahTinggi)) + (0.5 * 3) + 5 + 3;
                        $calculations['lebar_cover_luar_lidah'] = $this->getFloatVal($bawahPanjang) + 5 + 3;
                    }
                    $qtyBawah = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qtyBawah['qty1'];
                    $calculations['qty2_cover_luar_bawah'] = $qtyBawah['qty2'];
                    $calculations['final_qty_cover_luar_bawah'] = $qtyBawah['final_qty'];

                    $qtyLidah = $this->calculateSheetQuantities($calculations['panjang_cover_luar_lidah'], $calculations['lebar_cover_luar_lidah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_lidah'] = $qtyLidah['qty1'];
                    $calculations['qty2_cover_luar_lidah'] = $qtyLidah['qty2'];
                    $calculations['final_qty_cover_luar_lidah'] = $qtyLidah['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];

                    if ($boxType === 'BUKU MAGNET') {
                        $calculations['panjang_cover_dalam_lidah'] = $this->getFloatVal($bawahPanjang) + 3;
                    } else {
                        $calculations['panjang_cover_dalam_lidah'] = $calculations['panjang_board_lidah'];
                    }
                    $calculations['lebar_cover_dalam_lidah'] = ($boxType === 'BUKU PITA') ? $this->calculateBukuPitaCoverDalamLebarLidah($bawahLebar, $bawahTinggi) : $this->calculateBukuMagnetCoverDalamLebarLidah($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_lidah'], $calculations['lebar_cover_dalam_lidah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_lidah'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_lidah'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_lidah'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1'];
                    $calculations['qty2_busa'] = $qty['qty2'];
                    $calculations['final_qty_busa'] = $qty['final_qty'];
                }
                break;

            case 'SELONGSONG':
                if ($this->includeBoard && $itemBoard) {
                    $calculations['panjang_board_bawah'] = $this->calculateBaseBoardPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_board_bawah'] = $this->calculateBaseBoardLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_bawah'], $calculations['lebar_board_bawah'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_bawah'] = $qty['qty1'];
                    $calculations['qty2_board_bawah'] = $qty['qty2'];
                    $calculations['final_qty_board_bawah'] = $qty['final_qty'];

                    $calculations['panjang_board_selongsong'] = $this->calculateSelongsongBoardPanjangSelongsong($bawahLebar, $bawahTinggi);
                    $calculations['lebar_board_selongsong'] = $this->calculateSelongsongBoardLebarSelongsong($bawahPanjang, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_board_selongsong'], $calculations['lebar_board_selongsong'], $pkBoard, $lkBoard);
                    $calculations['qty1_board_selongsong'] = $qty['qty1'];
                    $calculations['qty2_board_selongsong'] = $qty['qty2'];
                    $calculations['final_qty_board_selongsong'] = $qty['final_qty'];
                }
                if ($this->includeCoverLuar && $itemCoverLuar) {
                    $calculations['panjang_cover_luar_bawah'] = $this->calculateBaseCoverLuarPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_luar_bawah'] = $this->calculateCoverLuarLebarBawahStyle($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_bawah'], $calculations['lebar_cover_luar_bawah'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_luar_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_luar_bawah'] = $qty['final_qty'];

                    $calculations['panjang_cover_luar_selongsong'] = $this->calculateSelongsongCoverLuarPanjangSelongsong($bawahLebar, $bawahTinggi);
                    $calculations['lebar_cover_luar_selongsong'] = $this->calculateSelongsongCoverLuarLebarSelongsong($bawahPanjang, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_luar_selongsong'], $calculations['lebar_cover_luar_selongsong'], $pkCoverLuar, $lkCoverLuar);
                    $calculations['qty1_cover_luar_selongsong'] = $qty['qty1'];
                    $calculations['qty2_cover_luar_selongsong'] = $qty['qty2'];
                    $calculations['final_qty_cover_luar_selongsong'] = $qty['final_qty'];
                }
                if ($this->includeCoverDalam && $itemCoverDalam) {
                    $calculations['panjang_cover_dalam_bawah'] = $this->calculateBaseCoverDalamPanjang($bawahPanjang, $bawahTinggi);
                    $calculations['lebar_cover_dalam_bawah'] = $this->calculateBaseCoverDalamLebar($bawahLebar, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_bawah'], $calculations['lebar_cover_dalam_bawah'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_bawah'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_bawah'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_bawah'] = $qty['final_qty'];

                    $calculations['panjang_cover_dalam_selongsong'] = $this->calculateSelongsongCoverDalamPanjangSelongsong($bawahLebar, $bawahTinggi);
                    $calculations['lebar_cover_dalam_selongsong'] = $this->calculateSelongsongCoverDalamLebarSelongsong($bawahPanjang, $bawahTinggi);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_cover_dalam_selongsong'], $calculations['lebar_cover_dalam_selongsong'], $pkCoverDalam, $lkCoverDalam);
                    $calculations['qty1_cover_dalam_selongsong'] = $qty['qty1'];
                    $calculations['qty2_cover_dalam_selongsong'] = $qty['qty2'];
                    $calculations['final_qty_cover_dalam_selongsong'] = $qty['final_qty'];
                }
                if ($this->includeBusa && $itemBusa) {
                    $calculations['panjang_busa'] = $this->calculateBaseBusaPanjang($bawahPanjang);
                    $calculations['lebar_busa'] = $this->calculateBaseBusaLebar($bawahLebar);
                    $qty = $this->calculateSheetQuantities($calculations['panjang_busa'], $calculations['lebar_busa'], $pkBusa, $lkBusa);
                    $calculations['qty1_busa'] = $qty['qty1'];
                    $calculations['qty2_busa'] = $qty['qty2'];
                    $calculations['final_qty_busa'] = $qty['final_qty'];
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

        foreach ($componentConfigs as $config) {
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
                    $allData[$config['unit_price_key']] = 0.0;
                }
            } else {
                $allData[$config['unit_price_key']] = 0.0;
            }
        }
    }

    protected function _calculateFinalPriceInternal(array &$allDataArray, bool $showNotification = true): void
    {
        $allData = $allDataArray;
        $quantity = (int)($allData['quantity'] ?? 1);
        $currentBoxType = $allData['box_type_selection'] ?? null;

        $totalMaterialCostPerUnit = 0.0;
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
            $materialToggle = null;
            $itemSelectKey = null;
            if (str_contains($unitPriceKey, '_board_')) {
                $materialToggle = 'includeBoard';
                $itemSelectKey = 'selected_item_board';
            } elseif (str_contains($unitPriceKey, '_cover_luar_')) {
                $materialToggle = 'includeCoverLuar';
                $itemSelectKey = 'selected_item_cover_luar';
            } elseif (str_contains($unitPriceKey, '_cover_dalam_')) {
                $materialToggle = 'includeCoverDalam';
                $itemSelectKey = 'selected_item_cover_dalam';
            } elseif (str_contains($unitPriceKey, '_busa')) {
                $materialToggle = 'includeBusa';
                $itemSelectKey = 'selected_item_busa';
            }

            $isMaterialIncluded = ($materialToggle && isset($allData[$materialToggle])) ? (bool)$allData[$materialToggle] : true;
            $isItemSelected = ($itemSelectKey && isset($allData[$itemSelectKey])) ? !empty($allData[$itemSelectKey]) : true;

            if ($isMaterialIncluded && $isItemSelected) {
                $totalMaterialCostPerUnit += $this->getFloatVal($allData[$unitPriceKey] ?? 0);
            }
        }

        $totalCostPerUnit = 0.0;
        $totalCostPerUnit += $totalMaterialCostPerUnit;

        $masterCostData = null;
        $masterCostProductionRate = 0.0;
        $masterCostKnifeRate = 0.0;
        $masterCostProfitPercentage = 0.0; // Default profit from MasterCost

        if (!empty($allData['size'])) {
            $masterCostData = MasterCost::where('size', $allData['size'])->first();
            if ($masterCostData) {
                $masterCostProductionRate = $this->getFloatVal($masterCostData->production_cost ?? 0);
                $masterCostKnifeRate = $this->getFloatVal($masterCostData->knife_cost ?? 0);
                $masterCostProfitPercentage = $this->getFloatVal($masterCostData->profit ?? 0);
                $totalCostPerUnit += $masterCostProductionRate;
            }
        }

        $polyCostData = null;
        $polyCostRate = 0.0;
        if (!empty($allData['poly_dimension'])) {
            $polyCostData = PolyCost::where('dimension', $allData['poly_dimension'])->first();
            if ($polyCostData) {
                $polyCostRate = $this->getFloatVal($polyCostData->cost ?? 0);
                $totalCostPerUnit += $polyCostRate;
            }
        }

        $actualKnifeCostPerUnit = 0.0;
        if (($allData['include_knife_cost'] ?? 'tidak_ada') === 'ada') {
            $actualKnifeCostPerUnit = $masterCostKnifeRate;
            $totalCostPerUnit += $actualKnifeCostPerUnit;
        }

        $finalTotalCostBeforeProfit = $totalCostPerUnit * $quantity;

        // --- Profit Calculation with Custom Override ---
        $appliedProfitPercentage = $masterCostProfitPercentage; // Default to MasterCost profit
        if (isset($allData['custom_profit_percentage']) && is_numeric($allData['custom_profit_percentage'])) {
            $customProfitInput = $this->getFloatVal($allData['custom_profit_percentage']);
            if ($customProfitInput >= 0) { // Allow 0% custom profit
                $appliedProfitPercentage = $customProfitInput;
            }
        }
        $totalProfitAmount = $finalTotalCostBeforeProfit * ($appliedProfitPercentage / 100);
        // --- End of Profit Calculation ---

        $finalTotalCost = $finalTotalCostBeforeProfit + $totalProfitAmount;

        // Mengisi properti publik untuk tampilan di Blade
        $this->unitPriceBoardAtas = $this->getFloatVal($allData['unit_price_board_atas'] ?? null);
        $this->unitPriceBoardBawah = $this->getFloatVal($allData['unit_price_board_bawah'] ?? null);
        $this->unitPriceBoardKuping = $this->getFloatVal($allData['unit_price_board_kuping'] ?? null);
        $this->unitPriceBoardLidah = $this->getFloatVal($allData['unit_price_board_lidah'] ?? null);
        $this->unitPriceBoardSelongsong = $this->getFloatVal($allData['unit_price_board_selongsong'] ?? null);
        $this->unitPriceClAtas = $this->getFloatVal($allData['unit_price_cover_luar_atas'] ?? null);
        $this->unitPriceClBawah = $this->getFloatVal($allData['unit_price_cover_luar_bawah'] ?? null);
        $this->unitPriceClKuping = $this->getFloatVal($allData['unit_price_cover_luar_kuping'] ?? null);
        $this->unitPriceClLidah = $this->getFloatVal($allData['unit_price_cover_luar_lidah'] ?? null);
        $this->unitPriceClSelongsong = $this->getFloatVal($allData['unit_price_cover_luar_selongsong'] ?? null);
        $this->unitPriceCdAtas = $this->getFloatVal($allData['unit_price_cover_dalam_atas'] ?? null);
        $this->unitPriceCdBawah = $this->getFloatVal($allData['unit_price_cover_dalam_bawah'] ?? null);
        $this->unitPriceCdLidah = $this->getFloatVal($allData['unit_price_cover_dalam_lidah'] ?? null);
        $this->unitPriceCdSelongsong = $this->getFloatVal($allData['unit_price_cover_dalam_selongsong'] ?? null);
        $this->unitPriceBusa = $this->getFloatVal($allData['unit_price_busa'] ?? null);

        // Simpan ringkasan biaya ke dalam $allDataArray (by reference) & properti publik
        $allDataArray['total_material_cost_summary'] = $totalMaterialCostPerUnit * $quantity;
        $this->summaryTotalMaterialCost = $allDataArray['total_material_cost_summary'];

        $allDataArray['production_cost_summary'] = $masterCostProductionRate * $quantity;
        $this->summaryTotalProductionWorkCost = $allDataArray['production_cost_summary'];

        $allDataArray['poly_cost_summary'] = $polyCostRate * $quantity;
        $this->summaryTotalPolyCost = $allDataArray['poly_cost_summary'];

        $allDataArray['knife_cost_summary'] = $actualKnifeCostPerUnit * $quantity;
        $this->summaryActualKnifeCost = $allDataArray['knife_cost_summary'];

        $allDataArray['applied_profit_percentage_summary'] = $appliedProfitPercentage; // Store the applied profit percentage
        $this->displayAppliedProfitPercentage = $appliedProfitPercentage; // For Blade display

        $allDataArray['profit_amount_summary'] = $totalProfitAmount;
        $this->summaryProfitAmount = $allDataArray['profit_amount_summary'];

        $allDataArray['total_price_per_item_summary'] = ($quantity > 0) ? ($finalTotalCost / $quantity) : 0;
        $this->summarySellingPricePerItem = $allDataArray['total_price_per_item_summary'];

        $allDataArray['total_price_summary'] = $finalTotalCost;
        $this->summaryTotalPrice = $allDataArray['total_price_summary'];

        $this->calculationResult = "Rp " . number_format($finalTotalCost, 0, ',', '.');

        if ($showNotification) {
            $boxTypeLabel = $allData['box_type_selection'] ? Str::title(str_replace('_', ' ', $allData['box_type_selection'])) : 'Tidak Diketahui';
            Notification::make()
                ->title('Estimasi Harga Dihitung')
                ->body('Total Estimasi: ' . $this->calculationResult . ' (Jenis Box: ' . $boxTypeLabel . ', Profit: ' . $appliedProfitPercentage . '%)')
                ->success()
                ->send();
        }
    }

    public function calculateFinalPrice(bool $showNotification = true): void
    {
        $currentState = $this->form->getState();
        $calculatedDimensions = $this->calculateAllDimensionsAndQuantities($currentState);

        $dataForCalculations = array_merge($currentState, $calculatedDimensions);
        $quantity = (int)($dataForCalculations['quantity'] ?? 1);

        $this->calculateAndStoreUnitPrices($dataForCalculations, $quantity);
        $this->form->fill($dataForCalculations);
        $this->_calculateFinalPriceInternal($dataForCalculations, $showNotification);
    }

    public function updateAllCalculations(bool $showNotification = false): array
    {
        $currentState = $this->form->getState();
        $calculatedDimensions = $this->calculateAllDimensionsAndQuantities($currentState);
        $dataForCalculations = array_merge($currentState, $calculatedDimensions);
        $quantity = (int)($dataForCalculations['quantity'] ?? 1);

        $this->calculateAndStoreUnitPrices($dataForCalculations, $quantity);
        $this->form->fill($dataForCalculations);
        $this->_calculateFinalPriceInternal($dataForCalculations, $showNotification);

        return $dataForCalculations;
    }

    protected function clearCalculationResults(): void
    {
        $this->calculationResult = null;
        $this->summaryTotalMaterialCost = null;
        $this->summaryTotalProductionWorkCost = null;
        $this->summaryTotalPolyCost = null;
        $this->summaryActualKnifeCost = null;
        $this->summaryProfitAmount = null;
        $this->summarySellingPricePerItem = null;
        $this->summaryTotalPrice = null;
        $this->displayAppliedProfitPercentage = null;

        $this->unitPriceBoardAtas = null;
        $this->unitPriceBoardBawah = null;
        $this->unitPriceBoardKuping = null;
        $this->unitPriceBoardLidah = null;
        $this->unitPriceBoardSelongsong = null;
        $this->unitPriceClAtas = null;
        $this->unitPriceClBawah = null;
        $this->unitPriceClKuping = null;
        $this->unitPriceClLidah = null;
        $this->unitPriceClSelongsong = null;
        $this->unitPriceCdAtas = null;
        $this->unitPriceCdBawah = null;
        $this->unitPriceCdLidah = null;
        $this->unitPriceCdSelongsong = null;
        $this->unitPriceBusa = null;
    }

    public function saveFullCalculation(): void
    {
        try {
            $this->form->validate();
            $dataToSave = $this->updateAllCalculations(false);

            $priceNumeric = $this->calculationResult ? $this->getFloatVal(preg_replace('/[^0-9,.]/', '', $this->calculationResult)) : 0.0;

            $selectedItemsIds = [];
            if (!empty($dataToSave['selected_item_board'])) $selectedItemsIds['board'] = $dataToSave['selected_item_board'];
            if (!empty($dataToSave['selected_item_cover_luar'])) $selectedItemsIds['cover_luar'] = $dataToSave['selected_item_cover_luar'];
            if (!empty($dataToSave['selected_item_cover_dalam'])) $selectedItemsIds['cover_dalam'] = $dataToSave['selected_item_cover_dalam'];
            if (!empty($dataToSave['selected_item_busa'])) $selectedItemsIds['busa'] = $dataToSave['selected_item_busa'];

            $masterCostData = !empty($dataToSave['size']) ? MasterCost::where('size', $dataToSave['size'])->first() : null;
            $polyCostData = !empty($dataToSave['poly_dimension']) ? PolyCost::where('dimension', $dataToSave['poly_dimension'])->first() : null;

            $recordData = [
                'product_name' => $dataToSave['product_name'] ?? '',
                'box_type_selection' => $dataToSave['box_type_selection'] ?? '',
                'quantity' => $dataToSave['quantity'] ?? 0,
                'custom_profit_input' => $this->getFloatVal($dataToSave['custom_profit_percentage'] ?? null),
                'include_knife_cost' => $dataToSave['include_knife_cost'] ?? 'tidak_ada',

                'atas_panjang' => $this->getFloatVal($dataToSave['atas_panjang'] ?? null),
                'atas_lebar' => $this->getFloatVal($dataToSave['atas_lebar'] ?? null),
                'atas_tinggi' => $this->getFloatVal($dataToSave['atas_tinggi'] ?? null),
                'bawah_panjang' => $this->getFloatVal($dataToSave['bawah_panjang'] ?? null),
                'bawah_lebar' => $this->getFloatVal($dataToSave['bawah_lebar'] ?? null),
                'bawah_tinggi' => $this->getFloatVal($dataToSave['bawah_tinggi'] ?? null),

                'is_board_included' => (bool)($dataToSave['includeBoard'] ?? false),
                'is_cover_luar_included' => (bool)($dataToSave['includeCoverLuar'] ?? false),
                'is_cover_dalam_included' => (bool)($dataToSave['includeCoverDalam'] ?? false),
                'is_busa_included' => (bool)($dataToSave['includeBusa'] ?? false),

                'selected_items_ids' => json_encode($selectedItemsIds),

                'raw_board_panjang_kertas' => $this->getFloatVal($dataToSave['board_panjang_kertas'] ?? null),
                'raw_board_lebar_kertas' => $this->getFloatVal($dataToSave['board_lebar_kertas'] ?? null),
                'raw_cl_panjang_kertas' => $this->getFloatVal($dataToSave['cover_luar_panjang_kertas'] ?? null),
                'raw_cl_lebar_kertas' => $this->getFloatVal($dataToSave['cover_luar_lebar_kertas'] ?? null),
                'raw_cd_panjang_kertas' => $this->getFloatVal($dataToSave['cover_dalam_panjang_kertas'] ?? null),
                'raw_cd_lebar_kertas' => $this->getFloatVal($dataToSave['cover_dalam_lebar_kertas'] ?? null),
                'raw_busa_panjang_material' => $this->getFloatVal($dataToSave['busa_panjang_kertas'] ?? null),
                'raw_busa_lebar_material' => $this->getFloatVal($dataToSave['busa_lebar_kertas'] ?? null),

                'dim_board_atas_p' => $this->getFloatVal($dataToSave['panjang_board_atas'] ?? null),
                'dim_board_atas_l' => $this->getFloatVal($dataToSave['lebar_board_atas'] ?? null),
                'dim_board_bawah_p' => $this->getFloatVal($dataToSave['panjang_board_bawah'] ?? null),
                'dim_board_bawah_l' => $this->getFloatVal($dataToSave['lebar_board_bawah'] ?? null),
                'dim_board_kuping_p' => $this->getFloatVal($dataToSave['panjang_board_kuping'] ?? null),
                'dim_board_kuping_l' => $this->getFloatVal($dataToSave['lebar_board_kuping'] ?? null),
                'dim_board_lidah_p' => $this->getFloatVal($dataToSave['panjang_board_lidah'] ?? null),
                'dim_board_lidah_l' => $this->getFloatVal($dataToSave['lebar_board_lidah'] ?? null),
                'dim_board_selongsong_p' => $this->getFloatVal($dataToSave['panjang_board_selongsong'] ?? null),
                'dim_board_selongsong_l' => $this->getFloatVal($dataToSave['lebar_board_selongsong'] ?? null),

                'dim_cl_atas_p' => $this->getFloatVal($dataToSave['panjang_cover_luar_atas'] ?? null),
                'dim_cl_atas_l' => $this->getFloatVal($dataToSave['lebar_cover_luar_atas'] ?? null),
                'dim_cl_bawah_p' => $this->getFloatVal($dataToSave['panjang_cover_luar_bawah'] ?? null),
                'dim_cl_bawah_l' => $this->getFloatVal($dataToSave['lebar_cover_luar_bawah'] ?? null),
                'dim_cl_kuping_p' => $this->getFloatVal($dataToSave['panjang_cover_luar_kuping'] ?? null),
                'dim_cl_kuping_l' => $this->getFloatVal($dataToSave['lebar_cover_luar_kuping'] ?? null),
                'dim_cl_lidah_p' => $this->getFloatVal($dataToSave['panjang_cover_luar_lidah'] ?? null),
                'dim_cl_lidah_l' => $this->getFloatVal($dataToSave['lebar_cover_luar_lidah'] ?? null),
                'dim_cl_selongsong_p' => $this->getFloatVal($dataToSave['panjang_cover_luar_selongsong'] ?? null),
                'dim_cl_selongsong_l' => $this->getFloatVal($dataToSave['lebar_cover_luar_selongsong'] ?? null),

                'dim_cd_atas_p' => $this->getFloatVal($dataToSave['panjang_cover_dalam_atas'] ?? null),
                'dim_cd_atas_l' => $this->getFloatVal($dataToSave['lebar_cover_dalam_atas'] ?? null),
                'dim_cd_bawah_p' => $this->getFloatVal($dataToSave['panjang_cover_dalam_bawah'] ?? null),
                'dim_cd_bawah_l' => $this->getFloatVal($dataToSave['lebar_cover_dalam_bawah'] ?? null),
                'dim_cd_lidah_p' => $this->getFloatVal($dataToSave['panjang_cover_dalam_lidah'] ?? null),
                'dim_cd_lidah_l' => $this->getFloatVal($dataToSave['lebar_cover_dalam_lidah'] ?? null),
                'dim_cd_selongsong_p' => $this->getFloatVal($dataToSave['panjang_cover_dalam_selongsong'] ?? null),
                'dim_cd_selongsong_l' => $this->getFloatVal($dataToSave['lebar_cover_dalam_selongsong'] ?? null),

                'dim_busa_p' => $this->getFloatVal($dataToSave['panjang_busa'] ?? null),
                'dim_busa_l' => $this->getFloatVal($dataToSave['lebar_busa'] ?? null),

                'final_qty_board_atas' => $dataToSave['final_qty_board_atas'] ?? null,
                'final_qty_board_bawah' => $dataToSave['final_qty_board_bawah'] ?? null,
                'final_qty_board_kuping' => $dataToSave['final_qty_board_kuping'] ?? null,
                'final_qty_board_lidah' => $dataToSave['final_qty_board_lidah'] ?? null,
                'final_qty_board_selongsong' => $dataToSave['final_qty_board_selongsong'] ?? null,
                'final_qty_cl_atas' => $dataToSave['final_qty_cl_atas'] ?? null,
                'final_qty_cl_bawah' => $dataToSave['final_qty_cl_bawah'] ?? null,
                'final_qty_cl_kuping' => $dataToSave['final_qty_cl_kuping'] ?? null,
                'final_qty_cl_lidah' => $dataToSave['final_qty_cl_lidah'] ?? null,
                'final_qty_cl_selongsong' => $dataToSave['final_qty_cl_selongsong'] ?? null,
                'final_qty_cd_atas' => $dataToSave['final_qty_cd_atas'] ?? null,
                'final_qty_cd_bawah' => $dataToSave['final_qty_cd_bawah'] ?? null,
                'final_qty_cd_lidah' => $dataToSave['final_qty_cd_lidah'] ?? null,
                'final_qty_cd_selongsong' => $dataToSave['final_qty_cd_selongsong'] ?? null,
                'final_qty_busa' => $dataToSave['final_qty_busa'] ?? null,

                'unit_price_board_atas' => $this->getFloatVal($dataToSave['unit_price_board_atas'] ?? null),
                'unit_price_board_bawah' => $this->getFloatVal($dataToSave['unit_price_board_bawah'] ?? null),
                'unit_price_board_kuping' => $this->getFloatVal($dataToSave['unit_price_board_kuping'] ?? null),
                'unit_price_board_lidah' => $this->getFloatVal($dataToSave['unit_price_board_lidah'] ?? null),
                'unit_price_board_selongsong' => $this->getFloatVal($dataToSave['unit_price_board_selongsong'] ?? null),
                'unit_price_cl_atas' => $this->getFloatVal($dataToSave['unit_price_cover_luar_atas'] ?? null),
                'unit_price_cl_bawah' => $this->getFloatVal($dataToSave['unit_price_cover_luar_bawah'] ?? null),
                'unit_price_cl_kuping' => $this->getFloatVal($dataToSave['unit_price_cover_luar_kuping'] ?? null),
                'unit_price_cl_lidah' => $this->getFloatVal($dataToSave['unit_price_cover_luar_lidah'] ?? null),
                'unit_price_cl_selongsong' => $this->getFloatVal($dataToSave['unit_price_cover_luar_selongsong'] ?? null),
                'unit_price_cd_atas' => $this->getFloatVal($dataToSave['unit_price_cover_dalam_atas'] ?? null),
                'unit_price_cd_bawah' => $this->getFloatVal($dataToSave['unit_price_cover_dalam_bawah'] ?? null),
                'unit_price_cd_lidah' => $this->getFloatVal($dataToSave['unit_price_cover_dalam_lidah'] ?? null),
                'unit_price_cd_selongsong' => $this->getFloatVal($dataToSave['unit_price_cover_dalam_selongsong'] ?? null),
                'unit_price_busa' => $this->getFloatVal($dataToSave['unit_price_busa'] ?? null),

                'master_cost_size_selected' => $dataToSave['size'] ?? null,
                'master_cost_production_rate' => $masterCostData ? $this->getFloatVal($masterCostData->production_cost ?? null) : null,
                'master_cost_knife_rate' => $masterCostData ? $this->getFloatVal($masterCostData->knife_cost ?? null) : null,
                'master_cost_profit_percentage' => $masterCostData ? $this->getFloatVal($masterCostData->profit ?? null) : null,

                'poly_dimension_selected' => $dataToSave['poly_dimension'] ?? null,
                'poly_cost_rate' => $polyCostData ? $this->getFloatVal($polyCostData->cost ?? null) : null,

                'summary_total_material_cost' => $this->getFloatVal($dataToSave['total_material_cost_summary'] ?? null),
                'summary_total_production_work_cost' => $this->getFloatVal($dataToSave['production_cost_summary'] ?? null),
                'summary_total_poly_cost' => $this->getFloatVal($dataToSave['poly_cost_summary'] ?? null),
                'summary_actual_knife_cost' => $this->getFloatVal($dataToSave['knife_cost_summary'] ?? null),
                'summary_profit_percentage_applied' => $this->getFloatVal($dataToSave['applied_profit_percentage_summary'] ?? null),
                'summary_total_profit_amount' => $this->getFloatVal($dataToSave['profit_amount_summary'] ?? null),
                'summary_selling_price_per_item' => $this->getFloatVal($dataToSave['total_price_per_item_summary'] ?? null),

                'total_price_estimate_numeric' => $priceNumeric,
                'total_price_estimate_display' => $this->calculationResult,
                'notes' => $dataToSave['notes'] ?? null,
            ];

            DB::beginTransaction();
            PriceCalculation::create($recordData);
            DB::commit();

            Notification::make()
                ->title('Kalkulasi Berhasil Disimpan')
                ->body('Data kalkulasi harga untuk ' . ($recordData['product_name'] ?? 'produk ini') . ' telah berhasil disimpan.')
                ->success()
                ->send();

            $this->resetCalculation();
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Menyimpan Kalkulasi (Validasi)')
                ->body('Terjadi kesalahan validasi pada formulir. Mohon periksa kembali input Anda.')
                ->danger()
                ->send();
            Log::error('Filament Form Validation Failed: ' . $e->getMessage(), ['errors' => $e->errors(), 'data' => $this->form->getState()]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Gagal Menyimpan Kalkulasi')
                ->body('Terjadi kesalahan sistem saat menyimpan data: ' . $e->getMessage())
                ->danger()
                ->send();
            Log::error('Error saving price calculation: ' . $e->getMessage(), ['data' => $recordData ?? [], 'exception' => $e->getTraceAsString()]);
        }
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

    public function resetCalculation(): void
    {
        $this->resetErrorBag();
        $this->form->fill([
            'quantity' => 1,
            'custom_profit_percentage' => null,
            'include_knife_cost' => 'tidak_ada',
            'includeBoard' => false,
            'includeCoverLuar' => false,
            'includeCoverDalam' => false,
            'includeBusa' => false,
            'box_type_selection' => 'TAB',
            'product_name' => null,
            'size' => null,
            'poly_dimension' => null,
            'atas_panjang' => null,
            'atas_lebar' => null,
            'atas_tinggi' => null,
            'bawah_panjang' => null,
            'bawah_lebar' => null,
            'bawah_tinggi' => null,
            'selected_item_board' => null,
            'selected_item_cover_luar' => null,
            'selected_item_cover_dalam' => null,
            'selected_item_busa' => null,
        ]);
        $this->clearCalculationResults();
        $this->updateAllCalculations(false);

        Notification::make()->title('Formulir direset!')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('calculateFinalPrice')
                ->label('Hitung Estimasi Harga')
                ->action('calculateFinalPrice')
                ->color('success')
                ->extraAttributes(['class' => 'filament-button-submit']),
            Action::make('saveFullCalculation')
                ->label('Simpan Perhitungan')
                ->action('saveFullCalculation')
                ->color('warning')
                ->requiresConfirmation()
        ];
    }

    public function form(Form $form): Form
    {
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
                ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations());
        };

        $dimensionDisplaySchema = function (string $name, string|\Closure $label, string $suffix = 'cm'): TextInput {
            return TextInput::make($name)
                ->label($label)
                ->disabled()
                ->suffix($suffix)
                ->placeholder('0');
        };

        $componentToggleSchema = function (string $name, string $label) {
            return Forms\Components\Toggle::make($name)
                ->label($label)
                ->live()
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set, $livewire) use ($name) {
                    if (!$get($name)) {
                        $itemSelectKey = 'selected_item_' . strtolower(str_replace('include', '', $name));
                        $set($itemSelectKey, null);
                    }
                    $livewire->updateAllCalculations();
                });
        };

        $componentItemSelectSchema = function (string $name, string $label, string $categoryName, string $mainToggleName) {
            return Forms\Components\Select::make($name)
                ->label($label)
                ->options(function () use ($categoryName) {
                    return ProductionItem::whereHas('category', fn($q) => $q->where('name', $categoryName))->pluck('name', 'id');
                })
                ->nullable()
                ->live()
                ->searchable()
                ->placeholder('Pilih Item ' . $label)
                ->hidden(fn(Get $get): bool => !$get($mainToggleName))
                ->required(fn(Get $get) => $get($mainToggleName))
                ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations());
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

        $getDynamicLabel = function (Get $get, string $part, string $defaultPrefix = "Box"): string {
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

        $isPartVisible = function (Get $get, string $part): bool {
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
                    Select::make('box_type_selection')
                        ->label('Jenis Box')
                        ->options($boxTypeOptions)
                        ->default('TAB')
                        // ->required()
                        ->live()
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'md' => 2]),
                    TextInput::make('product_name')
                        ->label('Nama Produk')
                        // ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'md' => 1]),
                    Select::make('size')
                        ->label('Ukuran Box (Master Cost)')
                        ->options(MasterCost::pluck('size', 'size')->toArray())
                        // ->required()
                        ->live()
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'sm' => 1]),
                    Select::make('poly_dimension')
                        ->label('Dimensi Poly')
                        ->options(fn() => PolyCost::all()->pluck('dimension', 'dimension'))
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'sm' => 1]),
                    Select::make('include_knife_cost')
                        ->label('Termasuk Ongkos Pisau')
                        ->options(['ada' => 'Ada', 'tidak_ada' => 'Tidak Ada'])
                        // ->required()
                        ->default('tidak_ada')
                        ->live()
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'sm' => 1]),
                    TextInput::make('quantity')
                        ->label('Jumlah Pesan')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        // ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'sm' => 1]),
                    TextInput::make('custom_profit_percentage')
                        ->label('Profit Kustom (%)')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->suffix('%')
                        ->placeholder('Misal: 25')
                        // ->helperText('Kosongkan untuk menggunakan profit dari Master Cost.')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($livewire) => $livewire->updateAllCalculations())
                        ->columnSpan(['default' => 2, 'sm' => 1]),

                ])->columns(['default' => 1, 'md' => 2, 'lg' => 3]),

            Section::make('Dimensi Box Input') // Section utama untuk input dimensi
                ->description("Masukkan dimensi box dalam satuan centimeter.")
                ->icon('heroicon-o-cube')
                ->columns(1) // Section utama ini hanya 1 kolom
                ->schema([
                    Fieldset::make('fieldset_dimensi_bawah') // Menggunakan Fieldset untuk grup bawah
                        ->label('Dimensi Box Bawah') // Label statis untuk Fieldset
                        ->columns(['default' => 1, 'md' => 3]) // Kolom responsif untuk field di dalam Fieldset
                        // ->collapsible() // Dihapus karena menyebabkan error
                        ->hidden(fn(Get $get): bool => !$isPartVisible($get, 'bawah'))
                        ->schema([
                            $dimensionInputSchema('bawah_panjang', 'Panjang Box Bawah')->columnSpan('full'),
                            $dimensionInputSchema('bawah_lebar', 'Lebar Box Bawah')->columnSpan('full'),
                            $dimensionInputSchema('bawah_tinggi', 'Tinggi Box Bawah')->columnSpan('full'),
                        ]),
                    Fieldset::make('fieldset_dimensi_atas') // Menggunakan Fieldset untuk grup atas
                        ->label(fn(Get $get) => "Dimensi " . $getDynamicLabel($get, "atas")) // Label dinamis untuk Fieldset
                        ->columns(['default' => 1, 'md' => 3]) // Kolom responsif untuk field di dalam Fieldset
                        // ->collapsible() // Dihapus karena menyebabkan error
                        ->hidden(fn(Get $get): bool => !$isPartVisible($get, 'atas'))
                        ->schema([
                            $dimensionInputSchema('atas_panjang', fn(Get $get) => "Panjang " . $getDynamicLabel($get, "atas"))->columnSpan('full'),
                            $dimensionInputSchema('atas_lebar', fn(Get $get) => "Lebar " . $getDynamicLabel($get, "atas"))->columnSpan('full'),
                            $dimensionInputSchema('atas_tinggi', fn(Get $get) => "Tinggi " . $getDynamicLabel($get, "atas"))->columnSpan('full'),
                        ]),
                ]),

            Section::make('Pilihan Komponen Material')
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()->icon('heroicon-o-adjustments-horizontal')
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
                    Fieldset::make('Dimensi Board')->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$get('includeBoard') || empty($get('selected_item_board')))->schema([
                        Group::make([$dimensionDisplaySchema('panjang_board_atas', fn(Get $get) => "Pjg. " . $getDynamicLabel($get, 'atas', 'Box')), $dimensionDisplaySchema('lebar_board_atas', fn(Get $get) => "Lbr. " . $getDynamicLabel($get, 'atas', 'Box'))])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('panjang_board_kuping', "Pjg. Kuping"), $dimensionDisplaySchema('lebar_board_kuping', "Lbr. Kuping")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('panjang_board_selongsong', "Pjg. Selongsong"), $dimensionDisplaySchema('lebar_board_selongsong', "Lbr. Selongsong")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('panjang_board_bawah', "Pjg. Box Bawah"), $dimensionDisplaySchema('lebar_board_bawah', "Lbr. Box Bawah")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('panjang_board_lidah', "Pjg. Lidah"), $dimensionDisplaySchema('lebar_board_lidah', "Lbr. Lidah")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Dimensi Cover Luar')->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$get('includeCoverLuar') || empty($get('selected_item_cover_luar')))->schema([
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_atas', fn(Get $get) => "Pjg. " . $getDynamicLabel($get, 'atas', 'Box')), $dimensionDisplaySchema('lebar_cover_luar_atas', fn(Get $get) => "Lbr. " . $getDynamicLabel($get, 'atas', 'Box'))])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_kuping', "Pjg. Kuping"), $dimensionDisplaySchema('lebar_cover_luar_kuping', "Lbr. Kuping")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_selongsong', "Pjg. Selongsong"), $dimensionDisplaySchema('lebar_cover_luar_selongsong', "Lbr. Selongsong")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_bawah', "Pjg. Box Bawah"), $dimensionDisplaySchema('lebar_cover_luar_bawah', "Lbr. Box Bawah")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('panjang_cover_luar_lidah', "Pjg. Lidah"), $dimensionDisplaySchema('lebar_cover_luar_lidah', "Lbr. Lidah")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Dimensi Cover Dalam')->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$get('includeCoverDalam') || empty($get('selected_item_cover_dalam')))->schema([
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_atas', fn(Get $get) => "Pjg. " . $getDynamicLabel($get, 'atas', 'Box')), $dimensionDisplaySchema('lebar_cover_dalam_atas', fn(Get $get) => "Lbr. " . $getDynamicLabel($get, 'atas', 'Box'))])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_selongsong', "Pjg. Selongsong"), $dimensionDisplaySchema('lebar_cover_dalam_selongsong', "Lbr. Selongsong")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_bawah', "Pjg. Box Bawah"), $dimensionDisplaySchema('lebar_cover_dalam_bawah', "Lbr. Box Bawah")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('panjang_cover_dalam_lidah', "Pjg. Lidah"), $dimensionDisplaySchema('lebar_cover_dalam_lidah', "Lbr. Lidah")])->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Dimensi Busa')->columns(['default' => 1, 'sm' => 2])->hidden(fn(Get $get) => !$get('includeBusa') || empty($get('selected_item_busa')) || !$isPartVisible($get, 'bawah'))->schema([
                        $dimensionDisplaySchema('panjang_busa', 'Panjang Busa'),
                        $dimensionDisplaySchema('lebar_busa', 'Lebar Busa')
                    ]),
                ]),
            Section::make('Hasil Perhitungan Kuantitas dari Bahan')
                ->columns(1)->collapsible()->icon('heroicon-o-view-columns')
                ->schema([
                    Fieldset::make('Kuantitas Board')->columns(1)->hidden(fn(Get $get) => !$get('includeBoard') || empty($get('selected_item_board')))->schema([
                        Group::make([$dimensionDisplaySchema('board_panjang_kertas', 'Pjg. Kertas'), $dimensionDisplaySchema('board_lebar_kertas', 'Lbr. Kertas')])->columns(['default' => 1, 'sm' => 2]),
                        Placeholder::make('qty_board_atas_label')->label(fn(Get $get) => "Kuantitas " . $getDynamicLabel($get, 'atas', 'Box') . " (Board)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('qty1_board_atas', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_atas', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_atas', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Placeholder::make('qty_board_kuping_label')->label("Kuantitas Kuping (Board)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('qty1_board_kuping', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_kuping', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_kuping', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Placeholder::make('qty_board_selongsong_label')->label("Kuantitas Selongsong (Board)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('qty1_board_selongsong', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_selongsong', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_selongsong', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Placeholder::make('qty_board_bawah_label')->label("Kuantitas Box Bawah (Board)")->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('qty1_board_bawah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_bawah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_bawah', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Placeholder::make('qty_board_lidah_label')->label("Kuantitas Lidah (Board)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                        Group::make([$dimensionDisplaySchema('qty1_board_lidah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_board_lidah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_board_lidah', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Kuantitas Cover Luar')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverLuar') || empty($get('selected_item_cover_luar')))->schema([
                        Group::make([$dimensionDisplaySchema('cover_luar_panjang_kertas', 'Pjg. Kertas'), $dimensionDisplaySchema('cover_luar_lebar_kertas', 'Lbr. Kertas')])->columns(['default' => 1, 'sm' => 2]),
                        Placeholder::make('qty_cl_atas_label')->label(fn(Get $get) => "Kuantitas " . $getDynamicLabel($get, 'atas', 'Box') . " (CL)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_atas', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_atas', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_atas', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        Placeholder::make('qty_cl_kuping_label')->label("Kuantitas Kuping (CL)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_kuping', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_kuping', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_kuping', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        Placeholder::make('qty_cl_selongsong_label')->label("Kuantitas Selongsong (CL)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_selongsong', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_selongsong', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_selongsong', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Placeholder::make('qty_cl_bawah_label')->label("Kuantitas Box Bawah (CL)")->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_bawah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_bawah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_bawah', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Placeholder::make('qty_cl_lidah_label')->label("Kuantitas Lidah (CL)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_luar_lidah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_luar_lidah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_luar_lidah', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Kuantitas Cover Dalam')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverDalam') || empty($get('selected_item_cover_dalam')))->schema([
                        Group::make([$dimensionDisplaySchema('cover_dalam_panjang_kertas', 'Pjg. Kertas'), $dimensionDisplaySchema('cover_dalam_lebar_kertas', 'Lbr. Kertas')])->columns(['default' => 1, 'sm' => 2]),
                        Placeholder::make('qty_cd_atas_label')->label(fn(Get $get) => "Kuantitas " . $getDynamicLabel($get, 'atas', 'Box') . " (CD)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_atas', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_atas', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_atas', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
                        Placeholder::make('qty_cd_selongsong_label')->label("Kuantitas Selongsong (CD)")->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_selongsong', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_selongsong', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_selongsong', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        Placeholder::make('qty_cd_bawah_label')->label("Kuantitas Box Bawah (CD)")->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_bawah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_bawah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_bawah', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        Placeholder::make('qty_cd_lidah_label')->label("Kuantitas Lidah (CD)")->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                        Group::make([$dimensionDisplaySchema('qty1_cover_dalam_lidah', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_cover_dalam_lidah', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_cover_dalam_lidah', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3])->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Kuantitas Busa')->columns(1)->hidden(fn(Get $get) => !$get('includeBusa') || empty($get('selected_item_busa')) || !$isPartVisible($get, 'bawah'))->schema([
                        Group::make([$dimensionDisplaySchema('busa_panjang_kertas', 'Pjg. Material'), $dimensionDisplaySchema('busa_lebar_kertas', 'Lbr. Material')])->columns(['default' => 1, 'sm' => 2]),
                        Group::make([$dimensionDisplaySchema('qty1_busa', 'Q1', 'pcs'), $dimensionDisplaySchema('qty2_busa', 'Q2', 'pcs'), $dimensionDisplaySchema('final_qty_busa', 'Final', 'pcs')])->columns(['default' => 1, 'sm' => 3]),
                    ]),
                ]),
            Section::make('Hasil Perhitungan Harga Satuan Komponen')
                ->columns(1)->collapsible()->icon('heroicon-o-currency-dollar')
                ->schema([
                    Fieldset::make('Harga Satuan Board')->columns(1)->hidden(fn(Get $get) => !$get('includeBoard') || empty($get('selected_item_board')))->schema([
                        $dimensionDisplaySchema('unit_price_board_atas', fn(Get $get) => $getDynamicLabel($get, 'atas', 'Box') . " (Board)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        $dimensionDisplaySchema('unit_price_board_kuping', "Kuping (Board)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        $dimensionDisplaySchema('unit_price_board_selongsong', "Selongsong (Board)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        $dimensionDisplaySchema('unit_price_board_bawah', "Box Bawah (Board)", 'Rp/pcs')->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        $dimensionDisplaySchema('unit_price_board_lidah', "Lidah (Board)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Harga Satuan Cover Luar')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverLuar') || empty($get('selected_item_cover_luar')))->schema([
                        $dimensionDisplaySchema('unit_price_cover_luar_atas', fn(Get $get) => $getDynamicLabel($get, 'atas', 'Box') . " (CL)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury'])),
                        $dimensionDisplaySchema('unit_price_cover_luar_kuping', "Kuping (CL)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'JENDELA'),
                        $dimensionDisplaySchema('unit_price_cover_luar_selongsong', "Selongsong (CL)", 'Rp/pcs')->hidden(fn(Get $get) => $get('box_type_selection') !== 'SELONGSONG'),
                        $dimensionDisplaySchema('unit_price_cover_luar_bawah', "Box Bawah (CL)", 'Rp/pcs')->hidden(fn(Get $get) => !$isPartVisible($get, 'bawah')),
                        $dimensionDisplaySchema('unit_price_cover_luar_lidah', "Lidah (CL)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['BUKU PITA', 'BUKU MAGNET'])),
                    ]),
                    Fieldset::make('Harga Satuan Cover Dalam')->columns(1)->hidden(fn(Get $get) => !$get('includeCoverDalam') || empty($get('selected_item_cover_dalam')))->schema([
                        $dimensionDisplaySchema('unit_price_cover_dalam_atas', fn(Get $get) => $getDynamicLabel($get, 'atas', 'Box') . " (CD)", 'Rp/pcs')->hidden(fn(Get $get) => !in_array($get('box_type_selection'), ['TAB', 'BUSA', 'Double WallTreasury', 'JENDELA'])),
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
                ->content(fn(): string => $this->calculationResult ?? 'Rp 0')
                ->visible(fn(): bool => $this->calculationResult !== null)
                ->columnSpanFull(),
        ];
        return $form->schema($sections)->statePath('data');
    }
}
