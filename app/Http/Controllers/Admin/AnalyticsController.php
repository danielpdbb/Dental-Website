<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Services\Analytics\ReportFilter;
use App\Services\Analytics\ReportService;
use App\Services\Analytics\SchedulingInsights;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $filter = ReportFilter::fromRequest($request);
        $service = new ReportService($filter);

        // Pivot: rows follow the chosen dimension (but avoid month × month), cols = month.
        $pivotRows = $filter->groupBy === 'month' ? 'category' : $filter->groupBy;

        return view('admin.analytics.index', [
            'filter' => $filter,
            'kpis' => $service->kpis(),
            'series' => $service->timeSeries(),
            'aggregate' => $service->aggregate($filter->groupBy),
            'serviceRevenue' => $service->lineItemRevenue('service'),
            'categoryMix' => $service->lineItemRevenue('category'),
            'paymentMix' => $service->paymentMethodMix(),
            'pivot' => $service->pivot($pivotRows, 'month', $filter->measure),
            'pivotRowsDim' => $pivotRows,
            'heatmap' => $service->demandHeatmap(),
            'insights' => (new SchedulingInsights($filter->from, $filter->to))->kpis(),
            'segments' => $service->segments(3),
            'appointments' => $service->tableQuery()->paginate(15)->withQueryString(),

            // Filter-bar option lists
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'serviceList' => Service::orderBy('name')->get(),
            'dimensions' => ReportFilter::DIMENSIONS,
            'measures' => ReportFilter::MEASURES,
            'buckets' => ReportFilter::BUCKETS,
            'statuses' => AppointmentStatus::options(),
            'categories' => ServiceCategory::options(),
        ]);
    }
}
