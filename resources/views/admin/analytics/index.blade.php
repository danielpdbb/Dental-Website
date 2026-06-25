@extends('layouts.admin')

@section('title', 'Analytics')
@section('heading', 'Analytics & reports')

@php
    $isMoney = $filter->measure !== 'count';
    $measureLabel = $measures[$filter->measure];
    $fmtMeasure = fn ($v) => $isMoney ? '₱'.number_format($v, 2) : number_format($v);
@endphp

@section('content')
  {{-- Async: the filter + pagination swap just this block; charts redraw on htmx:afterSettle. --}}
  <div id="analytics" hx-boost="true" hx-target="#analytics" hx-select="#analytics" hx-swap="outerHTML" hx-push-url="true">
    {{-- ============ FILTER BAR (drives every report below) ============ --}}
    <form method="GET" action="{{ route('admin.analytics') }}"
          class="rounded-2xl bg-white border border-slate-200/60 p-4 shadow-soft">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $filter->from->toDateString() }}" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $filter->to->toDateString() }}" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm outline-none focus:border-brand-blue" />
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Dentist</label>
                <select name="dentist_id" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    <option value="">All</option>
                    @foreach ($dentists as $d)
                        <option value="{{ $d->id }}" @selected($filter->dentistId === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Service</label>
                <select name="service_id" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    <option value="">All</option>
                    @foreach ($serviceList as $s)
                        <option value="{{ $s->id }}" @selected($filter->serviceId === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Category</label>
                <select name="category" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    <option value="">All</option>
                    @foreach ($categories as $val => $lbl)
                        <option value="{{ $val }}" @selected($filter->category === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    <option value="">All</option>
                    @foreach ($statuses as $val => $lbl)
                        <option value="{{ $val }}" @selected($filter->status === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Type</label>
                <select name="type" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    <option value="">All</option>
                    <option value="booked" @selected($filter->type === 'booked')>Booked</option>
                    <option value="walk_in" @selected($filter->type === 'walk_in')>Walk-in</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Group by</label>
                <select name="group_by" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    @foreach ($dimensions as $val => $lbl)
                        <option value="{{ $val }}" @selected($filter->groupBy === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Measure</label>
                <select name="measure" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    @foreach ($measures as $val => $lbl)
                        <option value="{{ $val }}" @selected($filter->measure === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Time bucket</label>
                <select name="bucket" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm">
                    @foreach ($buckets as $val => $lbl)
                        <option value="{{ $val }}" @selected($filter->bucket === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="h-10 px-4 rounded-lg gradient-brand text-white text-sm font-semibold hover:opacity-90 transition">Apply</button>
                <a href="{{ route('admin.analytics') }}" class="h-10 px-4 inline-flex items-center rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">Reset</a>
            </div>
        </div>
    </form>

    {{-- ============ KPI CARDS (aggregation) ============ --}}
    <div class="mt-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Appointments</div>
            <div class="mt-1 font-display text-3xl font-bold">{{ number_format($kpis['appointments']) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ number_format($kpis['completed']) }} completed</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Revenue collected</div>
            <div class="mt-1 font-display text-3xl font-bold text-emerald-600">₱{{ number_format($kpis['collected'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">Avg bill ₱{{ number_format($kpis['avgBill'], 2) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">Outstanding</div>
            <div class="mt-1 font-display text-3xl font-bold text-red-500">₱{{ number_format($kpis['outstanding'], 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">of ₱{{ number_format($kpis['charged'], 2) }} charged</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <div class="text-xs uppercase tracking-wider text-slate-400">No-show / cancel</div>
            <div class="mt-1 font-display text-3xl font-bold text-slate-700">{{ $kpis['noShowRate'] }}% <span class="text-lg text-slate-400">/ {{ $kpis['cancellationRate'] }}%</span></div>
            <div class="text-xs text-slate-400 mt-1">of all appointments</div>
        </div>
    </div>

    {{-- ============ DECISION-TREE SCHEDULING KPIs ============ --}}
    <div class="mt-8">
        <div class="flex items-center gap-2 mb-3">
            <h3 class="font-display text-lg font-bold">Scheduling insights</h3>
            <span class="text-xs text-slate-400">· Decision Tree · {{ $filter->from->format('M j') }}–{{ $filter->to->format('M j, Y') }}</span>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Busiest day</div>
                <div class="mt-1 font-display text-2xl font-bold">{{ $insights['busiestDay']['label'] ?? '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $insights['busiestDay']['count'] ?? 0 }} appointments</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Peak hour</div>
                <div class="mt-1 font-display text-2xl font-bold">{{ $insights['peakHour']['label'] ?? '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $insights['peakHour']['count'] ?? 0 }} bookings</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">No-show risk</div>
                <div class="mt-1 font-display text-2xl font-bold text-amber-600">{{ $insights['noShowPct'] }}%</div>
                <div class="text-xs text-slate-400 mt-1">missed / cancelled</div>
            </div>
            <div class="rounded-2xl bg-white border border-red-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Est. lost revenue</div>
                <div class="mt-1 font-display text-2xl font-bold text-red-500">₱{{ number_format($insights['lostRevenue'], 2) }}</div>
                <div class="text-xs text-slate-400 mt-1">from {{ $insights['missed'] }} cancelled / no-show</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Schedule efficiency</div>
                <div class="mt-1 font-display text-2xl font-bold text-brand-blue">{{ $insights['efficiency'] }}%</div>
                <div class="text-xs text-slate-400 mt-1">of chair time used</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Highest workload</div>
                <div class="mt-1 font-display text-xl font-bold">{{ $insights['topDentist']['name'] ?? '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $insights['topDentist']['count'] ?? 0 }} visits · {{ $insights['topDentist']['hours'] ?? 0 }} h</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Most underused slot</div>
                <div class="mt-1 font-display text-xl font-bold">{{ $insights['underused']['label'] ?? '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $insights['underused']['count'] ?? 0 }} bookings — promote it</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Most booked procedure</div>
                <div class="mt-1 font-display text-xl font-bold">{{ $insights['topProcedure']['name'] ?? '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $insights['topProcedure']['count'] ?? 0 }} times</div>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <div class="text-xs uppercase tracking-wider text-slate-400">Appointments in range</div>
                <div class="mt-1 font-display text-2xl font-bold">{{ number_format($insights['total']) }}</div>
                <div class="text-xs text-slate-400 mt-1">all statuses</div>
            </div>
        </div>

        {{-- Demand bars (weekday + hour) --}}
        <div class="mt-4 grid lg:grid-cols-2 gap-4">
            @foreach ([['Demand by day', $insights['byDay']], ['Demand by hour', $insights['byHour']]] as [$title, $ds])
                @php $maxC = max(1, max($ds['counts'] ?: [0])); @endphp
                <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                    <div class="text-xs uppercase tracking-wider text-slate-400 mb-3">{{ $title }}</div>
                    <div class="flex items-end gap-1.5 h-32">
                        @foreach ($ds['counts'] as $idx => $count)
                            <div class="flex-1 flex flex-col items-center justify-end h-full">
                                <div class="w-full rounded-t bg-brand-blue/70" style="height: {{ $count > 0 ? max(4, round($count / $maxC * 100)) : 1 }}%" title="{{ $count }}"></div>
                                <div class="text-[9px] text-slate-400 mt-1 truncate w-full text-center">{{ $ds['labels'][$idx] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============ TIME SERIES + STATUS MIX ============ --}}
    <div class="mt-5 grid lg:grid-cols-2 gap-5">
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-3">Trend over time ({{ $buckets[$filter->bucket] }})</h3>
            <canvas id="trendChart" height="200"></canvas>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-3">Appointment status mix</h3>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>

    {{-- ============ REVENUE BY DENTAL SERVICE (bar) ============ --}}
    <div class="mt-5 rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
        <h3 class="font-display text-lg font-bold mb-1">Revenue by dental service</h3>
        <p class="text-xs text-slate-400 mb-3">From performed procedures on completed &amp; paid visits (accurate per service, even on multi-service appointments).</p>
        <canvas id="serviceRevenueChart" height="110"></canvas>
    </div>

    {{-- ============ AGGREGATION (bar + drill table) + DONUTS ============ --}}
    <div class="mt-5 grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-1">{{ $measureLabel }} by {{ $dimensions[$filter->groupBy] }}</h3>
            <p class="text-xs text-slate-400 mb-3">Aggregation — change “Group by” / “Measure” above to disaggregate.</p>
            <canvas id="aggChart" height="120"></canvas>

            <table class="w-full text-sm mt-4">
                <thead class="text-slate-400 text-left text-xs uppercase tracking-wider border-b border-slate-100">
                    <tr><th class="py-1.5">{{ $dimensions[$filter->groupBy] }}</th><th class="py-1.5 text-right">Appts</th><th class="py-1.5 text-right">Charged</th><th class="py-1.5 text-right">Collected</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($aggregate as $row)
                        @php
                            $drill = in_array($filter->groupBy, ['category','status','type'])
                                ? request()->fullUrlWithQuery([$filter->groupBy === 'type' ? 'type' : $filter->groupBy => $row->key])
                                : null;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-2">
                                @if ($drill)<a href="{{ $drill }}" class="text-brand-blue hover:underline">{{ $row->label }}</a>
                                @else {{ $row->label }} @endif
                            </td>
                            <td class="py-2 text-right">{{ number_format($row->count) }}</td>
                            <td class="py-2 text-right">₱{{ number_format($row->charged, 2) }}</td>
                            <td class="py-2 text-right text-emerald-600">₱{{ number_format($row->collected, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-400">No data for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="space-y-5">
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <h3 class="font-display text-base font-bold mb-3">Revenue by category</h3>
                <canvas id="categoryChart" height="180"></canvas>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
                <h3 class="font-display text-base font-bold mb-3">Payment method mix</h3>
                <canvas id="paymentChart" height="180"></canvas>
            </div>
        </div>
    </div>

    {{-- ============ PIVOT (cross-tab: rows × month) ============ --}}
    <div class="mt-5 rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft overflow-x-auto">
        <h3 class="font-display text-lg font-bold mb-1">Pivot — {{ $dimensions[$pivotRowsDim] }} × Month ({{ $measureLabel }})</h3>
        <p class="text-xs text-slate-400 mb-3">Cross-tabulation of the selected measure.</p>
        <table class="w-full text-sm whitespace-nowrap">
            <thead class="text-slate-400 text-left text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                    <th class="py-2 pr-4">{{ $dimensions[$pivotRowsDim] }}</th>
                    @foreach ($pivot['cols'] as $col)
                        <th class="py-2 px-3 text-right">{{ $col['label'] }}</th>
                    @endforeach
                    <th class="py-2 pl-3 text-right font-bold">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pivot['rows'] as $prow)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2 pr-4 font-medium">{{ $prow['label'] }}</td>
                        @foreach ($pivot['cols'] as $col)
                            <td class="py-2 px-3 text-right text-slate-600">{{ $fmtMeasure($prow['cells'][$col['key']] ?? 0) }}</td>
                        @endforeach
                        <td class="py-2 pl-3 text-right font-bold">{{ $fmtMeasure($prow['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="99" class="py-6 text-center text-slate-400">No data for this filter.</td></tr>
                @endforelse
            </tbody>
            @if (count($pivot['rows']))
                <tfoot>
                    <tr class="border-t-2 border-slate-200 font-bold">
                        <td class="py-2 pr-4">Total</td>
                        @foreach ($pivot['cols'] as $col)
                            <td class="py-2 px-3 text-right">{{ $fmtMeasure($pivot['colTotals'][$col['key']] ?? 0) }}</td>
                        @endforeach
                        <td class="py-2 pl-3 text-right">{{ $fmtMeasure($pivot['grandTotal']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- ============ DEMAND HEATMAP (day × hour) ============ --}}
    <div class="mt-5 rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft overflow-x-auto">
        <h3 class="font-display text-lg font-bold mb-1">Demand heatmap — day × hour</h3>
        <p class="text-xs text-slate-400 mb-3">Darker = busier. Feeds the predictive-scheduling model later.</p>
        <table class="text-xs">
            <thead>
                <tr>
                    <th class="p-1 text-left text-slate-400"></th>
                    @foreach ($heatmap['hours'] as $h)
                        <th class="p-1 text-slate-400 font-medium w-12 text-center">{{ sprintf('%02d:00', $h) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($heatmap['days'] as $dow => $dayLabel)
                    <tr>
                        <td class="p-1 pr-2 text-slate-500 font-medium">{{ $dayLabel }}</td>
                        @foreach ($heatmap['hours'] as $h)
                            @php
                                $count = $heatmap['grid'][$dow][$h] ?? 0;
                                $intensity = $heatmap['max'] > 0 ? $count / $heatmap['max'] : 0;
                            @endphp
                            <td class="p-1 text-center rounded"
                                style="background-color: rgba(59,130,246,{{ round($intensity * 0.85 + ($count ? 0.06 : 0), 2) }}); color: {{ $intensity > 0.5 ? '#fff' : '#475569' }};"
                                title="{{ $dayLabel }} {{ sprintf('%02d:00', $h) }} — {{ $count }} appt(s)">
                                {{ $count ?: '' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ============ CLUSTERING (patient segments) ============ --}}
    <div class="mt-5 grid lg:grid-cols-2 gap-5">
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-1">Patient segments (k-means)</h3>
            <p class="text-xs text-slate-400 mb-3">Clustering on RFM — Recency, Frequency, Monetary.</p>
            @if ($segments)
                <canvas id="clusterChart" height="200"></canvas>
            @else
                <p class="py-10 text-center text-sm text-slate-400">Not enough patient history in this range to cluster.</p>
            @endif
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
            <h3 class="font-display text-lg font-bold mb-3">Segment summary</h3>
            @if ($segments)
                <table class="w-full text-sm">
                    <thead class="text-slate-400 text-left text-xs uppercase tracking-wider border-b border-slate-100">
                        <tr><th class="py-1.5">Segment</th><th class="py-1.5 text-right">Patients</th><th class="py-1.5 text-right">Avg visits</th><th class="py-1.5 text-right">Avg spend</th><th class="py-1.5 text-right">Avg days since</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($segments['summary'] as $seg)
                            <tr>
                                <td class="py-2 font-medium">{{ $seg['name'] }}</td>
                                <td class="py-2 text-right">{{ $seg['size'] }}</td>
                                <td class="py-2 text-right">{{ $seg['avg_frequency'] }}</td>
                                <td class="py-2 text-right text-emerald-600">₱{{ number_format($seg['avg_monetary'], 2) }}</td>
                                <td class="py-2 text-right">{{ (int) $seg['avg_recency'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="py-10 text-center text-sm text-slate-400">—</p>
            @endif
        </div>
    </div>

    {{-- ============ DATA TABLE + EXPORT ============ --}}
    <div class="mt-5 rounded-2xl bg-white border border-slate-200/60 p-5 shadow-soft">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-display text-lg font-bold">Appointments ({{ $appointments->total() }})</h3>
            <div class="flex gap-2">
                <a href="{{ route('admin.analytics.export', array_merge($filter->toQuery(), ['format' => 'csv'])) }}" hx-boost="false"
                   class="h-9 px-3 inline-flex items-center rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Export CSV</a>
                <a href="{{ route('admin.analytics.export', array_merge($filter->toQuery(), ['format' => 'xlsx'])) }}" hx-boost="false"
                   class="h-9 px-3 inline-flex items-center rounded-lg bg-brand-green/10 text-emerald-700 text-sm font-medium hover:bg-brand-green/20 transition">Export Excel</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
                <thead class="text-slate-400 text-left text-xs uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-2">Date</th><th class="py-2">Patient</th><th class="py-2">Dentist</th>
                        <th class="py-2">Service</th><th class="py-2">Status</th>
                        <th class="py-2 text-right">Charged</th><th class="py-2 text-right">Collected</th><th class="py-2 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($appointments as $a)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2">{{ $a->scheduled_at->format('M j, Y g:i A') }}</td>
                            <td class="py-2">{{ $a->patient?->fullName() ?? '—' }}</td>
                            <td class="py-2">{{ $a->dentist?->name ?? '—' }}</td>
                            <td class="py-2">{{ $a->service?->name ?? '—' }}</td>
                            <td class="py-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $a->status->badgeClasses() }}">{{ $a->status->label() }}</span></td>
                            <td class="py-2 text-right">₱{{ number_format($a->total_amount, 2) }}</td>
                            <td class="py-2 text-right text-emerald-600">₱{{ number_format($a->amountPaid(), 2) }}</td>
                            <td class="py-2 text-right {{ $a->balance() > 0 ? 'text-red-500' : 'text-slate-400' }}">₱{{ number_format($a->balance(), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-slate-400">No appointments match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $appointments->links() }}</div>
    </div>

    {{-- Chart data island — swapped in with new values on every async filter/pagination
         change; the renderer (footer) reads this and redraws on htmx:afterSettle. --}}
    @php
        $analyticsData = [
            'isMoney' => $isMoney,
            'measure' => $filter->measure,
            'measureLabel' => $measureLabel,
            'series' => $series,
            'aggregate' => $aggregate,
            'categoryMix' => $categoryMix,
            'serviceRevenue' => $serviceRevenue,
            'paymentMix' => $paymentMix,
            'segments' => $segments,
        ];
    @endphp
    <script type="application/json" id="analytics-data">{!! json_encode($analyticsData) !!}</script>
  </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    function draw() {
        const el = document.getElementById('analytics-data');
        if (!el || !window.Chart) return;
        const d = JSON.parse(el.textContent);

        // Destroy charts from a previous render so canvases (swapped fresh) don't leak.
        (window._acharts || []).forEach(c => { try { c.destroy(); } catch (e) {} });
        window._acharts = [];
        const reg = (c) => { window._acharts.push(c); return c; };

        const peso = (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const isMoney = d.isMoney;
        const fmtMeasure = (v) => isMoney ? peso(v) : Number(v).toLocaleString();

        const series = d.series;
        const agg = d.aggregate;
        const measure = d.measure;
        const categoryMix = d.categoryMix;
        const serviceRevenue = d.serviceRevenue;
        const paymentMix = d.paymentMix;
        const segments = d.segments;

        const grid = { grid: { color: 'rgba(0,0,0,0.05)' } };

    // 1) Trend (collected revenue + appointment count, dual axis)
    const trend = document.getElementById('trendChart');
    if (trend) reg(new Chart(trend, {
        data: {
            labels: series.map(s => s.period),
            datasets: [
                { type: 'line', label: 'Collected (₱)', yAxisID: 'y', data: series.map(s => s.collected),
                  borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
                { type: 'bar', label: 'Appointments', yAxisID: 'y1', data: series.map(s => s.count),
                  backgroundColor: 'rgba(59,130,246,0.4)' },
            ],
        },
        options: { responsive: true, interaction: { mode: 'index', intersect: false },
            scales: { y: { position: 'left', ticks: { callback: peso }, ...grid }, y1: { position: 'right', grid: { drawOnChartArea: false } } } },
    }));

    // 2) Status mix (stacked bar over time)
    const status = document.getElementById('statusChart');
    if (status) reg(new Chart(status, {
        type: 'bar',
        data: {
            labels: series.map(s => s.period),
            datasets: [
                { label: 'Completed', data: series.map(s => s.completed), backgroundColor: '#10B981' },
                { label: 'Booked', data: series.map(s => s.booked), backgroundColor: '#3B82F6' },
                { label: 'No-show', data: series.map(s => s.no_show), backgroundColor: '#EF4444' },
                { label: 'Cancelled', data: series.map(s => s.cancelled), backgroundColor: '#94A3B8' },
            ],
        },
        options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, ...grid } } },
    }));

    // 3) Aggregation by chosen dimension (bar)
    const aggEl = document.getElementById('aggChart');
    if (aggEl) reg(new Chart(aggEl, {
        type: 'bar',
        data: { labels: agg.map(a => a.label),
            datasets: [{ label: d.measureLabel, data: agg.map(a => a[measure]), backgroundColor: '#3B82F6' }] },
        options: { responsive: true, plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: (v) => fmtMeasure(v) }, ...grid } } },
    }));

    // 3b) Revenue by dental service (bar, from line items)
    const svcEl = document.getElementById('serviceRevenueChart');
    if (svcEl) reg(new Chart(svcEl, {
        type: 'bar',
        data: { labels: serviceRevenue.map(s => s.label),
            datasets: [{ label: 'Revenue (₱)', data: serviceRevenue.map(s => s.revenue), backgroundColor: '#10B981' }] },
        options: { responsive: true, plugins: { legend: { display: false },
            tooltip: { callbacks: { label: (c) => peso(c.parsed.y) } } },
            scales: { y: { ticks: { callback: peso }, ...grid } } },
    }));

    // 4) Category donut (line-item revenue)
    const catEl = document.getElementById('categoryChart');
    if (catEl) reg(new Chart(catEl, {
        type: 'doughnut',
        data: { labels: categoryMix.map(c => c.label),
            datasets: [{ data: categoryMix.map(c => c.revenue),
                backgroundColor: ['#10B981','#3B82F6','#A855F7','#EF4444','#F59E0B','#94A3B8'] }] },
        options: { responsive: true, plugins: { tooltip: { callbacks: { label: (c) => c.label + ': ' + peso(c.parsed) } } } },
    }));

    // 5) Payment method donut
    const payEl = document.getElementById('paymentChart');
    if (payEl) reg(new Chart(payEl, {
        type: 'doughnut',
        data: { labels: paymentMix.map(p => p.label),
            datasets: [{ data: paymentMix.map(p => p.total),
                backgroundColor: ['#3B82F6','#10B981','#A855F7','#F59E0B','#94A3B8'] }] },
        options: { responsive: true, plugins: { tooltip: { callbacks: { label: (c) => c.label + ': ' + peso(c.parsed) } } } },
    }));

    // 6) Clustering scatter (x = visits, y = spend, colour = segment)
    const clEl = document.getElementById('clusterChart');
    if (clEl && segments) {
        const palette = ['#3B82F6','#10B981','#F59E0B','#A855F7','#EF4444'];
        const byCluster = {};
        segments.points.forEach(p => { (byCluster[p.cluster] = byCluster[p.cluster] || []).push({ x: p.x, y: p.y }); });
        const names = {};
        segments.summary.forEach(s => names[s.cluster] = s.name);
        reg(new Chart(clEl, {
            type: 'scatter',
            data: { datasets: Object.keys(byCluster).map(c => ({
                label: names[c] || ('Segment ' + c), data: byCluster[c], backgroundColor: palette[c % palette.length],
            })) },
            options: { responsive: true,
                scales: { x: { title: { display: true, text: 'Visits (frequency)' }, ...grid },
                          y: { title: { display: true, text: 'Spend (₱)' }, ticks: { callback: peso }, ...grid } },
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + c.parsed.x + ' visits, ' + peso(c.parsed.y) } } } },
        }));
    }
    }

    // Initial draw + redraw after each async (htmx) swap of the analytics block.
    if (document.readyState !== 'loading') draw(); else document.addEventListener('DOMContentLoaded', draw);
    document.body.addEventListener('htmx:afterSettle', draw);
})();
</script>
@endpush
