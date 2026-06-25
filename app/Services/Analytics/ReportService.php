<?php

namespace App\Services\Analytics;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProcedureStatus;
use App\Enums\ServiceCategory;
use App\Models\Appointment;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Rubix\ML\Clusterers\KMeans;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\ZScaleStandardizer;

/**
 * The reporting engine. One filtered base query (`base()`) feeds every report, so
 * every number on the dashboard respects the same filters/slice.
 *
 * Capabilities mapped to the brief:
 *  - aggregate()      → Aggregation (group + SUM/COUNT)
 *  - aggregate() + drill links in the view → Disaggregation
 *  - pivot()          → Pivoting (cross-tab)
 *  - base() filters   → Filtering + Slicing
 *  - timeSeries()     → Time Series
 *  - segments()       → Clustering (k-means on RFM)
 *  - tableQuery()     → Data Table (+ CSV/Excel export)
 */
class ReportService
{
    public function __construct(private ReportFilter $filter) {}

    /*
    |--------------------------------------------------------------------------
    | Base filtered query (appointments = the fact table)
    |--------------------------------------------------------------------------
    */
    private function base(): Builder
    {
        $paidSub = '(select appointment_id, sum(amount) as paid from payments where status = \'paid\' group by appointment_id)';

        $q = DB::table('appointments as a')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->leftJoin('users as d', 'd.id', '=', 'a.dentist_id')
            ->leftJoin('patients as p', 'p.id', '=', 'a.patient_id')
            ->leftJoin(DB::raw($paidSub.' as pay'), 'pay.appointment_id', '=', 'a.id')
            ->whereBetween('a.scheduled_at', [$this->filter->from, $this->filter->to]);

        if ($this->filter->dentistId) {
            $q->where('a.dentist_id', $this->filter->dentistId);
        }
        if ($this->filter->serviceId) {
            $q->where('a.service_id', $this->filter->serviceId);
        }
        if ($this->filter->category) {
            $q->where('s.category', $this->filter->category);
        }
        if ($this->filter->status) {
            $q->where('a.status', $this->filter->status);
        }
        if ($this->filter->type === 'walk_in') {
            $q->where('a.is_walk_in', true);
        } elseif ($this->filter->type === 'booked') {
            $q->where('a.is_walk_in', false);
        }

        return $q;
    }

    /*
    |--------------------------------------------------------------------------
    | KPIs (headline aggregation)
    |--------------------------------------------------------------------------
    */
    public function kpis(): array
    {
        // "Charged" / "Outstanding" only count appointments that were actually billed
        // (a bill exists or the visit completed). Booked/cancelled/no-show carry an
        // estimate but were never billed, so they no longer inflate what's owed —
        // cancelled/no-show value is shown separately as "Est. lost revenue".
        $billedStatuses = "a.status in ('billed', 'for_billing', 'in_treatment', 'completed')";

        $r = $this->base()->selectRaw(
            "count(*) as total,
             sum(a.status = 'completed') as completed,
             sum(a.status = 'no_show') as no_show,
             sum(a.status = 'cancelled') as cancelled,
             sum({$billedStatuses}) as billed_count,
             coalesce(sum(case when {$billedStatuses} then a.total_amount else 0 end), 0) as charged,
             coalesce(sum(pay.paid), 0) as collected"
        )->first();

        $total = (int) ($r->total ?? 0);
        $billedCount = (int) ($r->billed_count ?? 0);

        return [
            'appointments' => $total,
            'completed' => (int) ($r->completed ?? 0),
            'collected' => (float) ($r->collected ?? 0),
            'charged' => (float) ($r->charged ?? 0),
            'outstanding' => max(0, (float) ($r->charged ?? 0) - (float) ($r->collected ?? 0)),
            'noShowRate' => $total ? round(($r->no_show ?? 0) / $total * 100, 1) : 0,
            'cancellationRate' => $total ? round(($r->cancelled ?? 0) / $total * 100, 1) : 0,
            'avgBill' => $billedCount ? round(($r->charged ?? 0) / $billedCount, 2) : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregation by a chosen dimension (the bar/donut + table)
    |--------------------------------------------------------------------------
    */
    public function aggregate(string $dim): Collection
    {
        [$expr, $orderExpr] = $this->dimExpr($dim);

        $query = $this->base()
            ->selectRaw("$expr as label,
                count(*) as count,
                coalesce(sum(a.total_amount), 0) as charged,
                coalesce(sum(pay.paid), 0) as collected")
            ->groupBy(DB::raw($expr));

        if (in_array($dim, ['month', 'weekday'], true)) {
            $query->orderBy(DB::raw($orderExpr));
        } else {
            $query->orderByDesc(DB::raw('count'));
        }

        return $query->get()->map(fn ($r) => (object) [
            'key' => (string) $r->label,
            'label' => $this->prettyLabel($dim, (string) $r->label),
            'count' => (int) $r->count,
            'charged' => (float) $r->charged,
            'collected' => (float) $r->collected,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Time series (bucket by day/week/month)
    |--------------------------------------------------------------------------
    */
    public function timeSeries(): Collection
    {
        $fmt = match ($this->filter->bucket) {
            'day' => '%Y-%m-%d',
            'week' => '%x-W%v',
            default => '%Y-%m',
        };

        return $this->base()
            ->selectRaw("DATE_FORMAT(a.scheduled_at, '$fmt') as period,
                count(*) as count,
                coalesce(sum(a.total_amount), 0) as charged,
                coalesce(sum(pay.paid), 0) as collected,
                sum(a.status = 'completed') as completed,
                sum(a.status = 'no_show') as no_show,
                sum(a.status = 'cancelled') as cancelled,
                sum(a.status = 'booked') as booked")
            ->groupBy(DB::raw('period'))
            ->orderBy(DB::raw('period'))
            ->get()
            ->map(fn ($r) => (object) [
                'period' => $r->period,
                'count' => (int) $r->count,
                'charged' => (float) $r->charged,
                'collected' => (float) $r->collected,
                'completed' => (int) $r->completed,
                'no_show' => (int) $r->no_show,
                'cancelled' => (int) $r->cancelled,
                'booked' => (int) $r->booked,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pivot (cross-tab: rows × cols, one measure)
    |--------------------------------------------------------------------------
    */
    public function pivot(string $rowDim, string $colDim, string $measure): array
    {
        [$rowExpr] = $this->dimExpr($rowDim);
        [$colExpr, $colOrder] = $this->dimExpr($colDim);
        $measureExpr = $this->measureExpr($measure);

        $rows = $this->base()
            ->selectRaw("$rowExpr as r, $colExpr as c, $measureExpr as v")
            ->groupBy(DB::raw($rowExpr), DB::raw($colExpr))
            ->get();

        $cols = $rows->pluck('c')->unique()->sort()->values();
        $rowKeys = $rows->pluck('r')->unique()->sort()->values();

        // grid[row][col] = value
        $grid = [];
        foreach ($rows as $cell) {
            $grid[(string) $cell->r][(string) $cell->c] = (float) $cell->v;
        }

        $matrix = [];
        $colTotals = array_fill_keys($cols->map(fn ($c) => (string) $c)->all(), 0.0);
        foreach ($rowKeys as $r) {
            $line = ['label' => $this->prettyLabel($rowDim, (string) $r), 'cells' => [], 'total' => 0.0];
            foreach ($cols as $c) {
                $v = $grid[(string) $r][(string) $c] ?? 0.0;
                $line['cells'][(string) $c] = $v;
                $line['total'] += $v;
                $colTotals[(string) $c] += $v;
            }
            $matrix[] = $line;
        }

        return [
            'cols' => $cols->map(fn ($c) => ['key' => (string) $c, 'label' => $this->prettyLabel($colDim, (string) $c)])->all(),
            'rows' => $matrix,
            'colTotals' => $colTotals,
            'grandTotal' => array_sum($colTotals),
            'measure' => $measure,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue from PROCEDURE LINE ITEMS (accurate per-service / per-category)
    |--------------------------------------------------------------------------
    | Sums performed procedures on completed (paid) appointments, so a multi-service
    | visit's revenue is attributed to each individual service.
    */
    public function lineItemRevenue(string $dim): Collection
    {
        $expr = $dim === 'category'
            ? "coalesce(s.category, 'other')"
            : "coalesce(s.name, ap.procedure_name)";

        $q = DB::table('appointment_procedures as ap')
            ->join('appointments as a', 'a.id', '=', 'ap.appointment_id')
            ->leftJoin('services as s', 's.id', '=', 'ap.service_id')
            ->where('a.status', AppointmentStatus::Completed->value)
            ->where('ap.status', ProcedureStatus::Performed->value)
            ->whereBetween('a.scheduled_at', [$this->filter->from, $this->filter->to]);

        if ($this->filter->dentistId) {
            $q->where('a.dentist_id', $this->filter->dentistId);
        }
        if ($this->filter->category) {
            $q->where('s.category', $this->filter->category);
        }
        if ($this->filter->type === 'walk_in') {
            $q->where('a.is_walk_in', true);
        } elseif ($this->filter->type === 'booked') {
            $q->where('a.is_walk_in', false);
        }

        return $q->selectRaw("$expr as label, sum(ap.price) as revenue, count(*) as cnt")
            ->groupBy(DB::raw($expr))
            ->orderByDesc(DB::raw('revenue'))
            ->get()
            ->map(fn ($r) => (object) [
                'label' => $this->prettyLabel($dim, (string) $r->label),
                'revenue' => (float) $r->revenue,
                'count' => (int) $r->cnt,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Demand heatmap (day-of-week × hour) — also informs scheduling
    |--------------------------------------------------------------------------
    */
    public function demandHeatmap(): array
    {
        $rows = $this->base()
            ->selectRaw('DAYOFWEEK(a.scheduled_at) as dow, HOUR(a.scheduled_at) as hr, count(*) as count')
            ->groupBy(DB::raw('dow'), DB::raw('hr'))
            ->get();

        $days = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
        $hours = range((int) substr(config('clinic.open_time', '09:00'), 0, 2), (int) substr(config('clinic.close_time', '17:00'), 0, 2) - 1);

        $grid = [];
        $max = 0;
        foreach ($rows as $r) {
            $grid[(int) $r->dow][(int) $r->hr] = (int) $r->count;
            $max = max($max, (int) $r->count);
        }

        return ['days' => $days, 'hours' => $hours, 'grid' => $grid, 'max' => $max];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment-method mix (donut) — from the payments side
    |--------------------------------------------------------------------------
    */
    public function paymentMethodMix(): Collection
    {
        $q = DB::table('payments as pm')
            ->join('appointments as a', 'a.id', '=', 'pm.appointment_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->where('pm.status', 'paid')
            ->whereBetween('a.scheduled_at', [$this->filter->from, $this->filter->to]);

        if ($this->filter->dentistId) {
            $q->where('a.dentist_id', $this->filter->dentistId);
        }
        if ($this->filter->serviceId) {
            $q->where('a.service_id', $this->filter->serviceId);
        }
        if ($this->filter->category) {
            $q->where('s.category', $this->filter->category);
        }

        return $q->selectRaw('pm.method as label, sum(pm.amount) as total, count(*) as cnt')
            ->groupBy('pm.method')
            ->get()
            ->map(fn ($r) => (object) [
                'label' => PaymentMethod::tryFrom($r->label)?->label() ?? ucfirst((string) $r->label),
                'total' => (float) $r->total,
                'count' => (int) $r->cnt,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Data table (paginated raw rows) + export query
    |--------------------------------------------------------------------------
    */
    public function tableQuery()
    {
        return Appointment::query()
            ->with(['patient', 'dentist', 'service'])
            ->whereBetween('scheduled_at', [$this->filter->from, $this->filter->to])
            ->when($this->filter->dentistId, fn ($q) => $q->where('dentist_id', $this->filter->dentistId))
            ->when($this->filter->serviceId, fn ($q) => $q->where('service_id', $this->filter->serviceId))
            ->when($this->filter->status, fn ($q) => $q->where('status', $this->filter->status))
            ->when($this->filter->type === 'walk_in', fn ($q) => $q->where('is_walk_in', true))
            ->when($this->filter->type === 'booked', fn ($q) => $q->where('is_walk_in', false))
            ->when($this->filter->category, fn ($q) => $q->whereHas('service', fn ($s) => $s->where('category', $this->filter->category)))
            ->orderByDesc('scheduled_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Clustering — patient segments via k-means on RFM
    |--------------------------------------------------------------------------
    */
    public function segments(int $k = 3): ?array
    {
        $paidSub = '(select appointment_id, sum(amount) as paid from payments where status = \'paid\' group by appointment_id)';

        $rows = DB::table('appointments as a')
            ->leftJoin(DB::raw($paidSub.' as pay'), 'pay.appointment_id', '=', 'a.id')
            ->whereNotNull('a.patient_id')
            ->whereBetween('a.scheduled_at', [$this->filter->from, $this->filter->to])
            ->selectRaw('a.patient_id,
                count(*) as frequency,
                coalesce(sum(pay.paid), 0) as monetary,
                datediff(curdate(), max(a.scheduled_at)) as recency')
            ->groupBy('a.patient_id')
            ->get();

        if ($rows->count() < $k) {
            return null; // not enough patients to cluster meaningfully
        }

        try {
            $samples = $rows->map(fn ($r) => [(float) $r->recency, (float) $r->frequency, (float) $r->monetary])->all();

            // Standardize so recency/frequency/monetary weigh fairly.
            $dataset = new Unlabeled($samples);
            $dataset->apply(new ZScaleStandardizer);

            $estimator = new KMeans($k);
            $estimator->train($dataset);
            $labels = $estimator->predict($dataset);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        // Group raw (un-standardized) values by cluster for human-readable summaries.
        $clusters = [];
        $points = [];
        foreach ($rows as $i => $r) {
            $c = (int) $labels[$i];
            $clusters[$c][] = $r;
            $points[] = ['x' => (float) $r->frequency, 'y' => (float) $r->monetary, 'r' => (float) $r->recency, 'cluster' => $c];
        }

        $summary = [];
        foreach ($clusters as $c => $members) {
            $coll = collect($members);
            $summary[] = [
                'cluster' => $c,
                'size' => $coll->count(),
                'avg_recency' => round($coll->avg('recency'), 0),
                'avg_frequency' => round($coll->avg('frequency'), 1),
                'avg_monetary' => round($coll->avg('monetary'), 2),
            ];
        }

        // Name segments heuristically from their averages.
        $summary = $this->nameSegments($summary);

        return ['k' => $k, 'summary' => $summary, 'points' => $points];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function dimExpr(string $dim): array
    {
        return match ($dim) {
            'service' => ["coalesce(s.name, 'Unknown')", 'label'],
            'category' => ["coalesce(s.category, 'other')", 'label'],
            'dentist' => ["coalesce(d.name, 'Unassigned')", 'label'],
            'status' => ['a.status', 'label'],
            'type' => ["case when a.is_walk_in = 1 then 'Walk-in' else 'Booked' end", 'label'],
            'age_band' => [$this->ageBandSql(), 'label'],
            'weekday' => ['DAYNAME(a.scheduled_at)', 'DAYOFWEEK(a.scheduled_at)'],
            'month' => ["DATE_FORMAT(a.scheduled_at, '%Y-%m')", 'label'],
            default => ["coalesce(s.name, 'Unknown')", 'label'],
        };
    }

    private function measureExpr(string $measure): string
    {
        return match ($measure) {
            'charged' => 'coalesce(sum(a.total_amount), 0)',
            'collected' => 'coalesce(sum(pay.paid), 0)',
            default => 'count(*)',
        };
    }

    private function ageBandSql(): string
    {
        return "case
            when p.date_of_birth is null then 'Unknown'
            when timestampdiff(year, p.date_of_birth, curdate()) < 13 then '0-12'
            when timestampdiff(year, p.date_of_birth, curdate()) < 19 then '13-18'
            when timestampdiff(year, p.date_of_birth, curdate()) < 36 then '19-35'
            when timestampdiff(year, p.date_of_birth, curdate()) < 56 then '36-55'
            else '56+' end";
    }

    private function prettyLabel(string $dim, string $value): string
    {
        return match ($dim) {
            'category' => ServiceCategory::tryFrom($value)?->label() ?? ucfirst($value),
            'status' => AppointmentStatus::tryFrom($value)?->label() ?? ucfirst($value),
            default => $value === '' ? '—' : $value,
        };
    }

    /**
     * Turn cluster averages into friendly RFM segment names.
     */
    private function nameSegments(array $summary): array
    {
        if (empty($summary)) {
            return $summary;
        }

        $maxMonetary = max(array_column($summary, 'avg_monetary')) ?: 1;
        $maxRecency = max(array_column($summary, 'avg_recency')) ?: 1;

        foreach ($summary as &$s) {
            $highValue = $s['avg_monetary'] >= 0.5 * $maxMonetary;
            $lapsing = $s['avg_recency'] >= 0.5 * $maxRecency;
            $frequent = $s['avg_frequency'] >= 2;

            $s['name'] = match (true) {
                $highValue && $frequent && ! $lapsing => 'Loyal high-value',
                $lapsing && ($highValue || $frequent) => 'At-risk / lapsing',
                ! $frequent && ! $lapsing => 'New / occasional',
                default => 'Regular',
            };
        }

        return $summary;
    }
}
