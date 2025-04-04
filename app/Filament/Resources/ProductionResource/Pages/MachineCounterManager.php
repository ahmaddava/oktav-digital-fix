<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use App\Filament\Resources\ProductionResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Models\Production;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MachineCounterManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProductionResource::class;
    protected static string $view = 'filament.resources.production-resource.pages.machine-counter-manager';
    protected static ?string $title = 'Pengaturan Counter Mesin';
    
    // Data model untuk form
    public ?array $data = [];
    
    public function mount(): void
    {
        // Inisialisasi data
        $this->refreshCounterData();
        $this->form->fill($this->data);
    }
    
    public function refreshCounterData(): void
    {
        // Dapatkan total counter untuk kedua mesin - hanya dari produksi normal (non-adjustment)
        $normalCounters = DB::table('productions')
            ->selectRaw('machine_type, SUM(total_counter) as total')
            ->where(function($query) {
                $query->where('is_adjustment', 0)
                    ->orWhereNull('is_adjustment');
            })
            ->groupBy('machine_type')
            ->get()
            ->keyBy('machine_type')
            ->map(fn($item) => $item->total ?? 0)
            ->toArray();
        
        // Dapatkan adjustment counter terakhir untuk setiap mesin
        $adjustments = DB::table('productions')
            ->where('is_adjustment', 1)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('machine_type')
            ->map(function ($items) {
                // Ambil adjustment terakhir untuk mesin ini
                return $items->first()->adjustment_value ?? 0;
            })
            ->toArray();
        
        // Hitung total counter saat ini untuk tiap mesin (produksi normal + adjustment)
        $mesin1Total = ($normalCounters['mesin_1'] ?? 0) + ($adjustments['mesin_1'] ?? 0);
        $mesin2Total = ($normalCounters['mesin_2'] ?? 0) + ($adjustments['mesin_2'] ?? 0);
        
        // Set data untuk form
        $this->data = [
            'mesin_1_current' => $mesin1Total,
            'mesin_2_current' => $mesin2Total,
            'mesin_1_new' => $mesin1Total,
            'mesin_2_new' => $mesin2Total,
        ];
        
        // Log data untuk debugging
        Log::info('Normal counters: ' . json_encode($normalCounters));
        Log::info('Adjustments: ' . json_encode($adjustments));
        Log::info('Total counters: M1=' . $mesin1Total . ', M2=' . $mesin2Total);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Status Counter Saat Ini')
                    ->description('Nilai counter yang tersimpan di database')
                    ->schema([
                        TextInput::make('mesin_1_current')
                            ->label('Mesin 1')
                            ->disabled()
                            ->numeric(),
                        
                        TextInput::make('mesin_2_current')
                            ->label('Mesin 2')
                            ->disabled()
                            ->numeric(),
                    ])
                    ->columns(2),
                
                Section::make('Update Counter Mesin')
                    ->description('Masukkan nilai counter sesuai dengan angka pada mesin fisik')
                    ->schema([
                        TextInput::make('mesin_1_new')
                            ->label('Mesin 1 - Nilai Baru')
                            ->required()
                            ->numeric()
                            ->helperText('Masukkan nilai aktual pada counter mesin 1'),
                        
                        TextInput::make('mesin_2_new')
                            ->label('Mesin 2 - Nilai Baru')
                            ->required()
                            ->numeric()
                            ->helperText('Masukkan nilai aktual pada counter mesin 2'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }
    
    public function updateCounters(): void
    {
        try {
            // Begin transaction
            DB::beginTransaction();
            
            // Dapatkan dan validasi nilai dari form
            $data = $this->form->getState();
            
            // Dapatkan daftar kolom yang ada di tabel productions
            $columns = DB::getSchemaBuilder()->getColumnListing('productions');
            
            // Ambil nilai dari state
            $mesin1Current = intval($data['mesin_1_current'] ?? 0);
            $mesin1New = intval($data['mesin_1_new'] ?? 0);
            $mesin2Current = intval($data['mesin_2_current'] ?? 0);
            $mesin2New = intval($data['mesin_2_new'] ?? 0);
            
            // Proses masing-masing mesin
            $changes = false;
            
            // Hanya update jika ada perubahan nilai
            if ($mesin1New !== $mesin1Current) {
                $this->setTotalCounter('mesin_1', $mesin1New, $columns);
                $changes = true;
            }
            
            if ($mesin2New !== $mesin2Current) {
                $this->setTotalCounter('mesin_2', $mesin2New, $columns);
                $changes = true;
            }
            
            // Commit transaction
            DB::commit();
            
            // Tampilkan notifikasi berdasarkan apakah ada perubahan atau tidak
            if ($changes) {
                Notification::make()
                    ->title('Counter Berhasil Diperbarui')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Tidak Ada Perubahan')
                    ->body('Nilai counter baru sama dengan nilai saat ini')
                    ->info()
                    ->send();
            }
            
            // Refresh data
            $this->refreshCounterData();
            $this->form->fill($this->data);
            
            // Refresh widget counter
            $this->dispatch('refresh-stats');
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();
            
            // Log error
            Log::error('Error updating counters: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            
            // Tampilkan notifikasi error
            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function setTotalCounter(string $machineType, int $newTotalValue, array $columns): void
    {
        // 1. Ambil total counter dari produksi normal (non-adjustment)
        $normalTotal = DB::table('productions')
            ->where('machine_type', $machineType)
            ->where(function($query) {
                $query->where('is_adjustment', 0)
                    ->orWhereNull('is_adjustment');
            })
            ->sum('total_counter');
        
        // 2. Hapus semua adjustment sebelumnya untuk mesin ini
        DB::table('productions')
            ->where('machine_type', $machineType)
            ->where('is_adjustment', 1)
            ->delete();
        
        // 3. Hitung nilai adjustment yang diperlukan untuk mencapai total baru
        $adjustmentValue = $newTotalValue - $normalTotal;
        
        // 4. Siapkan data untuk insert record adjustment baru
        $insertData = [
            'machine_type' => $machineType,
            'status' => 'completed',
            'total_clicks' => 0,
            'total_counter' => $adjustmentValue,
            'notes' => "Adjustment counter mesin $machineType ke nilai $newTotalValue",
            'created_at' => now(),
            'updated_at' => now(),
            'is_adjustment' => 1,
            'adjustment_value' => $adjustmentValue, // Simpan nilai adjustment
        ];
        
        // Tambahkan kolom lain jika ada di tabel
        if (in_array('completed_at', $columns)) {
            $insertData['completed_at'] = now();
        }
        
        if (in_array('failed_prints', $columns)) {
            $insertData['failed_prints'] = 0;
        }
        
        // Jika kolom invoice_id ada di tabel, set nilainya ke null
        if (in_array('invoice_id', $columns)) {
            $insertData['invoice_id'] = null;
        }
        
        // 5. Insert adjustment record baru ke database
        DB::table('productions')->insert($insertData);
        
        Log::info("Set total counter for $machineType to $newTotalValue (normal: $normalTotal, adjustment: $adjustmentValue)");
    }
}