@extends('lsa::admin.layout')

@section('content')
<h2 style="margin-bottom:1.25rem;">Dashboard</h2>

<div class="stat-grid">
    <div class="stat-box">
        <div class="value">{{ $eventCount }}</div>
        <div class="label">Security Events</div>
    </div>
    <div class="stat-box">
        <div class="value">{{ $blockedIpCount }}</div>
        <div class="label">Blocked IPs</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;font-size:1rem;">Recent Events</h3>

    @if ($recentEvents->isEmpty())
        <p style="color:#6c757d;font-size:.9rem;">No security events recorded yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Pattern</th>
                    <th>Confidence</th>
                    <th>Outcome</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentEvents as $event)
                <tr>
                    <td>{{ $event->ip_address }}</td>
                    <td>{{ $event->pattern_type }}</td>
                    <td>{{ $event->confidence !== null ? number_format($event->confidence, 2) : '—' }}</td>
                    <td>{{ $event->outcome }}</td>
                    <td>{{ $event->created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
