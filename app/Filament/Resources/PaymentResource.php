<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use App\Filament\Resources\PaymentResource\Pages;
use Filament\Forms\Components\Grid;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Get;
use Filament\Forms\Set;

class PaymentResource extends Resource implements HasShieldPermissions
{
    protected static ?string $navigationGroup = 'Management';
    
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Management';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'delete', 'delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('invoice_number')
                                ->label('Invoice Number')
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('name_customer')
                                ->label('Customer Name')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                        
                        Grid::make(2)->schema([
                            TextInput::make('customer_phone')
                                ->label('Customer Phone')
                                ->disabled()
                                ->dehydrated(false),
                            
                            TextInput::make('customer_email')
                                ->label('Customer Email')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                        
                        TextInput::make('grand_total')
                            ->label('Total Amount')
                            ->disabled()
                            ->prefix('Rp ')
                            ->formatStateUsing(function ($state) {
                                return number_format((float)$state, 0, ',', '.');
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->compact(),

                Section::make('Payment Details')
                    ->schema([
                        Grid::make(2)->schema([
                            // Status Pembayaran
                            ToggleButtons::make('status')
                                ->label('Payment Status')
                                ->options([
                                    'paid' => 'Paid',
                                    'unpaid' => 'Unpaid',
                                ])
                                ->required()
                                ->inline()
                                ->colors([
                                    'paid' => 'success',
                                    'unpaid' => 'danger',
                                ])
                                ->icons([
                                    'paid' => 'heroicon-s-check-circle',
                                    'unpaid' => 'heroicon-s-exclamation-circle',
                                ])
                                ->default('unpaid')
                                ->reactive(),

                            // Jenis Pembayaran
                            ToggleButtons::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'transfer' => 'Transfer',
                                    'cash' => 'Cash',
                                ])
                                ->required()
                                ->inline()
                                ->colors([
                                    'transfer' => 'info',
                                    'cash' => 'warning',
                                ])
                                ->icons([
                                    'transfer' => 'heroicon-s-credit-card',
                                    'cash' => 'heroicon-s-currency-dollar',
                                ])
                                ->default('transfer'),
                        ]),

                        Grid::make(2)->schema([
                            // Down Payment (DP)
                            TextInput::make('dp')
                            ->label('Down Payment (DP)')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Masukkan jumlah DP')
                            ->hidden(fn (Get $get) => $get('status') !== 'unpaid')
                            ->formatStateUsing(function ($state) {
                                return $state ? number_format((float)$state, 0, ',', '.') : '';
                            })
                            ->dehydrateStateUsing(function ($state, $record) {
                                $cleanValue = $state ? (int) str_replace(['.', ','], '', $state) : 0;
                                $grandTotal = $record?->grand_total ?? 0;
                                return min($cleanValue, $grandTotal);
                            })
                            ->rules([
                                'numeric',
                                'min:0',
                                function ($record) {
                                    return function ($attribute, $value, $fail) use ($record) {
                                        $cleanValue = $value ? (int) str_replace(['.', ','], '', $value) : 0;
                                        $grandTotal = $record->grand_total;
                                        
                                        if ($cleanValue > $grandTotal) {
                                            $fail('DP tidak boleh melebihi total amount (Rp ' . number_format($grandTotal, 0, ',', '.') . ')');
                                        }
                                    };
                                }
                            ])
                            ->maxValue(function ($record) {
                                return $record?->grand_total;
                            })
                            ->live(onBlur: true) // Update realtime saat keluar dari field
                            ->afterStateUpdated(function ($state, Set $set, $record) {
                                // Bersihkan nilai DP
                                $dpValue = $state ? (int) str_replace(['.', ','], '', $state) : 0;
                                $grandTotal = $record?->grand_total ?? 0;
                                
                                // Jika DP sama dengan grand total, ubah status menjadi paid
                                if ($dpValue === $grandTotal) {
                                    $set('status', 'paid');
                                    $set('paid_at', now());
                                }
                            })
                            ->helperText(function (Get $get, $record, $state) {
                                if ($get('status') !== 'unpaid') {
                                    return null;
                                }
                        
                                $grandTotal = $record?->grand_total ?? 0;
                                $dpValue = $state ? (int) str_replace(['.', ','], '', $state) : 0;
                                $remaining = $grandTotal - $dpValue;
                                
                                if ($dpValue > $grandTotal) {
                                    return 'DP melebihi total amount! Maksimal: Rp ' . number_format($grandTotal, 0, ',', '.');
                                }
                                
                                // Tambahkan pesan jika pembayaran lunas
                                if ($dpValue === $grandTotal) {
                                    return 'Pembayaran lunas! Status akan diubah otomatis menjadi Paid';
                                }
                                
                                return 'Sisa pembayaran: Rp ' . number_format($remaining, 0, ',', '.');
                            })
                            ->hintColor(function (Get $get, $record, $state) {
                                if ($get('status') !== 'unpaid') {
                                    return null;
                                }
                        
                                $grandTotal = $record?->grand_total ?? 0;
                                $dpValue = $state ? (int) str_replace(['.', ','], '', $state) : 0;
                                
                                if ($dpValue === $grandTotal) {
                                    return 'success'; // Warna hijau saat lunas
                                }
                                
                                return $dpValue > $grandTotal ? 'danger' : null;
                            }),
                            
                            // Remaining Amount (calculated)
                            TextInput::make('remaining_amount')
                                ->disabled()
                                ->label('Remaining Amount')
                                ->formatStateUsing(function ($record) {
                                    $remaining = $record->grand_total - ($record->dp ?? 0);
                                    return 'Rp ' . number_format($remaining, 0, ',', '.');
                                })
                                ->visible(fn ($record) => $record->status === 'unpaid' && $record->dp > 0),
                        ]),

                    Select::make('approval_status')
                        ->label('Status Persetujuan')
                        ->options([
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                        ])
                        ->required()
                        ->disabled(fn ($record) => $record->approval_status === 'approved' && $record->status === 'paid')
                        ->helperText(function ($record) {
                            if ($record->approval_status === 'approved' && $record->status === 'paid') {
                                return 'Invoice sudah dibayar dan disetujui, tidak dapat diubah';
                            } elseif ($record->approval_status === 'approved') {
                                return 'Status disetujui, bisa diubah ke rejected jika perlu';
                            }
                            return 'Pilih status persetujuan';
                        })
                    ])
                    ->columns(2)
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_customer')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->label('Total Amount')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Payment Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'paid' => 'heroicon-s-check-circle',
                        'unpaid' => 'heroicon-s-exclamation-circle',
                        default => '',
                    })
                    ->iconPosition(IconPosition::After)
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        default => 'Unknown',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('approval_status')
                    ->label('Approval Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'approved' => 'heroicon-s-check-badge',
                        'pending' => 'heroicon-s-clock',
                        'rejected' => 'heroicon-s-x-circle',
                        default => 'heroicon-s-question-mark-circle',
                    })
                    ->iconPosition(IconPosition::After)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Approved',
                        'pending' => 'Pending',
                        'rejected' => 'Rejected',
                        default => 'Not Set',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'transfer' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'transfer' => 'Transfer',
                        'cash' => 'Cash',
                        default => 'N/A',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('dp')
                    ->label('Down Payment')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('No DP'),

                TextColumn::make('approved_by')
                    ->label('Approved By')
                    ->getStateUsing(function ($record) {
                        return $record->approvedBy ? $record->approvedBy->name : '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approval_notes')
                    ->label('Approval Notes')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                    ]),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Approval Status')
                    ->options([
                        'approved' => 'Approved',
                        'pending' => 'Pending',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'transfer' => 'Transfer',
                        'cash' => 'Cash',
                    ]),

                Tables\Filters\Filter::make('has_dp')
                    ->label('Has Down Payment')
                    ->query(fn (Builder $query): Builder => $query->where('dp', '>', 0)),

                Tables\Filters\SelectFilter::make('month')
                    ->label('Month')
                    ->options([
                        '1' => 'January',
                        '2' => 'February', 
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereMonth('created_at', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Update Payment'),
                    
                Tables\Actions\ViewAction::make()
                    ->label('View Details'),

                // Tables\Actions\Action::make('approval_status')
                //     ->label('Approve')
                //     ->icon('heroicon-s-check-badge')
                //     ->color('success')
                //     ->requiresConfirmation()
                //     ->visible(fn ($record) => $record->approval_status !== 'approved')
                //     ->action(function ($record) {
                //         $record->update([
                //             'approval_status' => 'approved',
                //             'approved_by' => \Illuminate\Support\Facades\Auth::id(),
                //             'approved_at' => now(),
                //             'approval_notes' => 'Disetujui melalui table action'
                //         ]);
                //     }),

                // Tables\Actions\Action::make('approval_status')
                //     ->label('Reject')
                //     ->icon('heroicon-s-x-circle')
                //     ->color('danger')
                //     ->requiresConfirmation()
                //     ->visible(fn ($record) => $record->approval_status !== 'rejected')
                //     ->form([
                //         \Filament\Forms\Components\Textarea::make('rejection_reason')
                //             ->label('Rejection Reason')
                //             ->required()
                //             ->rows(3),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->update([
                //             'approval_status' => 'rejected',
                //             'approved_by' => \Illuminate\Support\Facades\Auth::id(),
                //             'approved_at' => now(),
                //             'approval_notes' => 'Ditolak: ' . $data['rejection_reason']
                //         ]);
                //     }),

                Tables\Actions\Action::make('print_invoice')
                    ->label('Print Invoice')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('invoices.print', $record))
                    ->openUrlInNewTab()
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-s-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'paid']);
                            });
                        }),

                    Tables\Actions\BulkAction::make('mark_as_unpaid')
                        ->label('Mark as Unpaid')
                        ->icon('heroicon-s-exclamation-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'unpaid']);
                            });
                        }),

                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Bulk Approve')
                        ->icon('heroicon-s-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'approval_status' => 'approved',
                                    'approved_by' => \Illuminate\Support\Facades\Auth::id(),
                                    'approved_at' => now(),
                                    'approval_notes' => 'Bulk approval'
                                ]);
                            });
                        }),

                    Tables\Actions\BulkAction::make('bulk_pending')
                        ->label('Mark as Pending')
                        ->icon('heroicon-s-clock')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'approval_status' => 'pending',
                                    'approved_by' => null,
                                    'approved_at' => null,
                                    'approval_notes' => 'Reset to pending status'
                                ]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->select('id', 'invoice_number', 'name_customer', 'grand_total', 'status', 'approval_status', 'payment_method', 'dp', 'approved_by', 'approved_at', 'approval_notes', 'created_at', 'updated_at');
            })
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    // app/Filament/Resources/PaymentResource.php
    public static function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\PaymentResource\Widgets\PaymentWidgets::class,
        ];
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'unpaid')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unpaidCount = static::getModel()::where('status', 'unpaid')->count();
        return $unpaidCount > 0 ? 'danger' : 'success';
    }

    // Custom query to only show invoices (since we're using Invoice model)
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('invoice_number');
    }
}