<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group')->map(function ($groupSettings) {
            return $groupSettings->mapWithKeys(function ($setting) {
                $value = $setting->value;
                
                // Cast value based on type
                if ($setting->type === 'boolean') {
                    $value = (bool) $value;
                } elseif ($setting->type === 'json') {
                    $value = json_decode($value, true);
                } elseif ($setting->type === 'integer') {
                    $value = (int) $value;
                } elseif ($setting->type === 'float') {
                    $value = (float) $value;
                }

                return [$setting->key => $value];
            });
        });

        return response()->json($settings);
    }

    /**
     * Get settings by group
     */
    public function getByGroup(string $group)
    {
        $settings = Setting::getByGroup($group);
        return response()->json($settings);
    }

    /**
     * Get a specific setting
     */
    public function show(string $key)
    {
        $value = Setting::get($key);
        
        if ($value === null) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json(['key' => $key, 'value' => $value]);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.group' => 'required|string',
            'settings.*.type' => 'required|string',
        ]);

        foreach ($validated['settings'] as $settingData) {
            Setting::set(
                $settingData['key'],
                $settingData['value'],
                $settingData['group'],
                $settingData['type']
            );
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => Setting::getAll()
        ]);
    }

    /**
     * Update a single setting
     */
    public function updateSingle(Request $request, string $key)
    {
        $validated = $request->validate([
            'value' => 'nullable',
            'group' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        $value = $validated['value'] ?? $setting->value;
        $group = $validated['group'] ?? $setting->group;
        $type = $validated['type'] ?? $setting->type;

        Setting::set($key, $value, $group, $type);

        return response()->json([
            'message' => 'Setting updated successfully',
            'setting' => [
                'key' => $key,
                'value' => Setting::get($key)
            ]
        ]);
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldLogo = Setting::get('site_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            // Store new logo
            $path = $request->file('logo')->store('settings', 'public');
            
            // Update setting
            Setting::set('site_logo', $path, 'site', 'image');

            return response()->json([
                'message' => 'Logo uploaded successfully',
                'path' => $path,
                'url' => Storage::url($path)
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    /**
     * Delete logo
     */
    public function deleteLogo()
    {
        $logo = Setting::get('site_logo');
        
        if ($logo && Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->delete($logo);
        }

        Setting::set('site_logo', null, 'site', 'image');

        return response()->json(['message' => 'Logo deleted successfully']);
    }

    /**
     * Upload team member image
     */
    public function uploadTeamMemberImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Store team member image
            $path = $request->file('image')->store('team_members', 'public');
            
            return response()->json([
                'message' => 'Team member image uploaded successfully',
                'path' => $path,
                'url' => Storage::url($path)
            ]);
        }

        return response()->json(['message' => 'No file uploaded'], 400);
    }

    /**
     * Delete team member image
     */
    public function deleteTeamMemberImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->path;
        
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['message' => 'Team member image deleted successfully']);
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'message' => 'nullable|string',
        ]);

        Setting::set('maintenance_mode', $validated['enabled'] ? '1' : '0', 'maintenance', 'boolean');
        
        if (isset($validated['message'])) {
            Setting::set('maintenance_message', $validated['message'], 'maintenance', 'textarea');
        }

        return response()->json([
            'message' => 'Maintenance mode updated successfully',
            'maintenance_mode' => (bool) $validated['enabled'],
        ]);
    }
}
