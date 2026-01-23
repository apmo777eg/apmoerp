<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    public function index()
    {
        // V57-HIGH-01 FIX: Add authorization for system settings viewing
        $this->authorize('settings.view');
        
        return $this->show();
    }

    public function show()
    {
        // V57-HIGH-01 FIX: Add authorization for system settings viewing
        $this->authorize('settings.view');
        
        $pairs = DB::table('system_settings')->pluck('value', 'key')->all();

        return $this->ok(['settings' => $pairs]);
    }

    public function update(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for system settings management
        $this->authorize('settings.manage');
        
        $data = (array) $request->input('settings', []);
        foreach ($data as $k => $v) {
            DB::table('system_settings')->updateOrInsert(['setting_key' => $k], ['value' => is_scalar($v) ? (string) $v : json_encode($v), 'updated_at' => now()]);
        }

        return $this->ok(['updated' => count($data)], __('Settings saved'));
    }
}
