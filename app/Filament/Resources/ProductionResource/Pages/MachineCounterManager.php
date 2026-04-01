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
use App\Models\Machine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MachineCounterManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProductionResource::class;
    protected static string $view = 'filament.resources.production-resource.pages.machine-counter-manager';
    protected static ?string $title = 'Pengaturan Counter Mesin';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->refreshCounterData();
        $this->form->fill($this->data);
    }
    
    public function refreshCounterData(): void
    {
        // Load only machines that use clicks
        $machines = Machine::where('use_clicks', true)->get();

        $data = [];
        
        foreach ($machines as $machine) {
            $key = 'machine_' . $machine->id;
            
            // Normal counter (non-adjustment)
            $normalTotal = DB::table('productions')
                ->where('machine_id', $machine->id)
                ->where(function($query) {
                    $query->where('is_adjustment', 0)
                        ->orWhereNull('is_adjustment');
                })
                ->sum('total_counter');

            // Get last adjustment value
            $adjustment = DB::table('productions')
                ->where('machine_id', $machine->id)
                ->where('is_adjustment', 1)
                ->orderBy('id', 'desc')
                ->first();

            $adjustmentValue = $adjustment->adjustment_value ?? 0;
            $total = $normalTotal + $adjustmentValue;
            
            $data[$key . '_current'] = $total;
            $data[$key . '_new'] = $total;
        }
        
        $this->data = $data;
    }
    
    public function form(Form $form): Form
    {
        $machines = Machine::where('use_clicks', true)->get();
        
        $currentFields = [];
        $updateFields = [];
        
        foreach ($machines as $machine) {
            $key = 'machine_' . $machine->id;
            
            $currentFields[] = TextInput::make($key . '_current')
                ->label($machine->name)
                ->disabled()
                ->numeric();
            
            $updateFields[] = TextInput::make($key . '_new')
                ->label($machine->name . ' - Nilai Baru')
                ->required()
                ->numeric()
                ->helperText('Masukkan nilai aktual pada counter ' . $machine->name);
        }
        
        return $form
            ->schema([
                Section::make('Status Counter Saat Ini')
                    ->description('Nilai counter yang tersimpan di database')
                    ->schema($currentFields)
                    ->columns(min(count($currentFields), 3)),
                
                Section::make('Update Counter Mesin')
                    ->description('Masukkan nilai counter sesuai dengan angka pada mesin fisik')
                    ->schema($updateFields)
                    ->columns(min(count($updateFields), 3)),
            ])
            ->statePath('data');
    }
    
    public function updateCounters(): void
    {
        try {
            DB::beginTransaction();
            
            $data = $this->form->getState();
            $machines = Machine::where('use_clicks', true)->get();
            $columns = DB::getSchemaBuilder()->getColumnListing('productions');
            
            $changes = false;
            
            foreach ($machines as $machine) {
                $key = 'machine_' . $machine->id;
                $current = intval($data[$key . '_current'] ?? 0);
                $new = intval($data[$key . '_new'] ?? 0);
                
                if ($new !== $current) {
                    $this->setTotalCounter($machine->id, $new, $columns);
                    $changes = true;
                }
            }
            
            DB::commit();
            
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
            
            $this->refreshCounterData();
            $this->form->fill($this->data);
            $this->dispatch('refresh-stats');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating counters: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function setTotalCounter(int $machineId, int $newTotalValue, array $columns): void
    {
        $machine = Machine::find($machineId);
        
        // 1. Get normal counter total
        $normalTotal = DB::table('productions')
            ->where('machine_id', $machineId)
            ->where(function($query) {
                $query->where('is_adjustment', 0)
                    ->orWhereNull('is_adjustment');
            })
            ->sum('total_counter');
        
        // 2. Delete previous adjustments for this machine
        DB::table('productions')
            ->where('machine_id', $machineId)
            ->where('is_adjustment', 1)
            ->delete();
        
        // 3. Calculate the adjustment needed
        $adjustmentValue = $newTotalValue - $normalTotal;
        
        // 4. Insert new adjustment record
        $insertData = [
            'machine_id' => $machineId,
            'machine_type' => 'mesin_' . $machineId, // Backward compatibility
            'status' => 'completed',
            'total_clicks' => 0,
            'total_counter' => $adjustmentValue,
            'notes' => "Adjustment counter {$machine->name} ke nilai $newTotalValue",
            'created_at' => now(),
            'updated_at' => now(),
            'is_adjustment' => 1,
            'adjustment_value' => $adjustmentValue,
        ];
        
        if (in_array('completed_at', $columns)) {
            $insertData['completed_at'] = now();
        }
        
        if (in_array('failed_prints', $columns)) {
            $insertData['failed_prints'] = 0;
        }
        
        if (in_array('invoice_id', $columns)) {
            $insertData['invoice_id'] = null;
        }
        
        DB::table('productions')->insert($insertData);
        
        Log::info("Set total counter for {$machine->name} (ID: $machineId) to $newTotalValue (normal: $normalTotal, adjustment: $adjustmentValue)");
    }
}