<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Attendance History - Smart EmCa System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/brand-overrides.css') }}">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Century Gothic", CenturyGothic, AppleGothic, sans-serif; background: #f4f4f4; color: #333; }
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; background: #940000; color: #fff;
            position: sticky; top: 0; z-index: 10;
        }
        .topbar a { color: #fff; text-decoration: none; font-weight: 600; }
        .content { padding: 16px; max-width: 640px; margin: 0 auto; }
        .summary-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;
        }
        .summary-card {
            background: #fff; border-radius: 12px; padding: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center;
        }
        .summary-card strong { display: block; font-size: 1.4rem; color: #940000; }
        .summary-card span { font-size: 0.78rem; color: #888; text-transform: uppercase; }
        .user-card, .record-card {
            background: #fff; border-radius: 12px; padding: 14px 16px;
            margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .record-date { font-size: 0.8rem; color: #888; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; }
        .time-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .time-row:last-child { border-bottom: none; }
        .badge-in, .badge-out, .badge-late, .badge-ot {
            display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700;
        }
        .badge-in { background: #d4edda; color: #155724; }
        .badge-out { background: #e2e3e5; color: #383d41; }
        .badge-late { background: #fff3cd; color: #856404; }
        .badge-ot { background: #fde8e8; color: #940000; }
        .month-filter { margin-bottom: 12px; }
        .month-filter input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; }
        .replay-link { font-size: 0.82rem; color: #940000; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ route('staff.sign') }}"><i class="fa fa-arrow-left"></i> Back</a>
        <h1 style="margin:0;font-size:1rem;">My Attendance</h1>
        <span style="width:48px;"></span>
    </div>

    <div class="content">
        <div class="user-card">
            <strong>{{ Auth::user()->name }}</strong><br>
            <small class="text-muted">{{ Auth::user()->email }}</small>
        </div>

        <form class="month-filter" method="GET">
            <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()">
        </form>

        <div class="summary-grid">
            <div class="summary-card"><strong>{{ $summary['days_present'] }}</strong><span>Days Present</span></div>
            <div class="summary-card"><strong>{{ $summary['late_count'] }}</strong><span>Late Arrivals</span></div>
            <div class="summary-card"><strong>{{ floor($summary['total_minutes'] / 60) }}h {{ $summary['total_minutes'] % 60 }}m</strong><span>Total Hours</span></div>
            <div class="summary-card"><strong>{{ floor($summary['overtime_minutes'] / 60) }}h {{ $summary['overtime_minutes'] % 60 }}m</strong><span>Overtime</span></div>
        </div>

        @forelse($records as $record)
            <div class="record-card">
                <div class="record-date">{{ $record->signed_in_at?->format('l, d M Y') }}</div>
                <div class="time-row">
                    <div>
                        <span class="badge-in">IN</span>
                        <span style="margin-left:8px;font-weight:600;">{{ $record->signed_in_at?->format('h:i A') }}</span>
                        @if($record->is_late)<span class="badge-late" style="margin-left:6px;">LATE</span>@endif
                        @if($record->gps_flagged_in)<span class="badge-late" style="margin-left:4px;">GPS FLAG</span>@endif
                    </div>
                    @if($record->location_verified_in)<small style="color:#28a745;"><i class="fa fa-check-circle"></i> HQ</small>@endif
                </div>
                @if($record->signed_out_at)
                    <div class="time-row">
                        <div>
                            <span class="badge-out">OUT</span>
                            <span style="margin-left:8px;font-weight:600;">{{ $record->signed_out_at->format('h:i A') }}</span>
                            @if($record->is_early_out)<span class="badge-late" style="margin-left:6px;">EARLY</span>@endif
                            @if($record->auto_signed_out)<span class="badge-late" style="margin-left:4px;">AUTO</span>@endif
                            @if($record->overtime_minutes > 0)<span class="badge-ot" style="margin-left:4px;">OT {{ floor($record->overtime_minutes/60) }}h{{ $record->overtime_minutes%60 }}m</span>@endif
                        </div>
                    </div>
                    <div class="time-row">
                        <small>Worked: {{ $record->workingMinutes() }} min</small>
                        @if(!empty($record->path_trace))
                            <a class="replay-link" href="{{ route('staff.sign.replay', $record) }}"><i class="fa fa-map"></i> Replay path</a>
                        @endif
                    </div>
                @else
                    <div class="time-row"><small style="color:#940000;font-weight:600;"><i class="fa fa-clock-o"></i> Still signed in</small></div>
                @endif
            </div>
        @empty
            <div style="text-align:center;padding:40px;color:#888;">
                <i class="fa fa-calendar-o" style="font-size:2rem;"></i>
                <p>No records for this month.</p>
            </div>
        @endforelse
    </div>
</body>
</html>
