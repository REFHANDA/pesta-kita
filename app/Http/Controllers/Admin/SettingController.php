<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pengaturan parameter sistem oleh Admin. Setiap perubahan nilai dicatat
 * di log aktivitas (setting_updated) dengan nilai lama dan baru.
 */
class SettingController extends Controller
{
    /**
     * Nilai bawaan bila pengaturan belum pernah disimpan.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'commission_percent' => 10,
        'payment_deadline_hours' => 48,
        'objection_window_hours' => 24,
        'admin_review_sla_hours' => 24,
        'unique_code_enabled' => true,
        'admin_whatsapp' => '',
        'refund_tiers' => [
            ['min_days' => 30, 'percent' => 90],
            ['min_days' => 14, 'percent' => 70],
            ['min_days' => 7, 'percent' => 50],
            ['min_days' => 3, 'percent' => 25],
            ['min_days' => 0, 'percent' => 0],
        ],
        'budget_allocation' => [
            'venue' => 35, 'catering' => 30, 'dekorasi' => 15,
            'hiburan' => 10, 'peralatan' => 5, 'cadangan' => 5,
        ],
    ];

    public function edit(): View
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = Setting::get($key, $default);
        }

        return view('admin.pengaturan', ['settings' => $settings]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $changed = 0;

        DB::transaction(function () use ($request, &$changed): void {
            foreach ($request->settingValues() as $key => $value) {
                $stored = Setting::where('key', $key)->first();
                $old = $stored ? $stored->value : self::DEFAULTS[$key];

                if ($stored && $old === $value) {
                    continue;
                }

                $setting = Setting::set($key, $value);

                if ($old === $value) {
                    continue;
                }

                $changed++;

                AuditLog::create([
                    'actor_id' => $request->user()->id,
                    'action' => 'setting_updated',
                    'subject_type' => $setting->getMorphClass(),
                    'subject_id' => $setting->id,
                    'from_status' => mb_strimwidth(json_encode($old, JSON_UNESCAPED_UNICODE), 0, 255),
                    'to_status' => mb_strimwidth(json_encode($value, JSON_UNESCAPED_UNICODE), 0, 255),
                    'reason' => "Admin mengubah pengaturan {$key}: ".json_encode($old, JSON_UNESCAPED_UNICODE).' → '.json_encode($value, JSON_UNESCAPED_UNICODE).'.',
                    'ip' => $request->ip(),
                ]);
            }
        });

        return redirect()->route('admin.pengaturan')->with('status', $changed > 0
            ? "Pengaturan disimpan ({$changed} perubahan)."
            : 'Tidak ada perubahan pengaturan.');
    }
}
