<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * One object that captures every filter/slice/group choice from the query string,
 * so the SAME selection drives every chart, pivot, table and export on the page.
 * This is what makes "filtering" and "slicing" uniform across the dashboard.
 */
class ReportFilter
{
    public Carbon $from;
    public Carbon $to;
    public ?int $dentistId;
    public ?int $serviceId;
    public ?string $category;
    public ?string $status;
    public ?string $type;       // 'walk_in' | 'booked' | null
    public string $bucket;      // time-series granularity: day|week|month
    public string $groupBy;     // dimension for the aggregation chart
    public string $measure;     // count|charged|collected

    public const DIMENSIONS = [
        'service' => 'Service',
        'category' => 'Category',
        'dentist' => 'Dentist',
        'status' => 'Status',
        'age_band' => 'Age band',
        'type' => 'Booking type',
        'weekday' => 'Day of week',
        'month' => 'Month',
    ];

    public const MEASURES = [
        'count' => 'Appointments',
        'charged' => 'Revenue charged (₱)',
        'collected' => 'Revenue collected (₱)',
    ];

    public const BUCKETS = ['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly'];

    public static function fromRequest(Request $request): self
    {
        $f = new self;

        $f->to = $request->filled('to') ? Carbon::parse($request->date('to'))->endOfDay() : now()->endOfDay();
        $f->from = $request->filled('from')
            ? Carbon::parse($request->date('from'))->startOfDay()
            : now()->copy()->subMonths(11)->startOfMonth();

        $f->dentistId = $request->filled('dentist_id') ? $request->integer('dentist_id') : null;
        $f->serviceId = $request->filled('service_id') ? $request->integer('service_id') : null;
        $f->category = $request->filled('category') ? $request->string('category')->toString() : null;
        $f->status = $request->filled('status') ? $request->string('status')->toString() : null;
        $f->type = in_array($request->input('type'), ['walk_in', 'booked'], true) ? $request->input('type') : null;

        $f->bucket = array_key_exists((string) $request->input('bucket'), self::BUCKETS) ? $request->input('bucket') : 'month';
        $f->groupBy = array_key_exists((string) $request->input('group_by'), self::DIMENSIONS) ? $request->input('group_by') : 'service';
        $f->measure = array_key_exists((string) $request->input('measure'), self::MEASURES) ? $request->input('measure') : 'collected';

        return $f;
    }

    /** Query-string array (for keeping filters across links/exports). */
    public function toQuery(): array
    {
        return array_filter([
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'dentist_id' => $this->dentistId,
            'service_id' => $this->serviceId,
            'category' => $this->category,
            'status' => $this->status,
            'type' => $this->type,
            'bucket' => $this->bucket,
            'group_by' => $this->groupBy,
            'measure' => $this->measure,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
