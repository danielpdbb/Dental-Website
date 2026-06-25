{{-- Itemised bill block. Expects $statement (BillingStatement with items) + $appointment. --}}
<div class="rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <th class="text-left px-3 py-2 font-medium">Item</th>
                <th class="text-right px-3 py-2 font-medium w-28">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($statement->items as $item)
                <tr>
                    <td class="px-3 py-2">{{ $item->description }} @if ($item->quantity > 1)<span class="text-slate-400">× {{ $item->quantity }}</span>@endif</td>
                    <td class="px-3 py-2 text-right">₱{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="px-3 py-2 text-slate-400">No line items.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="border-t border-slate-200 text-sm">
            <tr><td class="px-3 py-1.5 text-right text-slate-500">Subtotal</td><td class="px-3 py-1.5 text-right">₱{{ number_format((float) $statement->subtotal, 2) }}</td></tr>
            @if ((float) $statement->discount > 0)
                <tr><td class="px-3 py-1.5 text-right text-slate-500">Discount</td><td class="px-3 py-1.5 text-right text-emerald-600">− ₱{{ number_format((float) $statement->discount, 2) }}</td></tr>
            @endif
            <tr class="font-bold"><td class="px-3 py-2 text-right">Total</td><td class="px-3 py-2 text-right">₱{{ number_format((float) $statement->total, 2) }}</td></tr>
        </tfoot>
    </table>
</div>
