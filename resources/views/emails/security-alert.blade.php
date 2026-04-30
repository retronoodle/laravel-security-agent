Security Alert — Suspicious Activity Detected
=============================================

Time:         {{ $timestamp->toDateTimeString() }} UTC
IP Address:   {{ $ip }}
Pattern Type: {{ $patternType }}
Confidence:   {{ number_format($confidence * 100, 1) }}%

Summary
-------
{{ $summary }}

---
This alert was generated automatically by the Laravel Security Agent.
Review your security_events table for full details and audit history.
