<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientSettingController extends Controller
{
    /**
     * Update the client app settings.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate([
            'free_trial' => ['boolean'],
            'manual_access' => ['boolean'],
        ]);

        Setting::putBoolean('client.free_trial', $request->boolean('free_trial'));
        Setting::putBoolean('client.manual_access', $request->boolean('manual_access'));

        return redirect()
            ->route('dashboard', ['page' => 'client-settings'])
            ->with('toast', __('site.admin.settings_saved'));
    }
}
