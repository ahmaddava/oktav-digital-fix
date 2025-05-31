<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolyCostResource\Pages;
use App\Models\PolyCost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PolyCostResource extends Resource
{
    protected static ?string $model = PolyCost::class;
    protected static ?string $navigationGroup = 'Manajemen Harga';
    protected static ?string $navigationLabel = 'Biaya Poly';
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('dimension')
                ->label('Dimensi Poly')
                ->options([
                    '10x10' => '10 cm x 10 cm',
                    '10x15' => '10 cm x 15 cm',
                    '15x15' => '15 cm x 15 cm'
                ])
                ->required(),
            Forms\Components\TextInput::make('cost')
                ->label('Harga Poly')
                ->numeric()
                ->prefix('Rp')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('dimension')->label('Dimensi Poly')->sortable(),
            Tables\Columns\TextColumn::make('cost')->label('Harga')->money('IDR'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolyCosts::route('/'),
            'edit' => Pages\EditPolyCost::route('/{record}/edit'),
            'create' => Pages\CreatePolyCost::route('/create'),
        ];
    }
}
