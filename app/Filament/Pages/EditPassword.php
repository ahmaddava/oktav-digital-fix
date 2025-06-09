<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;

class EditPassword extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.edit-password';

    protected static bool $shouldRegisterNavigation = false;

    // PERBAIKAN: Properti title harus static
    protected static ?string $title = 'Ubah Password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ubah Password Anda')
                    ->description('Pastikan Anda menggunakan password yang kuat dan mudah diingat.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->prefixIcon('heroicon-o-lock-closed'),

                        // Membuat grup untuk password baru agar rapi
                        Grid::make(2)
                            ->schema([
                                TextInput::make('new_password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->required()
                                    ->rules(['min:8', 'string', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'])
                                    ->helperText('Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.')
                                    ->different('current_password')
                                    ->confirmed()
                                    ->prefixIcon('heroicon-o-key'),

                                TextInput::make('new_password_confirmation')
                                    ->label('Konfirmasi Password Baru')
                                    ->password()
                                    ->required()
                                    ->prefixIcon('heroicon-o-key'),
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    public function updatePassword(): void
    {
        $data = $this->form->getState();

        \Illuminate\Support\Facades\Auth::user()->update([
            'password' => Hash::make($data['new_password']),
        ]);

        Notification::make()
            ->title('Password berhasil diperbarui')
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('updatePassword')
                ->label('Simpan Perubahan')
                ->submit('updatePassword'),
        ];
    }
}