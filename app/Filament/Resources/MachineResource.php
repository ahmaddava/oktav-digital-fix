<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MachineResource\Pages;
use App\Models\Machine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;

    public static function getNavigationLabel(): string
    {
        return __('Pengaturan Mesin');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Produksi');
    }

    public static function getModelLabel(): string
    {
        return __('Mesin');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Mesin');
    }
    protected static ?string $modelLabel = 'Mesin';
    protected static ?string $pluralModelLabel = 'Mesin';
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Mesin')
                    ->description('Tambah atau edit informasi mesin produksi.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Mesin')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Mesin Roland, Mesin Konica, dll'),

                        Forms\Components\Toggle::make('use_clicks')
                            ->label('Menggunakan Hitungan Klik?')
                            ->helperText('Jika diaktifkan, produksi di mesin ini akan mencatat total klik dan counter.')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Mesin')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('use_clicks')
                    ->label('Pakai Klik')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('productions_count')
                    ->counts('productions')
                    ->label('Total Produksi')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Machine $record) {
                        // Set productions to null machine before deleting
                        $record->productions()->update(['machine_id' => null]);
                    }),
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
            'index' => Pages\ManageMachines::route('/'),
        ];
    }
}
