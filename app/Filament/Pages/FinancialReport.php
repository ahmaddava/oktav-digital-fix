<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class FinancialReport extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Reports';
    protected static string $view = 'filament.pages.financial-report';

    public ?string $selectedDate = null;
    public float $totalIncome = 0;
    public float $totalExpense = 0;
    public float $totalReceivables = 0;
    public float $balance = 0;
    
    // Pagination per page settings
    public int $incomePerPage = 5;
    public int $expensePerPage = 5;

    public function mount(): void
    {
        $this->selectedDate = now()->startOfMonth()->format('Y-m-d');
        $this->calculateStats();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_expense')
                ->label('Tambah Pengeluaran')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->form([
                    TextInput::make('description')
                        ->label('Deskripsi')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->required()
                        ->prefix('Rp')
                        ->placeholder('Contoh: 100.000')
                        ->extraInputAttributes([
                            'x-data' => '{}',
                            'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                            'inputmode' => 'numeric',
                        ])
                        ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0),
                    DatePicker::make('expense_date')
                        ->label('Tanggal Pengeluaran')
                        ->required()
                        ->default(now()),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    Expense::create([
                        'description' => $data['description'],
                        'amount' => $data['amount'],
                        'expense_date' => $data['expense_date'],
                        'notes' => $data['notes'] ?? null,
                    ]);
                    
                    $this->calculateStats();
                    
                    Notification::make()
                        ->title('Pengeluaran berhasil ditambahkan')
                        ->success()
                        ->send();
                })
                ->modalHeading('Tambah Pengeluaran Baru')
                ->modalSubmitActionLabel('Simpan')
                ->modalCancelActionLabel('Batal'),

            Action::make('export')
                ->label('Export ke Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn(): string => route('export.financial-report', [
                    'date' => $this->selectedDate
                ]), shouldOpenInNewTab: true),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('selectedDate')
                ->label('Pilih Bulan')
                ->displayFormat('F Y')
                ->format('Y-m-d')
                ->native(false)
                ->closeOnDateSelection()
                ->default(now()->startOfMonth())
                ->live(),
        ];
    }

    public function updated($propertyName): void
    {
        if ($propertyName === 'selectedDate') {
            $this->calculateStats();
        }
    }

    public function calculateStats(): void
    {
        $date = Carbon::parse($this->selectedDate);
        $year = $date->year;
        $month = $date->month;

        // Query untuk income dari invoice yang sudah dibayar
        $incomeQuery = Invoice::query()
            ->where('status', 'paid')
            ->whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month);

        $expenseQuery = Expense::query()
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month);

        $this->totalIncome = $incomeQuery->sum('grand_total');
        $this->totalExpense = $expenseQuery->sum('amount');
        $this->totalReceivables = Invoice::where('status', 'unpaid')->sum('grand_total');
        $this->balance = $this->totalIncome - $this->totalExpense;
    }

    // Edit expense action
    public function editExpenseAction(): Action
    {
        return Action::make('editExpense')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->size('sm')
            ->form([
                TextInput::make('description')
                    ->label('Deskripsi')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->prefix('Rp')
                    ->placeholder('Contoh: 100.000')
                    ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                    ->extraInputAttributes([
                        'x-data' => '{}',
                        'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                        'inputmode' => 'numeric',
                    ])
                    ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0),
                DatePicker::make('expense_date')
                    ->label('Tanggal Pengeluaran')
                    ->required(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->fillForm(function (array $arguments): array {
                $expense = Expense::find($arguments['expense']);
                return [
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date,
                    'notes' => $expense->notes,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $expense = Expense::find($arguments['expense']);
                $expense->update([
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'expense_date' => $data['expense_date'],
                    'notes' => $data['notes'] ?? null,
                ]);
                
                $this->calculateStats();
                
                Notification::make()
                    ->title('Pengeluaran berhasil diupdate')
                    ->success()
                    ->send();
            })
            ->modalHeading('Edit Pengeluaran')
            ->modalSubmitActionLabel('Update')
            ->modalCancelActionLabel('Batal');
    }

    // Delete expense action
    public function deleteExpenseAction(): Action
    {
        return Action::make('deleteExpense')
            ->label('Hapus')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Hapus Pengeluaran')
            ->modalDescription('Apakah Anda yakin ingin menghapus pengeluaran ini? Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->modalCancelActionLabel('Batal')
            ->action(function (array $arguments): void {
                $expense = Expense::find($arguments['expense']);
                $expense->delete();
                
                $this->calculateStats();
                
                Notification::make()
                    ->title('Pengeluaran berhasil dihapus')
                    ->success()
                    ->send();
            });
    }

    // View expense action
    public function viewExpenseAction(): Action
    {
        return Action::make('viewExpense')
            ->label('Detail')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->size('sm')
            ->modalContent(function (array $arguments): \Illuminate\Contracts\View\View {
                $expense = Expense::find($arguments['expense']);
                return view('filament.pages.partials.expense-detail', ['expense' => $expense]);
            })
            ->modalHeading('Detail Pengeluaran')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
