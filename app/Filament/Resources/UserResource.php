<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Ditambahkan untuk type-hinting
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role; // Impor model Role

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Ganti ikon jika diinginkan

    protected static ?string $navigationGroup = 'Admin'; // Kelompokkan di sidebar

    // --- Metode Otorisasi Ditambahkan ---


    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any', // Untuk bulk delete
            'user.management', // Tambahkan prefix ini untuk mengelola user
            // Tambahkan prefix lain jika ada aksi kustom
            // 'restore',
            // 'force_delete',
        ];
    }
    // /**
    //  * Hanya admin yang bisa melihat item navigasi User dan mengakses halaman daftar.
    //  */
    public static function canViewAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()->hasRole('admin');
    }

    /**
     * Hanya admin yang bisa melihat detail user individual.
     */
    public static function canView(Model $record): bool
    {
        return \Illuminate\Support\Facades\Auth::user()->hasRole('admin');
    }

    /**
     * Hanya admin yang bisa membuat user baru.
     */
    public static function canCreate(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()->hasRole('admin');
    }

    /**
     * Hanya admin yang bisa mengedit user.
     */
    public static function canEdit(Model $record): bool
    {
        return \Illuminate\Support\Facades\Auth::user()->hasRole('admin');
    }

    /**
     * Hanya admin yang bisa menghapus user.
     */
    public static function canDelete(Model $record): bool
    {
        // Tambahan: mungkin Anda tidak ingin admin bisa menghapus dirinya sendiri.
        if ($record->id === \Illuminate\Support\Facades\Auth::id()) {
            return false;
        }
        return \Illuminate\Support\Facades\Auth::user()->hasRole('admin');
    }

    /**
     * Hanya admin yang bisa melakukan bulk delete.
     */
    public static function canDeleteAny(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()->hasRole('admin');
    }

    // Jika Anda menggunakan soft deletes dan ingin mengontrolnya juga:
    // public static function canForceDelete(Model $record): bool
    // {
    //     return auth()->user()->hasRole('admin');
    // }

    // public static function canForceDeleteAny(): bool
    // {
    //     return auth()->user()->hasRole('admin');
    // }

    // public static function canRestore(Model $record): bool
    // {
    //     return auth()->user()->hasRole('admin');
    // }

    // public static function canRestoreAny(): bool
    // {
    //     return auth()->user()->hasRole('admin');
    // }


    // --- Akhir Metode Otorisasi ---

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true), // Pastikan email unik, abaikan record saat ini (untuk edit)
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create') // Wajib hanya saat membuat user baru
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)) // Hash password saat menyimpan
                    ->dehydrated(fn ($state) => filled($state)) // Hanya proses jika diisi (agar tidak mengosongkan password saat edit jika tidak diubah)
                    ->maxLength(255),
                // Forms\Components\DateTimePicker::make('email_verified_at'), // Hapus atau sesuaikan jika perlu

                // Pilihan untuk Role menggunakan spatie/laravel-permission
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name') // 'roles' adalah nama relasi di model User, 'name' adalah kolom yang ditampilkan dari tabel roles
                    ->preload() // Memuat opsi saat halaman dimuat
                    ->searchable()
                    ->label('Roles'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default, bisa ditampilkan
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default, bisa ditampilkan
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan default, bisa ditampilkan

                // Menampilkan Roles di tabel
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge() // Menampilkan sebagai badge
                    ->searchable(),
            ])
            ->filters([
                // Tables\Filters\TrashedFilter::make(), // Jika Anda menggunakan SoftDeletes
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Tambahkan ini jika ingin ada aksi hapus
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(), // Jika menggunakan SoftDeletes
                    // Tables\Actions\RestoreBulkAction::make(), // Jika menggunakan SoftDeletes
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\RolesRelationManager::class, // Anda bisa membuat relation manager jika ingin manajemen role yang lebih detail
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
