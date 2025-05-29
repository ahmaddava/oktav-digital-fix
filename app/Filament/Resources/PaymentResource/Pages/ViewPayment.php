<?php
// App/Filament/Resources/PaymentResource/Pages/ViewPayment.php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Update Payment'),
                
            Actions\Action::make('view_invoice')
                ->label('View Invoice')
                ->icon('heroicon-o-document-text')
                ->url(fn () => \App\Filament\Resources\InvoiceResource::getUrl('view', ['record' => $this->record]))
                ->color('info'),

            Actions\Action::make('print_invoice')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('invoices.print', $this->record))
                ->openUrlInNewTab()
                ->color('gray'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('invoice_number')
                                ->label('Invoice Number'),
                            
                            TextEntry::make('name_customer')
                                ->label('Customer Name'),
                        ]),
                        
                        Grid::make(2)->schema([
                            TextEntry::make('customer_phone')
                                ->label('Customer Phone'),
                            
                            TextEntry::make('customer_email')
                                ->label('Customer Email'),
                        ]),
                        
                        TextEntry::make('alamat_customer')
                            ->label('Customer Address')
                            ->columnSpanFull(),
                            
                        TextEntry::make('grand_total')
                            ->label('Total Amount')
                            ->money('IDR')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Payment Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('status')
                                ->label('Payment Status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'paid' => 'success',
                                    'unpaid' => 'danger',
                                    default => 'gray',
                                }),
                            
                            TextEntry::make('payment_method')
                                ->label('Payment Method')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'transfer' => 'info',
                                    'cash' => 'warning',
                                    default => 'gray',
                                }),
                        ]),
                        
                        Grid::make(2)->schema([
                            TextEntry::make('dp')
                                ->label('Down Payment')
                                ->money('IDR')
                                ->placeholder('No Down Payment'),
                            
                            TextEntry::make('remaining_amount')
                                ->label('Remaining Amount')
                                ->formatStateUsing(function ($record) {
                                    $remaining = $record->grand_total - ($record->dp ?? 0);
                                    return 'Rp ' . number_format($remaining, 0, ',', '.');
                                })
                                ->visible(fn ($record) => $record->status === 'unpaid' && $record->dp > 0),
                        ]),
                        
                        TextEntry::make('payment_notes')
                            ->label('Payment Notes')
                            ->placeholder('No payment notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Created At')
                                ->dateTime(),
                            
                            TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime(),
                        ]),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}