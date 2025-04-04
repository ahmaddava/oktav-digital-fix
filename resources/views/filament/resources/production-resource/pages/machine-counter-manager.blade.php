<?php
// resources/views/filament/resources/production-resource/pages/machine-counter-manager.blade.php
?>

<x-filament::page>
    <x-filament::card>
        <div class="mb-5">
            <h2 class="text-xl font-bold mb-2">Pengaturan Counter Mesin</h2>
            <p class="text-gray-500">
                Halaman ini digunakan untuk menyesuaikan nilai counter pada database dengan nilai counter fisik pada mesin.
                Jika terdapat perbedaan, masukkan nilai yang tertera pada mesin fisik untuk menyesuaikannya.
            </p>
        </div>

        <form wire:submit="updateCounters">
            {{ $this->form }}
            
            <div class="mt-5">
                <x-filament::button type="submit" color="primary">
                    Update Counter Mesin
                </x-filament::button>
                
                <x-filament::button type="button" color="secondary" wire:click="refreshCounterData" class="ml-2">
                    Refresh Data
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
    
    <div class="mt-5">
        <x-filament::card>
            <h3 class="text-lg font-medium mb-3">Petunjuk Penggunaan</h3>
            <ol class="list-decimal pl-5 space-y-2">
                <li>Lihat nilai <strong>Counter Saat Ini</strong> yang merupakan nilai yang tersimpan dalam database.</li>
                <li>Periksa nilai counter pada mesin fisik Anda.</li>
                <li>Jika terdapat perbedaan, masukkan nilai counter mesin fisik pada kolom <strong>Nilai Baru</strong>.</li>
                <li>Sistem hanya memperbolehkan nilai baru yang lebih besar dari nilai saat ini.</li>
                <li>Klik <strong>Update Counter Mesin</strong> untuk menyimpan perubahan.</li>
                <li>Sistem akan secara otomatis membuat record adjustment untuk mencatat perbedaan.</li>
            </ol>
        </x-filament::card>
    </div>
</x-filament::page>