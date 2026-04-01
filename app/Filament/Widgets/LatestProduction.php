<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Production;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;

class LatestProduction extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return __('Monitoring Produksi');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => Production::query()
                    ->where(function (Builder $query) {
                        return $query->where('is_adjustment', 0)
                            ->orWhereNull('is_adjustment');
                    })
                    ->whereIn('status', ['pending', 'started', 'completed'])
                    ->latest('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label(__('Nomor Invoice'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('machine.name')
                    ->label(__('Nama Mesin'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label(__('Mulai'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('invoice.due_date')
                    ->label(__('Deadline'))
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->color(fn (?string $state, Production $record): string => 
                        $state && $record->status !== 'completed' && \Carbon\Carbon::parse($state)->isPast() ? 'danger' : 'default'
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'started' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('Antrian'),
                        'started' => __('Dalam Proses'),
                        'completed' => __('Selesai'),
                        default => $state,
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Antrian'),
                        'started' => __('Dalam Proses'),
                        'completed' => __('Selesai'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Detail'))
                    ->icon('heroicon-m-eye')
                    ->color('warning')
                    ->modalHeading(__('Detail Produksi & Invoice'))
                    ->modalWidth('4xl')
                    ->infolist(fn (Infolist $infolist): Infolist => $infolist
                        ->schema([
                            Section::make(__('Informasi Invoice'))
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('invoice.invoice_number')
                                                ->label(__('Nomor Invoice'))
                                                ->weight(FontWeight::Bold)
                                                ->copyable()
                                                ->color('primary'),
                                            TextEntry::make('invoice.name_customer')
                                                ->label(__('Nama Pelanggan'))
                                                ->placeholder('-'),
                                            TextEntry::make('invoice.created_at')
                                                ->label(__('Tanggal Invoice'))
                                                ->date()
                                                ->placeholder('-'),
                                        ]),
                                    TextEntry::make('invoice.notes_invoice')
                                        ->label(__('Catatan Invoice'))
                                        ->markdown()
                                        ->placeholder('-')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),

                            Section::make(__('Detail Produksi'))
                                ->icon('heroicon-o-cpu-chip')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('machine.name')
                                                ->label(__('Mesin'))
                                                ->badge()
                                                ->color('info')
                                                ->placeholder(__('Belum ditentukan')),
                                            TextEntry::make('status')
                                                ->label(__('Status'))
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'pending' => 'gray',
                                                    'started' => 'info',
                                                    'completed' => 'success',
                                                    default => 'gray',
                                                })
                                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                                    'pending' => __('Antrian'),
                                                    'started' => __('Dalam Proses'),
                                                    'completed' => __('Selesai'),
                                                    default => $state,
                                                }),
                                            TextEntry::make('started_at')
                                                ->label(__('Mulai Produksi'))
                                                ->date()
                                                ->placeholder('-'),
                                        ]),
                                ])
                                ->collapsible(),

                            Section::make(__('Item Pesanan'))
                                ->icon('heroicon-o-shopping-cart')
                                ->schema([
                                    RepeatableEntry::make('invoice.invoiceProducts')
                                        ->label('')
                                        ->schema([
                                            Grid::make(4)
                                                ->schema([
                                                    TextEntry::make('product_name')
                                                        ->label(__('Nama Produk'))
                                                        ->weight(FontWeight::Bold)
                                                        ->getStateUsing(fn ($record) => $record->product->product_name ?? $record->product_name),
                                                    TextEntry::make('quantity')
                                                        ->label(__('Jumlah'))
                                                        ->suffix(' unit')
                                                        ->numeric(),
                                                    TextEntry::make('status')
                                                        ->label(__('Status Item'))
                                                        ->badge()
                                                        ->color(fn (string $state): string => match ($state) {
                                                            'pending' => 'gray',
                                                            'started' => 'info',
                                                            'completed' => 'success',
                                                            default => 'gray',
                                                        })
                                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                                            'pending' => __('Pending'),
                                                            'started' => __('Proses'),
                                                            'completed' => __('Selesai'),
                                                            default => $state,
                                                        }),
                                                    TextEntry::make('machine.name')
                                                        ->label(__('Mesin Item'))
                                                        ->badge()
                                                        ->color('primary')
                                                        ->placeholder('-'),
                                                ]),
                                        ])
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),
                        ])),
            ]);
    }
}
