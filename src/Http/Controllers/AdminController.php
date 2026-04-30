<?php

namespace Timmonaghan\SecurityAgent\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Timmonaghan\SecurityAgent\Services\EnvWriter;

class AdminController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('lsa::admin.login');
    }

    public function login(Request $request)
    {
        $password = config('lsa.admin.password');
        $path = config('lsa.admin.path', 'lsa-admin');

        if (! $password) {
            abort(403);
        }

        if ($request->input('password') === $password) {
            $request->session()->put('lsa_admin_authenticated', true);
            return redirect("/{$path}");
        }

        return back()->withErrors(['password' => 'Incorrect password.']);
    }

    public function dashboard(Request $request)
    {
        $eventCount = DB::table('lsa_security_events')->count();
        $blockedIpCount = DB::table('lsa_ip_blocklist')->count();
        $recentEvents = DB::table('lsa_security_events')
            ->select(['ip_address', 'pattern_type', 'confidence', 'outcome', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('lsa::admin.dashboard', compact('eventCount', 'blockedIpCount', 'recentEvents'));
    }

    public function showSettings(Request $request)
    {
        $currentModel = config('lsa.model', 'claude-sonnet-4-6');
        $availableModels = config('lsa.admin.available_models', []);

        $rawKey = env('LSA_ANTHROPIC_API_KEY') ?: config('security-agent.anthropic_api_key');
        $maskedKey = $rawKey ? substr($rawKey, 0, 10) . '****' : null;

        return view('lsa::admin.settings', compact('currentModel', 'availableModels', 'maskedKey'));
    }

    public function saveSettings(Request $request, EnvWriter $envWriter)
    {
        $request->validate([
            'model' => 'required|string',
            'api_key' => 'nullable|string',
        ]);

        $availableModels = config('lsa.admin.available_models', []);
        if (! in_array($request->input('model'), $availableModels, true)) {
            return back()->withErrors(['model' => 'Invalid model selected.']);
        }

        $envWriter->set('LSA_MODEL', $request->input('model'));

        if ($request->filled('api_key')) {
            $envWriter->set('LSA_ANTHROPIC_API_KEY', $request->input('api_key'));
        }

        return back()->with('success', 'Settings saved.');
    }
}
