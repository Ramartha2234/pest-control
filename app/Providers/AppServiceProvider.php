<?php

namespace App\Providers;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (Schema::hasTable('pengaturan')) {
                $settings = Pengaturan::all()->pluck('nilai', 'kunci');
                if ($settings->isNotEmpty()) {
                    if (! empty($settings['bank_name'])) {
                        config(['pestcontrol.bank_name' => $settings['bank_name']]);
                    }
                    if (! empty($settings['bank_account_number'])) {
                        config(['pestcontrol.bank_account_number' => $settings['bank_account_number']]);
                    }
                    if (! empty($settings['bank_account_holder'])) {
                        config(['pestcontrol.bank_account_holder' => $settings['bank_account_holder']]);
                    }
                    if (! empty($settings['whatsapp_number'])) {
                        config(['pestcontrol.whatsapp_number' => $settings['whatsapp_number']]);
                    }
                }
            }
        } catch (\Throwable) {
            // Safe fallback during early boot or migration runs
        }
    }
}
