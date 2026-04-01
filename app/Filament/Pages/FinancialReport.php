<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithPagination;

class FinancialReport extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Reports';
    protected static string $view = 'filament.pages.financial-report';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public float $totalIncome = 0;
    public float $totalExpense = 0;
    public float $totalReceivables = 0;
    public float $balance = 0;
    
    // Pagination per page settings
    public int $incomePerPage = 5;
    public int $expensePerPage = 5;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
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
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ]), shouldOpenInNewTab: true),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)->schema([
                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->displayFormat('d F Y')
                    ->format('Y-m-d')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->default(now()->startOfMonth())
                    ->live(),
                DatePicker::make('endDate')
                    ->label('Tanggal Akhir')
                    ->displayFormat('d F Y')
                    ->format('Y-m-d')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->default(now()->endOfMonth())
                    ->live(),
            ]),
        ];
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'incomePerPage', 'expensePerPage'])) {
            if (in_array($propertyName, ['startDate', 'endDate'])) {
                $this->resetPage('incomePage');
                $this->resetPage('expensePage');
            }
            $this->calculateStats();
        }
    }

    public function calculateStats(): void
    {
        if (!$this->startDate || !$this->endDate) return;

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Query untuk income dari invoice yang sudah dibayar
        $incomeQuery = Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('updated_at', [$start, $end]);

        $expenseQuery = Expense::query()
            ->whereBetween('expense_date', [$start, $end]);

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
