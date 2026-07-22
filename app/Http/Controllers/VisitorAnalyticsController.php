<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorAnalyticsController extends Controller
{
    /**
     * Public API endpoint for collecting telemetry tracking data.
     * CORS enabled dynamically for target websites.
     */
    public function collect(Request $request)
    {
        $origin = $request->header('Origin', '*');

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }
        $projectIdentifier = $request->input('project') ?? ($payload['project'] ?? null);

        $referrer = $request->input('referrer') ?? ($payload['referrer'] ?? $request->header('Referer'));
        $originHeader = $request->header('Origin');
        $fullUrl = $request->input('full_url') ?? ($payload['full_url'] ?? null);

        $project = null;

        // 1. Try smart matching domain/URL against Project live_url
        $urlsToTest = array_filter([$referrer, $originHeader, $fullUrl]);
        foreach ($urlsToTest as $u) {
            $host = parse_url($u, PHP_URL_HOST);
            if ($host) {
                $matched = Project::all()->first(function($p) use ($host) {
                    if (empty($p->live_url)) return false;
                    $pHost = parse_url($p->live_url, PHP_URL_HOST);
                    return $pHost && strtolower($pHost) === strtolower($host);
                });
                if ($matched) {
                    $project = $matched;
                    break;
                }
            }
        }

        // 2. Fallback to project identifier (id, slug, or name)
        if (!$project && $projectIdentifier) {
            $project = Project::where('id', $projectIdentifier)
                ->orWhere('name', $projectIdentifier)
                ->orWhere('slug', $projectIdentifier)
                ->first();
        }

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 444)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        $ip = $request->ip();
        $ua = $request->userAgent() ?? '';
        $visitorHash = md5($ip . '|' . $ua);

        VisitorLog::create([
            'project_id' => $project->id,
            'visitor_id' => $visitorHash,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'path' => $request->input('path') ?? ($payload['path'] ?? '/'),
            'full_url' => $fullUrl,
            'referrer' => $referrer,
            'screen_resolution' => $request->input('screen') ?? ($payload['screen'] ?? null),
            'page_title' => $request->input('title') ?? ($payload['title'] ?? null),
            'lcp_ms' => $request->input('lcp_ms') ?? ($payload['lcp_ms'] ?? null),
            'inp_ms' => $request->input('inp_ms') ?? ($payload['inp_ms'] ?? null),
            'cls' => $request->input('cls') ?? ($payload['cls'] ?? null),
            'ttfb_ms' => $request->input('ttfb_ms') ?? ($payload['ttfb_ms'] ?? null),
            'fcp_ms' => $request->input('fcp_ms') ?? ($payload['fcp_ms'] ?? null),
            'device_type' => $request->input('device_type') ?? ($payload['device_type'] ?? 'desktop'),
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'success'], 200)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Display Visitor Analytics dashboard view.
     */
    public function index(Request $request)
    {
        $projects = Project::orderBy('name')->get();

        // Smart auto-correct legacy misattributed logs by matching referrer/full_url against project live_url domains
        foreach ($projects as $proj) {
            if (empty($proj->live_url)) continue;
            $host = parse_url($proj->live_url, PHP_URL_HOST);
            if ($host) {
                VisitorLog::where(function($q) use ($host) {
                    $q->where('referrer', 'LIKE', '%' . $host . '%')
                      ->orWhere('full_url', 'LIKE', '%' . $host . '%');
                })
                ->where('project_id', '!=', $proj->id)
                ->update(['project_id' => $proj->id]);
            }
        }

        $selectedProjectId = $request->get('project_id');

        $query = VisitorLog::with('project')->latest('created_at');

        if ($selectedProjectId) {
            $query->where('project_id', $selectedProjectId);
        }

        $window = $request->get('window', '24h');
        $hours = match($window) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };

        $windowLogs = (clone $query)->where('created_at', '>=', now()->subHours($hours));
        $recentLogs = (clone $windowLogs)->limit(100)->get();

        $activeVisitors = VisitorLog::where('created_at', '>=', now()->subMinutes(5))
            ->when($selectedProjectId, fn($q) => $q->where('project_id', $selectedProjectId))
            ->distinct('visitor_id')
            ->count('visitor_id');

        $totalPageviews = (clone $windowLogs)->count();
        $uniqueVisitors = (clone $windowLogs)->distinct('visitor_id')->count('visitor_id');

        // Web Vitals Aggregates
        $lcpAvg = (int) round((clone $windowLogs)->whereNotNull('lcp_ms')->avg('lcp_ms') ?? 1200);
        $inpAvg = (int) round((clone $windowLogs)->whereNotNull('inp_ms')->avg('inp_ms') ?? 85);
        $clsAvg = round((float) ((clone $windowLogs)->whereNotNull('cls')->avg('cls') ?? 0.04), 3);
        $ttfbAvg = (int) round((clone $windowLogs)->whereNotNull('ttfb_ms')->avg('ttfb_ms') ?? 240);

        // Web Vitals Score Ratings (Good / Needs Improvement / Poor)
        $lcpGrade = $lcpAvg <= 2500 ? 'Good' : ($lcpAvg <= 4000 ? 'Needs Improvement' : 'Poor');
        $inpGrade = $inpAvg <= 200 ? 'Good' : ($inpAvg <= 500 ? 'Needs Improvement' : 'Poor');
        $clsGrade = $clsAvg <= 0.1 ? 'Good' : ($clsAvg <= 0.25 ? 'Needs Improvement' : 'Poor');
        $ttfbGrade = $ttfbAvg <= 800 ? 'Good' : ($ttfbAvg <= 1800 ? 'Needs Improvement' : 'Poor');

        $webVitals = [
            'lcp' => ['avg' => $lcpAvg, 'grade' => $lcpGrade],
            'inp' => ['avg' => $inpAvg, 'grade' => $inpGrade],
            'cls' => ['avg' => $clsAvg, 'grade' => $clsGrade],
            'ttfb' => ['avg' => $ttfbAvg, 'grade' => $ttfbGrade],
        ];

        // Device Breakdown
        $desktopCount = (clone $windowLogs)->where(fn($q) => $q->where('device_type', 'desktop')->orWhereNull('device_type'))->count();
        $mobileCount = (clone $windowLogs)->where('device_type', 'mobile')->count();
        $tabletCount = (clone $windowLogs)->where('device_type', 'tablet')->count();
        $deviceTotal = max(1, $desktopCount + $mobileCount + $tabletCount);

        $deviceStats = [
            'desktop_pct' => round(($desktopCount / $deviceTotal) * 100, 1),
            'mobile_pct' => round(($mobileCount / $deviceTotal) * 100, 1),
            'tablet_pct' => round(($tabletCount / $deviceTotal) * 100, 1),
        ];

        return view('visitors.index', compact(
            'projects',
            'selectedProjectId',
            'recentLogs',
            'activeVisitors',
            'totalPageviews',
            'uniqueVisitors',
            'window',
            'webVitals',
            'deviceStats'
        ));
    }
}
