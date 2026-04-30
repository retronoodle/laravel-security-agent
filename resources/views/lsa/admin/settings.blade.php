@extends('lsa::admin.layout')

@section('content')
<h2 style="margin-bottom:1.25rem;">Settings</h2>

@if (session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert-error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ url(config('lsa.admin.path', 'lsa-admin') . '/settings') }}">
        @csrf

        <div style="margin-bottom:1.25rem;">
            <label for="model">Claude Model</label>
            <select id="model" name="model">
                @foreach ($availableModels as $m)
                    <option value="{{ $m }}" @selected($m === $currentModel)>{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:1.25rem;">
            <label for="api_key">Anthropic API Key</label>
            <input type="text"
                   id="api_key"
                   name="api_key"
                   placeholder="{{ $maskedKey ?? 'sk-ant-...' }}"
                   autocomplete="off">
            <p style="font-size:.8rem;color:#6c757d;margin-top:.35rem;">
                Leave blank to keep the current key.
                @if ($maskedKey) Current: <code>{{ $maskedKey }}</code> @endif
            </p>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
@endsection
