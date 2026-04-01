<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Pages\Dashboard;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\MenuItem;
use App\Http\Middleware\SetLocaleMiddleware;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->spa()
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\ProductionCalculator::class,
                \App\Filament\Pages\EditPassword::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Ubah Password')
                    ->url(fn (): string => \App\Filament\Pages\EditPassword::getUrl())
                    ->icon('heroicon-s-key'),
                // 'logout' => MenuItem::make()->label('Log out'), // Ini adalah item logout default
            ])
            ->resources([
                \App\Filament\Resources\ProductionCategoryResource::class,
                \App\Filament\Resources\ProductionItemResource::class,
                \App\Filament\Resources\PriceCalculationResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets are now managed by the custom Dashboard page
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocaleMiddleware::class,
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('
                    <div class="flex items-center gap-x-2 px-3 py-1.5 rounded-full bg-gray-100/50 dark:bg-white/5 border border-gray-200 dark:border-white/10 mx-4">
                        <a href="{{ route(\'language.switch\', [\'locale\' => \'id\']) }}" 
                           class="px-2 py-0.5 rounded-full text-xs font-bold transition-all {{ app()->getLocale() === \'id\' ? \'bg-primary-500 text-white shadow-sm\' : \'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200\' }}">
                            ID
                        </a>
                        <a href="{{ route(\'language.switch\', [\'locale\' => \'en\']) }}" 
                           class="px-2 py-0.5 rounded-full text-xs font-bold transition-all {{ app()->getLocale() === \'en\' ? \'bg-primary-500 text-white shadow-sm\' : \'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200\' }}">
                            EN
                        </a>
                    </div>
                '),
            )
            ->pages([
                Dashboard::class,
            ])
            ->plugins([])
            ->authMiddleware([
                Authenticate::class,
            ]);
        }
}