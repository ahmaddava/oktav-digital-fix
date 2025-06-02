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
use Illuminate\Support\Collection;
use App\Models\MasterCost;
use App\Models\PolyCost;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class ProductionCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Kalkulator Harga';
    protected static string $view = 'filament.pages.production-calculator';

    public ?array $data = [];
    public $selectedSize = 'Sedang'; // Default or example, ensure it's handled if used
    public $productName = '';       // Ensure this is bound or used appropriately
    public $calculationResult = null;

    // Properties to control visibility of sections
    public $includeBoard = false;
    public $includeCoverLuar = false;
    public $includeCoverDalam = false;
    public $includeCoverLuarLidah = false;
    public $includeBusa = false;


    public function mount(): void
    {
        // Initialize form with default data, including initial states for toggles
        // which will ensure calculateAllDimensionsAndQuantities uses correct include flags.
        $this->form->fill([
            'includeBoard' => $this->includeBoard,
            'includeCoverLuar' => $this->includeCoverLuar,
            'includeCoverDalam' => $this->includeCoverDalam,
            'includeCoverLuarLidah' => $this->includeCoverLuarLidah,
            'includeBusa' => $this->includeBusa,
            // Add other default values if necessary
        ]);
        // Initial calculation if needed, or rely on user interaction
        // $this->updateBoardCalculations(); // Uncomment if initial calculation is desired
    }

    public function getCategoryItems()
    {
        // Fetch all active categories and their active items
        return ProductionCategory::with(['items' => function ($query) {
            $query->where('is_active', true);
        }])->where('is_active', true)->get();
    }

    // Method untuk mendapatkan harga berdasarkan quantity
    protected function getItemPriceByQuantity($item, $quantity)
    {
        $price = $item->price;

        $tierPrices = $item->prices()
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc')
            ->get();

        if ($tierPrices->isNotEmpty()) {
            $price = $tierPrices->first()->price;
        }

        return $price;
    }

    // Method untuk menghitung board berdasarkan dimensi
    protected function calculateBoard($panjang, $lebar, $tinggi)
    {
        // Pastikan semua input numerik dan tidak null
        if ($panjang === null || $lebar === null || $tinggi === null || !is_numeric($panjang) || !is_numeric($lebar) || !is_numeric($tinggi)) {
            return 0;
        }
        return (2 * (float)$tinggi) + (float)$panjang + 3;
    }

    // Helper function to calculate Qty1, Qty2, Final Qty for a given item type
    protected function calculateQuantities($itemPanjang, $itemLebar, $panjangKertas, $lebarKertas) {
        // Pastikan semua input numerik dan lebih besar dari 0
        if (!is_numeric($itemPanjang) || !is_numeric($itemLebar) || !is_numeric($panjangKertas) || !is_numeric($lebarKertas) ||
            $itemPanjang <= 0 || $itemLebar <= 0 || $panjangKertas <= 0 || $lebarKertas <= 0) {
            return ['qty1' => 0, 'qty2' => 0, 'final_qty' => 0];
        }

        $qty1 = floor($panjangKertas / $itemPanjang) * floor($lebarKertas / $itemLebar);
        $qty2 = floor($lebarKertas / $itemPanjang) * floor($panjangKertas / $itemLebar); // Corrected logic for qty2
        $finalQty = max($qty1, $qty2);
        
        return ['qty1' => $qty1, 'qty2' => $qty2, 'final_qty' => $finalQty];
    }


    // Method untuk menghitung semua dimensions dan quantities
    protected function calculateAllDimensionsAndQuantities(array $data): array
    {
        $calculations = [];

        // Ensure toggles are correctly read from data or livewire properties
        $this->includeBoard = (bool)($data['includeBoard'] ?? $this->includeBoard);
        $this->includeCoverLuar = (bool)($data['includeCoverLuar'] ?? $this->includeCoverLuar);
        $this->includeCoverDalam = (bool)($data['includeCoverDalam'] ?? $this->includeCoverDalam);
        $this->includeCoverLuarLidah = (bool)($data['includeCoverLuarLidah'] ?? $this->includeCoverLuarLidah);
        $this->includeBusa = (bool)($data['includeBusa'] ?? $this->includeBusa);

        // Board Calculations
        if ($this->includeBoard) {
            $selectedItemId = $data['selected_item_board'] ?? null;
            $boardPanjangKertas = 0.0;
            $boardLebarKertas = 0.0;

            if ($selectedItemId) {
                $item = ProductionItem::find($selectedItemId);
                if ($item) {
                    $boardPanjangKertas = (float)($item->panjang_kertas ?? 0);
                    $boardLebarKertas = (float)($item->lebar_kertas ?? 0);
                }
            }
            $calculations['board_panjang_kertas'] = $boardPanjangKertas;
            $calculations['board_lebar_kertas'] = $boardLebarKertas;

            $calculations['board_panjang_atas'] = $this->calculateBoard(
                $data['atas_panjang'] ?? null,
                $data['atas_lebar'] ?? null,
                $data['atas_tinggi'] ?? null
            );
            $calculations['board_panjang_bawah'] = $this->calculateBoard(
                $data['bawah_panjang'] ?? null,
                $data['bawah_lebar'] ?? null,
                $data['bawah_tinggi'] ?? null
            );
            $calculations['board_lebar_atas'] = $this->calculateBoard( // Note: lebar uses same formula as panjang based on provided code
                $data['atas_lebar'] ?? null,    // panjang argument in calculateBoard
                $data['atas_panjang'] ?? null,  // lebar argument in calculateBoard (unused by current formula)
                $data['atas_tinggi'] ?? null   // tinggi argument in calculateBoard
            );
            $calculations['board_lebar_bawah'] = $this->calculateBoard(
                $data['bawah_lebar'] ?? null,
                $data['bawah_panjang'] ?? null,
                $data['bawah_tinggi'] ?? null
            );

            $board_qty_atas = $this->calculateQuantities(
                $calculations['board_panjang_atas'],
                $calculations['board_lebar_atas'],
                $boardPanjangKertas,
                $boardLebarKertas
            );
            $board_qty_bawah = $this->calculateQuantities(
                $calculations['board_panjang_bawah'],
                $calculations['board_lebar_bawah'],
                $boardPanjangKertas,
                $boardLebarKertas
            );

            $calculations['qty1_board_atas'] = $board_qty_atas['qty1'];
            $calculations['qty2_board_atas'] = $board_qty_atas['qty2'];
            $calculations['final_qty_board_atas'] = $board_qty_atas['final_qty'];
            $calculations['qty1_board_bawah'] = $board_qty_bawah['qty1'];
            $calculations['qty2_board_bawah'] = $board_qty_bawah['qty2'];
            $calculations['final_qty_board_bawah'] = $board_qty_bawah['final_qty'];
        }


        // Cover Luar Calculations
        if ($this->includeCoverLuar) {
            $selectedItemId = $data['selected_item_cover_luar'] ?? null;
            $coverLuarPanjangKertas = 0.0;
            $coverLuarLebarKertas = 0.0;

            if ($selectedItemId) {
                $item = ProductionItem::find($selectedItemId);
                if ($item) {
                    $coverLuarPanjangKertas = (float)($item->panjang_kertas ?? 0);
                    $coverLuarLebarKertas = (float)($item->lebar_kertas ?? 0);
                }
            }
            $calculations['cover_luar_panjang_kertas'] = $coverLuarPanjangKertas;
            $calculations['cover_luar_lebar_kertas'] = $coverLuarLebarKertas;

            $calculations['cover_luar_panjang_box_bawah'] = (2 * ((float)($data['bawah_tinggi'] ?? 0))) + ((float)($data['bawah_panjang'] ?? 0)) + 3 + 4;
            $calculations['cover_luar_panjang_box_atas'] = (2 * ((float)($data['atas_tinggi'] ?? 0))) + ((float)($data['atas_panjang'] ?? 0)) + 3 + 4;
            $calculations['cover_luar_lebar_box_bawah'] = (2 * ((float)($data['bawah_tinggi'] ?? 0))) + ((float)($data['bawah_lebar'] ?? 0)) + 3 + 4;
            $calculations['cover_luar_lebar_box_atas'] = (2 * ((float)($data['atas_tinggi'] ?? 0))) + ((float)($data['atas_lebar'] ?? 0)) + 3 + 4;

            $cover_luar_qty_atas = $this->calculateQuantities(
                $calculations['cover_luar_panjang_box_atas'],
                $calculations['cover_luar_lebar_box_atas'],
                $coverLuarPanjangKertas,
                $coverLuarLebarKertas
            );
            $cover_luar_qty_bawah = $this->calculateQuantities(
                $calculations['cover_luar_panjang_box_bawah'],
                $calculations['cover_luar_lebar_box_bawah'],
                $coverLuarPanjangKertas,
                $coverLuarLebarKertas
            );
            $calculations['qty1_cover_luar_atas'] = $cover_luar_qty_atas['qty1'];
            $calculations['qty2_cover_luar_atas'] = $cover_luar_qty_atas['qty2'];
            $calculations['final_qty_cover_luar_atas'] = $cover_luar_qty_atas['final_qty'];
            $calculations['qty1_cover_luar_bawah'] = $cover_luar_qty_bawah['qty1'];
            $calculations['qty2_cover_luar_bawah'] = $cover_luar_qty_bawah['qty2'];
            $calculations['final_qty_cover_luar_bawah'] = $cover_luar_qty_bawah['final_qty'];
        }

        // Cover Dalam Calculations
        if ($this->includeCoverDalam) {
            $selectedItemId = $data['selected_item_cover_dalam'] ?? null;
            $coverDalamPanjangKertas = 0.0;
            $coverDalamLebarKertas = 0.0;

            if ($selectedItemId) {
                $item = ProductionItem::find($selectedItemId);
                if ($item) {
                    $coverDalamPanjangKertas = (float)($item->panjang_kertas ?? 0);
                    $coverDalamLebarKertas = (float)($item->lebar_kertas ?? 0);
                }
            }
            $calculations['cover_dalam_panjang_kertas'] = $coverDalamPanjangKertas;
            $calculations['cover_dalam_lebar_kertas'] = $coverDalamLebarKertas;

            $calculations['cover_dalam_panjang_box_bawah'] = (2 * ((float)($data['bawah_tinggi'] ?? 0))) + ((float)($data['bawah_panjang'] ?? 0)) + 3;
            $calculations['cover_dalam_panjang_box_atas'] = (2 * ((float)($data['atas_tinggi'] ?? 0))) + ((float)($data['atas_panjang'] ?? 0)) + 3;
            $calculations['cover_dalam_lebar_box_bawah'] = (2 * ((float)($data['bawah_tinggi'] ?? 0))) + ((float)($data['bawah_lebar'] ?? 0)) + 3;
            $calculations['cover_dalam_lebar_box_atas'] = (2 * ((float)($data['atas_tinggi'] ?? 0))) + ((float)($data['atas_lebar'] ?? 0)) + 3;

            $cover_dalam_qty_atas = $this->calculateQuantities(
                $calculations['cover_dalam_panjang_box_atas'],
                $calculations['cover_dalam_lebar_box_atas'],
                $coverDalamPanjangKertas,
                $coverDalamLebarKertas
            );
            $cover_dalam_qty_bawah = $this->calculateQuantities(
                $calculations['cover_dalam_panjang_box_bawah'],
                $calculations['cover_dalam_lebar_box_bawah'],
                $coverDalamPanjangKertas,
                $coverDalamLebarKertas
            );
            $calculations['qty1_cover_dalam_atas'] = $cover_dalam_qty_atas['qty1'];
            $calculations['qty2_cover_dalam_atas'] = $cover_dalam_qty_atas['qty2'];
            $calculations['final_qty_cover_dalam_atas'] = $cover_dalam_qty_atas['final_qty'];
            $calculations['qty1_cover_dalam_bawah'] = $cover_dalam_qty_bawah['qty1'];
            $calculations['qty2_cover_dalam_bawah'] = $cover_dalam_qty_bawah['qty2'];
            $calculations['final_qty_cover_dalam_bawah'] = $cover_dalam_qty_bawah['final_qty'];
        }

        // Cover Luar Lidah Calculations
        if ($this->includeCoverLuarLidah) {
            $selectedItemId = $data['selected_item_cover_luar_lidah'] ?? null;
            $coverLuarLidahPanjangKertas = 0.0;
            $coverLuarLidahLebarKertas = 0.0;

            if ($selectedItemId) {
                $item = ProductionItem::find($selectedItemId);
                if ($item) {
                    $coverLuarLidahPanjangKertas = (float)($item->panjang_kertas ?? 0);
                    $coverLuarLidahLebarKertas = (float)($item->lebar_kertas ?? 0);
                }
            }
            $calculations['cover_luar_lidah_panjang_kertas'] = $coverLuarLidahPanjangKertas;
            $calculations['cover_luar_lidah_lebar_kertas'] = $coverLuarLidahLebarKertas;

            $calculations['cover_luar_lidah_panjang_box_bawah'] = (2 * ((float)($data['bawah_tinggi'] ?? 0))) + ((float)($data['bawah_panjang'] ?? 0)) + 3 + 4;
            $calculations['cover_luar_lidah_panjang_box_atas'] = (2 * ((float)($data['atas_tinggi'] ?? 0))) + ((float)($data['atas_panjang'] ?? 0)) + 3 + 4;
            $calculations['cover_luar_lidah_lebar_box_bawah'] = (2 * ((float)($data['bawah_tinggi'] ?? 0))) + ((float)($data['bawah_lebar'] ?? 0)) + 3 + 4;
            $calculations['cover_luar_lidah_lebar_box_atas'] = (2 * ((float)($data['atas_tinggi'] ?? 0))) + ((float)($data['atas_lebar'] ?? 0)) + 3 + 4;

            $cover_luar_lidah_qty_atas = $this->calculateQuantities(
                $calculations['cover_luar_lidah_panjang_box_atas'],
                $calculations['cover_luar_lidah_lebar_box_atas'],
                $coverLuarLidahPanjangKertas,
                $coverLuarLidahLebarKertas
            );
            $cover_luar_lidah_qty_bawah = $this->calculateQuantities(
                $calculations['cover_luar_lidah_panjang_box_bawah'],
                $calculations['cover_luar_lidah_lebar_box_bawah'],
                $coverLuarLidahPanjangKertas,
                $coverLuarLidahLebarKertas
            );
            $calculations['qty1_cover_luar_lidah_atas'] = $cover_luar_lidah_qty_atas['qty1'];
            $calculations['qty2_cover_luar_lidah_atas'] = $cover_luar_lidah_qty_atas['qty2'];
            $calculations['final_qty_cover_luar_lidah_atas'] = $cover_luar_lidah_qty_atas['final_qty'];
            $calculations['qty1_cover_luar_lidah_bawah'] = $cover_luar_lidah_qty_bawah['qty1'];
            $calculations['qty2_cover_luar_lidah_bawah'] = $cover_luar_lidah_qty_bawah['qty2'];
            $calculations['final_qty_cover_luar_lidah_bawah'] = $cover_luar_lidah_qty_bawah['final_qty'];
        }


        // Busa Calculations
        if ($this->includeBusa) {
            $selectedItemId = $data['selected_item_busa'] ?? null;
            $busaPanjangKertas = 0.0; // Note: "Kertas" in name is generic, represents material dimensions
            $busaLebarKertas = 0.0;

            if ($selectedItemId) {
                $item = ProductionItem::find($selectedItemId);
                if ($item) {
                    $busaPanjangKertas = (float)($item->panjang_kertas ?? 0);
                    $busaLebarKertas = (float)($item->lebar_kertas ?? 0);
                }
            }
            $calculations['busa_panjang_kertas'] = $busaPanjangKertas;
            $calculations['busa_lebar_kertas'] = $busaLebarKertas;

            $calculations['panjang_busa'] = ((float)($data['bawah_panjang'] ?? 0)) + 3;
            $calculations['lebar_busa'] = ((float)($data['bawah_lebar'] ?? 0)) + 3;

            $busa_qty = $this->calculateQuantities(
                $calculations['panjang_busa'],
                $calculations['lebar_busa'],
                $busaPanjangKertas,
                $busaLebarKertas
            );
            $calculations['qty1_busa'] = $busa_qty['qty1'];
            $calculations['qty2_busa'] = $busa_qty['qty2'];
            $calculations['final_qty_busa'] = $busa_qty['final_qty'];
        }

        return $calculations;
    }

    public function updateBoardCalculations(): void
    {
        $data = $this->form->getState();
        $calculations = $this->calculateAllDimensionsAndQuantities($data);
        // Ensure existing data isn't lost when filling, especially from other sections
        $this->form->fill(array_merge($data, $calculations));
    }


    public function form(Form $form): Form
    {
        // Helper function untuk membuat field dimensi
        $createDimensionField = function (string $name, string $label): TextInput {
            return TextInput::make($name)
                ->label($label)
                ->numeric()
                ->nullable()
                ->suffix('cm')
                ->minValue(0)
                ->step(0.1)
                ->placeholder('0.0')
                ->live(onBlur: true) // Using onBlur for live to avoid too many updates
                ->afterStateUpdated(function ($livewire) {
                    $livewire->updateBoardCalculations();
                })
                ->columnSpan(1);
        };

        $createDimensionSection = function (string $title, string $prefix) use ($createDimensionField): Section {
            return Section::make($title)
                ->description("Masukkan dimensi {$title} dalam satuan centimeter")
                ->schema([
                    $createDimensionField("{$prefix}_panjang", 'Panjang'),
                    $createDimensionField("{$prefix}_lebar", 'Lebar'),
                    $createDimensionField("{$prefix}_tinggi", 'Tinggi'),
                ])
                ->columns(3)
                ->collapsible()
                ->icon('heroicon-o-cube');
        };

        // Section statis (informasi produk)
        $sections = [
            Forms\Components\Section::make('Informasi Produk')
                ->schema([
                    Forms\Components\TextInput::make('product_name')
                        ->label('Nama Produk')
                        ->required()
                        ->live(onBlur: true)
                        ->columnSpan(1),

                    Forms\Components\Select::make('size')
                        ->label('Ukuran')
                        ->options(MasterCost::pluck('size', 'size')->toArray())
                        ->required()
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\Select::make('poly_dimension')
                        ->label('Dimensi Poly')
                        ->options(fn () => PolyCost::all()->pluck('dimension', 'dimension')) // Corrected PolyCost model reference
                        ->nullable()
                        ->live()
                        ->columnSpan(1),

                    Forms\Components\Select::make('include_knife_cost')
                        ->label('Termasuk Ongkos Pisau')
                        ->options([
                            'ada' => 'Ada',
                            'tidak_ada' => 'Tidak Ada',
                        ])
                        ->required()
                        ->default('tidak_ada')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Jumlah')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($livewire) {
                            $livewire->updateBoardCalculations();
                        })
                        ->columnSpan(1),
                ])
                ->columns(5), // Adjusted to 5 for better layout or as per original design
        ];

        // Section Dimensi Box
        $sections[] = Forms\Components\Grid::make(2)
            ->schema([
                $createDimensionSection('Dimensi Box Atas', 'atas')->columnSpan(1),
                $createDimensionSection('Dimensi Box Bawah', 'bawah')->columnSpan(1),
            ]);


        // Section for Component Toggles and Item Selection
        $sections[] = Forms\Components\Section::make('Pilihan Komponen')
            ->schema([
                Forms\Components\Toggle::make('includeBoard')
                    ->label('Sertakan Board')
                    ->live()
                    ->afterStateUpdated(function ($livewire, Forms\Set $set, Forms\Get $get) {
                        if (!$get('includeBoard')) { // Clear related fields if unchecked
                            $fieldsToClear = ['selected_item_board', 'board_panjang_atas', 'board_lebar_atas', 'board_panjang_bawah', 'board_lebar_bawah', 'board_panjang_kertas', 'board_lebar_kertas', 'qty1_board_atas', 'qty2_board_atas', 'final_qty_board_atas', 'qty1_board_bawah', 'qty2_board_bawah', 'final_qty_board_bawah'];
                            foreach ($fieldsToClear as $field) $set($field, null);
                        }
                        $livewire->updateBoardCalculations();
                    }),
                Forms\Components\Toggle::make('includeCoverLuar')
                    ->label('Sertakan Cover Luar')
                    ->live()
                    ->afterStateUpdated(function ($livewire, Forms\Set $set, Forms\Get $get) {
                         if (!$get('includeCoverLuar')) {
                            $fieldsToClear = ['selected_item_cover_luar', 'cover_luar_panjang_box_atas', 'cover_luar_lebar_box_atas', 'cover_luar_panjang_box_bawah', 'cover_luar_lebar_box_bawah', 'cover_luar_panjang_kertas', 'cover_luar_lebar_kertas', 'qty1_cover_luar_atas', 'qty2_cover_luar_atas', 'final_qty_cover_luar_atas', 'qty1_cover_luar_bawah', 'qty2_cover_luar_bawah', 'final_qty_cover_luar_bawah'];
                            foreach ($fieldsToClear as $field) $set($field, null);
                        }
                        $livewire->updateBoardCalculations();
                    }),
                Forms\Components\Toggle::make('includeCoverDalam')
                    ->label('Sertakan Cover Dalam')
                    ->live()
                    ->afterStateUpdated(function ($livewire, Forms\Set $set, Forms\Get $get) {
                        if (!$get('includeCoverDalam')) {
                            $fieldsToClear = ['selected_item_cover_dalam', 'cover_dalam_panjang_box_atas', 'cover_dalam_lebar_box_atas', 'cover_dalam_panjang_box_bawah', 'cover_dalam_lebar_box_bawah', 'cover_dalam_panjang_kertas', 'cover_dalam_lebar_kertas', 'qty1_cover_dalam_atas', 'qty2_cover_dalam_atas', 'final_qty_cover_dalam_atas', 'qty1_cover_dalam_bawah', 'qty2_cover_dalam_bawah', 'final_qty_cover_dalam_bawah'];
                            foreach ($fieldsToClear as $field) $set($field, null);
                        }
                        $livewire->updateBoardCalculations();
                    }),
                Forms\Components\Toggle::make('includeCoverLuarLidah')
                    ->label('Sertakan Cover Luar Lidah')
                    ->live()
                    ->afterStateUpdated(function ($livewire, Forms\Set $set, Forms\Get $get) {
                        if (!$get('includeCoverLuarLidah')) {
                            $fieldsToClear = ['selected_item_cover_luar_lidah', 'cover_luar_lidah_panjang_box_atas', 'cover_luar_lidah_lebar_box_atas', 'cover_luar_lidah_panjang_box_bawah', 'cover_luar_lidah_lebar_box_bawah', 'cover_luar_lidah_panjang_kertas', 'cover_luar_lidah_lebar_kertas', 'qty1_cover_luar_lidah_atas', 'qty2_cover_luar_lidah_atas', 'final_qty_cover_luar_lidah_atas', 'qty1_cover_luar_lidah_bawah', 'qty2_cover_luar_lidah_bawah', 'final_qty_cover_luar_lidah_bawah'];
                            foreach ($fieldsToClear as $field) $set($field, null);
                        }
                        $livewire->updateBoardCalculations();
                    }),
                Forms\Components\Toggle::make('includeBusa')
                    ->label('Sertakan Busa')
                    ->live()
                    ->afterStateUpdated(function ($livewire, Forms\Set $set, Forms\Get $get) {
                        if (!$get('includeBusa')) {
                            $fieldsToClear = ['selected_item_busa', 'panjang_busa', 'lebar_busa', 'busa_panjang_kertas', 'busa_lebar_kertas', 'qty1_busa', 'qty2_busa', 'final_qty_busa'];
                            foreach ($fieldsToClear as $field) $set($field, null);
                        }
                        $livewire->updateBoardCalculations();
                    }),

                // Conditional Selects for each component type
                Forms\Components\Select::make('selected_item_board')
                    ->label('Pilih Item Board')
                    ->options(ProductionItem::whereHas('category', fn ($q) => $q->where('name', 'Board'))->pluck('name', 'id'))
                    ->nullable()
                    ->live()
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeBoard'))
                    ->afterStateUpdated(fn ($livewire) => $livewire->updateBoardCalculations()),

                Forms\Components\Select::make('selected_item_cover_luar')
                    ->label('Pilih Item Cover Luar')
                    ->options(ProductionItem::whereHas('category', fn ($q) => $q->where('name', 'Cover Luar'))->pluck('name', 'id'))
                    ->nullable()
                    ->live()
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverLuar'))
                    ->afterStateUpdated(fn ($livewire) => $livewire->updateBoardCalculations()),

                Forms\Components\Select::make('selected_item_cover_dalam')
                    ->label('Pilih Item Cover Dalam')
                    ->options(ProductionItem::whereHas('category', fn ($q) => $q->where('name', 'Cover Dalam'))->pluck('name', 'id'))
                    ->nullable()
                    ->live()
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverDalam'))
                    ->afterStateUpdated(fn ($livewire) => $livewire->updateBoardCalculations()),

                Forms\Components\Select::make('selected_item_cover_luar_lidah')
                    ->label('Pilih Item Cover Luar Lidah')
                    ->options(ProductionItem::whereHas('category', fn ($q) => $q->where('name', 'Cover Luar Lidah'))->pluck('name', 'id'))
                    ->nullable()
                    ->live()
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverLuarLidah'))
                    ->afterStateUpdated(fn ($livewire) => $livewire->updateBoardCalculations()),

                Forms\Components\Select::make('selected_item_busa')
                    ->label('Pilih Item Busa')
                    ->options(ProductionItem::whereHas('category', fn ($q) => $q->where('name', 'Busa'))->pluck('name', 'id'))
                    ->nullable()
                    ->live()
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeBusa'))
                    ->afterStateUpdated(fn ($livewire) => $livewire->updateBoardCalculations()),
            ])
            ->columns(2) // Toggles on one side, Selects on the other if more space needed, or adjust as per design
            ->collapsible()
            ->icon('heroicon-o-adjustments-horizontal');


        // Section Hasil Perhitungan Dimensi Komponen
        $sections[] = Forms\Components\Section::make('Hasil Perhitungan Dimensi Komponen')
            ->schema([
                Forms\Components\Fieldset::make('Dimensi Board')
                    ->schema([
                        Forms\Components\TextInput::make('board_panjang_atas')
                            ->label('Board Panjang Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('board_lebar_atas')
                            ->label('Board Lebar Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('board_panjang_bawah')
                            ->label('Board Panjang Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('board_lebar_bawah')
                            ->label('Board Lebar Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeBoard')),

                Forms\Components\Fieldset::make('Dimensi Cover Luar')
                    ->schema([
                        Forms\Components\TextInput::make('cover_luar_panjang_box_atas')
                            ->label('Cover Luar Panjang Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lebar_box_atas')
                            ->label('Cover Luar Lebar Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_luar_panjang_box_bawah')
                            ->label('Cover Luar Panjang Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lebar_box_bawah')
                            ->label('Cover Luar Lebar Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverLuar')),
                
                Forms\Components\Fieldset::make('Dimensi Cover Dalam')
                    ->schema([
                        Forms\Components\TextInput::make('cover_dalam_panjang_box_atas')
                            ->label('Cover Dalam Panjang Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_dalam_lebar_box_atas')
                            ->label('Cover Dalam Lebar Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_dalam_panjang_box_bawah')
                            ->label('Cover Dalam Panjang Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_dalam_lebar_box_bawah')
                            ->label('Cover Dalam Lebar Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverDalam')),

                Forms\Components\Fieldset::make('Dimensi Cover Luar Lidah')
                    ->schema([
                        Forms\Components\TextInput::make('cover_luar_lidah_panjang_box_atas')
                            ->label('Cover Luar Lidah Panjang Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lidah_lebar_box_atas')
                            ->label('Cover Luar Lidah Lebar Atas')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lidah_panjang_box_bawah')
                            ->label('Cover Luar Lidah Panjang Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lidah_lebar_box_bawah')
                            ->label('Cover Luar Lidah Lebar Bawah')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverLuarLidah')),

                Forms\Components\Fieldset::make('Dimensi Busa')
                    ->schema([
                        Forms\Components\TextInput::make('panjang_busa')
                            ->label('Panjang Busa')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('lebar_busa')
                            ->label('Lebar Busa')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeBusa')),
            ])
            ->columns(1) // Each fieldset will take full width, then its internal columns(2) will apply
            ->collapsible()
            ->icon('heroicon-o-clipboard-document-list');


        // New Section for Quantity Calculations with specific paper dimensions
        // This is where your snippet left off.
        $sections[] = Forms\Components\Section::make('Hasil Perhitungan Kuantitas')
            ->schema([
                // Kuantitas Board Fieldset
                Forms\Components\Fieldset::make('Kuantitas Board')
                    ->schema([
                        Forms\Components\TextInput::make('board_panjang_kertas')
                            ->label('Panjang Kertas (Board)')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('board_lebar_kertas')
                            ->label('Lebar Kertas (Board)')
                            ->disabled()
                            ->suffix('cm')
                            ->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('qty1_board_atas')
                            ->label('Qty 1 Board Atas')
                            ->disabled()
                            ->suffix('pcs')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_board_atas')
                            ->label('Qty 2 Board Atas')
                            ->disabled()
                            ->suffix('pcs')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_board_atas')
                            ->label('Final Qty Board Atas')
                            ->disabled()
                            ->suffix('pcs')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty1_board_bawah')
                            ->label('Qty 1 Board Bawah')
                            ->disabled()
                            ->suffix('pcs')
                            ->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_board_bawah')
                            ->label('Qty 2 Board Bawah')
                            ->disabled()
                            ->suffix('pcs')
                            ->placeholder('Akan dihitung otomatis'), // This was the last line in your snippet
                        Forms\Components\TextInput::make('final_qty_board_bawah') // Added missing field
                            ->label('Final Qty Board Bawah')
                            ->disabled()
                            ->suffix('pcs')
                            ->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeBoard')),

                // Kuantitas Cover Luar Fieldset
                Forms\Components\Fieldset::make('Kuantitas Cover Luar')
                    ->schema([
                        Forms\Components\TextInput::make('cover_luar_panjang_kertas')
                            ->label('Panjang Kertas (Cover Luar)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lebar_kertas')
                            ->label('Lebar Kertas (Cover Luar)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('qty1_cover_luar_atas')
                            ->label('Qty 1 Cover Luar Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_cover_luar_atas')
                            ->label('Qty 2 Cover Luar Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_cover_luar_atas')
                            ->label('Final Qty Cover Luar Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty1_cover_luar_bawah')
                            ->label('Qty 1 Cover Luar Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_cover_luar_bawah')
                            ->label('Qty 2 Cover Luar Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_cover_luar_bawah')
                            ->label('Final Qty Cover Luar Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverLuar')),

                // Kuantitas Cover Dalam Fieldset
                Forms\Components\Fieldset::make('Kuantitas Cover Dalam')
                    ->schema([
                        Forms\Components\TextInput::make('cover_dalam_panjang_kertas')
                            ->label('Panjang Kertas (Cover Dalam)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('cover_dalam_lebar_kertas')
                            ->label('Lebar Kertas (Cover Dalam)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('qty1_cover_dalam_atas')
                            ->label('Qty 1 Cover Dalam Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_cover_dalam_atas')
                            ->label('Qty 2 Cover Dalam Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_cover_dalam_atas')
                            ->label('Final Qty Cover Dalam Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty1_cover_dalam_bawah')
                            ->label('Qty 1 Cover Dalam Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_cover_dalam_bawah')
                            ->label('Qty 2 Cover Dalam Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_cover_dalam_bawah')
                            ->label('Final Qty Cover Dalam Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverDalam')),

                // Kuantitas Cover Luar Lidah Fieldset
                Forms\Components\Fieldset::make('Kuantitas Cover Luar Lidah')
                    ->schema([
                        Forms\Components\TextInput::make('cover_luar_lidah_panjang_kertas')
                            ->label('Panjang Kertas (Cover Luar Lidah)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('cover_luar_lidah_lebar_kertas')
                            ->label('Lebar Kertas (Cover Luar Lidah)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('qty1_cover_luar_lidah_atas')
                            ->label('Qty 1 Cover Luar Lidah Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_cover_luar_lidah_atas')
                            ->label('Qty 2 Cover Luar Lidah Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_cover_luar_lidah_atas')
                            ->label('Final Qty Cover Luar Lidah Atas')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty1_cover_luar_lidah_bawah')
                            ->label('Qty 1 Cover Luar Lidah Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_cover_luar_lidah_bawah')
                            ->label('Qty 2 Cover Luar Lidah Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_cover_luar_lidah_bawah')
                            ->label('Final Qty Cover Luar Lidah Bawah')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeCoverLuarLidah')),

                // Kuantitas Busa Fieldset
                Forms\Components\Fieldset::make('Kuantitas Busa')
                    ->schema([
                        Forms\Components\TextInput::make('busa_panjang_kertas') // 'kertas' here refers to the raw material sheet
                            ->label('Panjang Material (Busa)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('busa_lebar_kertas')
                            ->label('Lebar Material (Busa)')
                            ->disabled()->suffix('cm')->placeholder('Akan diisi otomatis'),
                        Forms\Components\TextInput::make('qty1_busa')
                            ->label('Qty 1 Busa')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('qty2_busa')
                            ->label('Qty 2 Busa')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                        Forms\Components\TextInput::make('final_qty_busa')
                            ->label('Final Qty Busa')
                            ->disabled()->suffix('pcs')->placeholder('Akan dihitung otomatis'),
                    ])
                    ->columns(2) // Will result in 3 rows, one field might be alone or adjust to columns(1) for 5 rows
                    ->hidden(fn (Forms\Get $get): bool => !$get('includeBusa')),
            ])
            ->columns(1) // Each fieldset takes full width, then its internal columns(2) applies
            ->collapsible()
            ->icon('heroicon-o-view-columns'); // Changed icon for variety, use what fits best


        return $form
            ->schema($sections)
            ->statePath('data');
    }

    // Placeholder for the actual calculation logic that uses the quantities and prices
    public function calculate(): void
    {
        $data = $this->form->getState();
        // Perform final cost calculation based on $data, selected items, quantities, etc.
        // This will involve fetching prices for selected items, applying logic from MasterCost, PolyCost etc.
        // For example:
        // $totalCost = 0;
        // $quantity = (int)($data['quantity'] ?? 1);

        // if ($data['includeBoard'] && isset($data['selected_item_board'])) {
        //     $item = ProductionItem::find($data['selected_item_board']);
        //     if ($item) {
        //          $pricePerUnitMaterialAtas = $this->getItemPriceByQuantity($item, $quantity);
        //          $finalQtyAtas = $data['final_qty_board_atas'] ?? 0;
        //          if ($finalQtyAtas > 0) {
        //             $totalCost += ($pricePerUnitMaterialAtas / $finalQtyAtas) * $quantity;
        //          }
        //          // Add logic for bawah part, etc.
        //     }
        // }
        // ... and so on for other components ...

        // $masterCost = MasterCost::where('size', $data['size'])->first();
        // if ($masterCost) {
        //    $totalCost += $masterCost->cost_per_unit; // Or other logic
        // }
        // if ($data['poly_dimension']) {
        //    $polyCost = PolyCost::where('dimension', $data['poly_dimension'])->first();
        //    if ($polyCost) {
        //       $totalCost += $polyCost->cost;
        //    }
        // }

        // This is highly dependent on your specific pricing rules.
        // $this->calculationResult = $totalCost; // Store or display the result

        Notification::make()
            ->title('Perhitungan Harga Selesai (Logika Belum Diimplementasikan Sepenuhnya)')
            ->success()
            ->send();
    }

    // Placeholder for saving the calculation
    public function saveCalculation(): void
    {
        $data = $this->form->getState();
        // Logic to save $data and $this->calculationResult to PriceCalculation model or similar
        // PriceCalculation::create([...]);
        Notification::make()
            ->title('Kalkulasi Disimpan (Logika Belum Diimplementasikan)')
            ->success()
            ->send();
    }
}