<x-filament-panels::page>
    <style>
        /* Custom tooltip for action buttons */
        .action-btn {
            position: relative;
        }
        .action-btn:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 8px;
            background: #1f2937;
            color: white;
            font-size: 11px;
            border-radius: 4px;
            white-space: nowrap;
            z-index: 50;
            margin-bottom: 4px;
        }
        
        /* Stats grid responsive */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Stat card styling */
        .stat-card {
            border-radius: 0.75rem;
            padding: 1.25rem;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .stat-card-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .stat-card-value {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 0.25rem;
            word-break: break-word;
        }
        .stat-card-icon {
            flex-shrink: 0;
            padding: 0.5rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Table row hover - keep text visible */
        .data-table tbody tr:hover {
            background-color: rgba(55, 65, 81, 0.3) !important;
        }
        .data-table tbody tr:hover td {
            color: inherit !important;
        }
    </style>

    <div class="space-y-6">
        {{-- Header Section with Year & Month Filter --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn p-4">
                <div class="flex flex-wrap items-end gap-4">
                    <form wire:submit.prevent class="flex flex-wrap gap-4">
                        {{ $this->form }}
                    </form>
                </div>
            </div>
        </div>

        {{-- Stats Cards - 2x2 on mobile, 4 cols on desktop --}}
        <div class="stats-grid">
            {{-- Total Pemasukan --}}
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="stat-card-content">
                    <div style="min-width: 0;">
                        <p class="text-sm font-medium opacity-90">Total Pemasukan</p>
                        <p class="stat-card-value">Rp {{ number_format($totalIncome, 0, ",", ".") }}</p>
                    </div>
                    <div class="stat-card-icon">
                        <x-heroicon-s-arrow-trending-up class="h-5 w-5" />
                    </div>
                </div>
            </div>

            {{-- Total Pengeluaran --}}
            <div class="stat-card" style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
                <div class="stat-card-content">
                    <div style="min-width: 0;">
                        <p class="text-sm font-medium opacity-90">Total Pengeluaran</p>
                        <p class="stat-card-value">Rp {{ number_format($totalExpense, 0, ",", ".") }}</p>
                    </div>
                    <div class="stat-card-icon">
                        <x-heroicon-s-arrow-trending-down class="h-5 w-5" />
                    </div>
                </div>
            </div>

            {{-- Balance --}}
            <div class="stat-card" style="background: linear-gradient(135deg, {{ $balance >= 0 ? '#3b82f6' : '#f97316' }} 0%, {{ $balance >= 0 ? '#2563eb' : '#ea580c' }} 100%);">
                <div class="stat-card-content">
                    <div style="min-width: 0;">
                        <p class="text-sm font-medium opacity-90">Balance</p>
                        <p class="stat-card-value">{{ $balance >= 0 ? '+' : '' }}Rp {{ number_format($balance, 0, ",", ".") }}</p>
                    </div>
                    <div class="stat-card-icon">
                        <x-heroicon-s-scale class="h-5 w-5" />
                    </div>
                </div>
            </div>

            {{-- Total Piutang --}}
            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="stat-card-content">
                    <div style="min-width: 0;">
                        <p class="text-sm font-medium opacity-90">Total Piutang</p>
                        <p class="stat-card-value">Rp {{ number_format($totalReceivables, 0, ",", ".") }}</p>
                    </div>
                    <div class="stat-card-icon">
                        <x-heroicon-s-clock class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        @php
            $date = \Carbon\Carbon::parse($this->selectedDate);
            $year = $date->year;
            $month = $date->format('m');
        @endphp

        {{-- Tables Section --}}
        <div class="space-y-6">
            {{-- Income Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-banknotes class="h-5 w-5 text-emerald-500" />
                        <span>Pemasukan (Paid Invoices)</span>
                    </div>
                </x-slot>
                <x-slot name="headerEnd">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Per halaman:</span>
                        <select wire:model.live="incomePerPage" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs py-1 px-2">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </x-slot>
                
                @php
                    $incomeData = \App\Models\Invoice::query()
                        ->where('status', 'paid')
                        ->whereYear('updated_at', $year)
                        ->whereMonth('updated_at', $month)
                        ->orderBy('updated_at', 'desc')
                        ->paginate($this->incomePerPage, ['*'], 'incomePage');
                @endphp
                
                @if ($incomeData->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="data-table w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">No. Invoice</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Customer</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                @foreach ($incomeData as $invoice)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($invoice->updated_at)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                            {{ $invoice->invoice_number }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $invoice->name_customer ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                            +Rp {{ number_format($invoice->grand_total, 0, ",", ".") }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                        {{ $incomeData->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                            <x-heroicon-o-document-magnifying-glass class="h-6 w-6 text-gray-400" />
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada data pemasukan pada periode ini</p>
                    </div>
                @endif
            </x-filament::section>

            {{-- Expense Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-s-receipt-percent class="h-5 w-5 text-rose-500" />
                        <span>Pengeluaran</span>
                    </div>
                </x-slot>
                <x-slot name="headerEnd">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Per halaman:</span>
                        <select wire:model.live="expensePerPage" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs py-1 px-2">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </x-slot>
                
                @php
                    $expenseData = \App\Models\Expense::query()
                        ->whereYear('expense_date', $year)
                        ->whereMonth('expense_date', $month)
                        ->orderBy('expense_date', 'desc')
                        ->paginate($this->expensePerPage, ['*'], 'expensePage');
                @endphp
                
                @if ($expenseData->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="data-table w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Deskripsi</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Jumlah</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                @foreach ($expenseData as $expense)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $expense->description }}</div>
                                            @if($expense->notes)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ $expense->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400">
                                            -Rp {{ number_format($expense->amount, 0, ",", ".") }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-2">
                                                {{-- View Button --}}
                                                <button 
                                                    type="button"
                                                    wire:click="mountAction('viewExpense', { expense: {{ $expense->id }} })"
                                                    class="action-btn inline-flex items-center justify-center rounded-lg p-2 text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                                                    data-tooltip="Lihat Detail"
                                                >
                                                    <x-heroicon-o-eye class="h-4 w-4" />
                                                </button>
                                                
                                                {{-- Edit Button --}}
                                                <button 
                                                    type="button"
                                                    wire:click="mountAction('editExpense', { expense: {{ $expense->id }} })"
                                                    class="action-btn inline-flex items-center justify-center rounded-lg p-2 text-amber-600 hover:bg-amber-100 dark:text-amber-400 dark:hover:bg-amber-900/50 transition-colors"
                                                    data-tooltip="Edit"
                                                >
                                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                                </button>
                                                
                                                {{-- Delete Button --}}
                                                <button 
                                                    type="button"
                                                    wire:click="mountAction('deleteExpense', { expense: {{ $expense->id }} })"
                                                    class="action-btn inline-flex items-center justify-center rounded-lg p-2 text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors"
                                                    data-tooltip="Hapus"
                                                >
                                                    <x-heroicon-o-trash class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                        {{ $expenseData->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-800">
                            <x-heroicon-o-banknotes class="h-6 w-6 text-gray-400" />
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada data pengeluaran pada periode ini</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Klik "Tambah Pengeluaran" untuk menambah data</p>
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
