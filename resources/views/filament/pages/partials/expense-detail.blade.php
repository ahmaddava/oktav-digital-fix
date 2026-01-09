<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Deskripsi</p>
            <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $expense->description }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah</p>
            <p class="text-base font-semibold text-danger-600">IDR {{ number_format($expense->amount, 0, ',', '.') }}</p>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal</p>
            <p class="text-base text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d F Y') }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dibuat</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $expense->created_at->format('d F Y, H:i') }}</p>
        </div>
    </div>
    
    @if($expense->notes)
    <div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Catatan</p>
        <p class="text-base text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg p-3 mt-1">{{ $expense->notes }}</p>
    </div>
    @endif
</div>
