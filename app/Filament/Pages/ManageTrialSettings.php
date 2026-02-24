<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\RemnawaveService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManageTrialSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationGroup = 'مدیریت کاربران';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'تنظیمات اکانت تست';
    protected static string $view = 'filament.pages.manage-trial-settings';
    protected static ?string $title = 'مدیریت تنظیمات اکانت تست';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill([
            'trial_enabled'        => $settings['trial_enabled'] ?? false,
            'trial_volume_mb'      => $settings['trial_volume_mb'] ?? 500,
            'trial_duration_hours' => $settings['trial_duration_hours'] ?? 24,
            'trial_limit_per_user' => $settings['trial_limit_per_user'] ?? 1,
            'trial_server_id'      => $settings['trial_server_id'] ?? null,
            'remnawave_squad_uuid' => $settings['remnawave_squad_uuid'] ?? null,
        ]);
    }

    /**
     * لیست Squad های Remnawave رو از API می‌گیره
     */
    public static function getRemnawaveSquads(): array
    {
        $settings = Setting::all()->pluck('value', 'key');
        $panelType = $settings->get('panel_type');

        if ($panelType !== 'remnawave') {
            return [];
        }

        $host     = rtrim($settings->get('remnawave_host', ''), '/');
        $apiToken = trim($settings->get('remnawave_api_token', ''), '"\'\ ');

        if (!$host || !$apiToken) {
            return [];
        }

        try {
            $response = Http::withToken($apiToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(5)
                ->get($host . '/api/internal-squads');

            if ($response->successful()) {
                $squads = $response->json('response.internalSquads', []);
                return collect($squads)->mapWithKeys(function ($squad) {
                    $members  = $squad['info']['membersCount'] ?? 0;
                    $inbounds = $squad['info']['inboundsCount'] ?? 0;
                    return [
                        $squad['uuid'] => "{$squad['name']} ({$inbounds} اینباند، {$members} کاربر)"
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch Remnawave squads: ' . $e->getMessage());
        }

        return [];
    }

    public function form(Form $form): Form
    {
        $settings  = Setting::all()->pluck('value', 'key');
        $panelType = $settings->get('panel_type', '');
        $isRemnawave = $panelType === 'remnawave';

        return $form
            ->schema([
                Section::make('تنظیمات اصلی اکانت تست')
                    ->description('در این بخش می‌توانید قابلیت اکانت تست را فعال کرده و مقادیر پیش‌فرض آن را تعیین کنید.')
                    ->schema([
                        Toggle::make('trial_enabled')
                            ->label('فعال‌سازی اکانت تست')
                            ->helperText('اگر فعال باشد، کاربران می‌توانند از ربات اکانت تست دریافت کنند.'),

                        // سرور مخصوص (برای پنل‌های multi-server، نه Remnawave)
                        Select::make('trial_server_id')
                            ->label('سرور مخصوص اکانت تست')
                            ->options(function () {
                                if (class_exists('Modules\\MultiServer\\Models\\Server')) {
                                    return \Modules\MultiServer\Models\Server::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($server) {
                                            return [$server->id => "{$server->name} ({$server->ip_address})"];
                                        });
                                }
                                return [];
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('انتخاب کنید...')
                            ->helperText('اکانت‌های تست روی این سرور ساخته می‌شوند. اگر انتخاب نکنید، سیستم خودکار یک سرور خالی را انتخاب می‌کند.')
                            ->visible(!$isRemnawave),

                        // Squad انتخابی برای Remnawave
                        Select::make('remnawave_squad_uuid')
                            ->label('اسکواد پیش‌فرض (Remnawave)')
                            ->helperText('اکانت‌های تست به این Squad وصل می‌شوند. فقط برای پنل Remnawave.')
                            ->options(fn () => self::getRemnawaveSquads())
                            ->searchable()
                            ->preload()
                            ->placeholder('انتخاب Squad...')
                            ->visible($isRemnawave),

                        TextInput::make('trial_volume_mb')
                            ->label('حجم اکانت تست (مگابایت)')
                            ->numeric()
                            ->required()
                            ->default(500),

                        TextInput::make('trial_duration_hours')
                            ->label('مدت زمان اکانت تست (ساعت)')
                            ->numeric()
                            ->required()
                            ->default(24),

                        TextInput::make('trial_limit_per_user')
                            ->label('محدودیت هر کاربر')
                            ->numeric()
                            ->required()
                            ->default(1),
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            $val = is_null($value) ? '' : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $val]);
        }
        Notification::make()->title('تنظیمات با موفقیت ذخیره شد.')->success()->send();
    }
}
