<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionCategoryResource\Pages;
use App\Models\ProductionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionCategoryResource extends Resource
{
    protected static ?string $model = ProductionCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getNavigationLabel(): string
    {
        return __('Kategori Produksi');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Kalkulasi Harga');
    }

    public static function getModelLabel(): string
    {
        return __('Kategori Produksi');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Kategori Produksi');
    }
    protected static ?string $pluralModelLabel = 'Kategori Produksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dalam' => 'success',
                        'luar' => 'info',
                        'material' => 'warning',
                        'service' => 'primary',
                        'profit' => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'dalam' => 'Dalam',
                        'luar' => 'Luar',
                        'material' => 'Material',
                        'service' => 'Service',
                        'profit' => 'Profit'
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
            'index' => Pages\ListProductionCategories::route('/'),
            'create' => Pages\CreateProductionCategory::route('/create'),
            'edit' => Pages\EditProductionCategory::route('/{record}/edit'),
        ];
    }
}