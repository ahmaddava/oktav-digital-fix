<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class CustomerResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
            // Tambahkan permission kustom jika diperlukan, misalnya 'export'
            'export',
        ];
    }
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationLabel(): string
    {
        return __('Pelanggan');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Manajemen');
    }

    public static function getModelLabel(): string
    {
        return __('Pelanggan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pelanggan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_customer')
                    ->label('Name Customer')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email_customer')
                    ->label('Email Customer')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nomor_customer')
                    ->label('Nomor Telepon')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                Forms\Components\Textarea::make('alamat_customer')
                    ->label('Alamat Customer')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_customer')
                    ->label('Name Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_customer')
                    ->label('Email Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nomor_customer')
                    ->label('Nomor Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }
}