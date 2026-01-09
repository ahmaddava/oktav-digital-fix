<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterCostResource\Pages;
use App\Filament\Resources\MasterCostResource\RelationManagers;
use App\Models\MasterCost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MasterCostResource extends Resource
{
    protected static ?string $model = MasterCost::class;
    protected static ?string $navigationGroup = 'Manajemen Harga';
    protected static ?string $navigationLabel = 'Biaya Produksi';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('size')
                ->label('Ukuran')
                ->options([
                    'XS' => 'XS',
                    'Kecil' => 'Kecil',
                    'Sedang' => 'Sedang',
                    'Besar' => 'Besar',
                    'XXL' => 'XXL'
                ])
                ->required(),
            Forms\Components\TextInput::make('production_cost')
                ->label('Ongkos Produksi')
                ->required()
                ->prefix('Rp')
                ->placeholder('Contoh: 100.000')
                ->extraInputAttributes([
                    'x-data' => '{}',
                    'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                    'inputmode' => 'numeric',
                ])
                ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0),
            Forms\Components\TextInput::make('knife_cost')
                ->label('Ongkos Pisau')
                ->required()
                ->prefix('Rp')
                ->placeholder('Contoh: 100.000')
                ->extraInputAttributes([
                    'x-data' => '{}',
                    'x-on:input' => 'let v = $el.value.replace(/\D/g, ""); $el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                    'inputmode' => 'numeric',
                ])
                ->formatStateUsing(fn ($state) => $state ? number_format((int)$state, 0, ',', '.') : '')
                ->dehydrateStateUsing(fn ($state) => $state ? (int) preg_replace('/[^0-9]/', '', $state) : 0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('size')->label('Ukuran')->sortable(),
            Tables\Columns\TextColumn::make('production_cost')->label('Ongkos Produksi')->money('IDR'),
            Tables\Columns\TextColumn::make('knife_cost')->label('Ongkos Pisau')->money('IDR'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterCosts::route('/'),
            'edit' => Pages\EditMasterCost::route('/{record}/edit'),
            'create' => Pages\CreateMasterCost::route('/create'),
        ];
    }
}