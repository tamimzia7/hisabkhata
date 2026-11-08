<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\BazarRecordRequest;
use App\Models\BazarRecord;
use App\Models\Mess;
use App\Models\MessTransaction;
use App\Services\AuditLogger;
use App\Services\MessLedger;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BazarRecordController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MessLedger $ledger,
        private readonly SettlementService $settlements,
    ) {}

    /**
     * List bazar purchases with filters.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $month = $this->month($request);
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $records = $mess->bazarRecords()
            ->with(['purchaser', 'account', 'items'])
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('member'), fn ($query) => $query->where('purchased_by_member_id', $request->integer('member')))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('student.bazar.index', [
            'mess' => $mess,
            'month' => $month,
            'records' => $records,
            'members' => $mess->activeMembers()->orderBy('name')->get(),
            'accounts' => $mess->accounts()->where('is_active', true)->orderBy('name')->get(),
            'total' => (float) $records->getCollection()->sum('total_amount'),
            'monthTotal' => (float) $mess->bazarRecords()->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount'),
            'categories' => $mess->bazarRecords()->distinct()->orderBy('category')->pluck('category'),
            'locked' => $this->settlements->isLocked($mess, $month),
            'canManage' => $request->user()->can('manageBazar', $mess),
        ]);
    }

    /**
     * Record a bazar purchase with its line items and ledger entry.
     */
    public function store(BazarRecordRequest $request, Mess $mess): RedirectResponse
    {
        $validated = $request->validated();
        $this->settlements->ensureMonthOpen($mess, $validated['purchase_date'], 'purchase_date');

        $record = DB::transaction(function () use ($request, $mess, $validated): BazarRecord {
            $items = collect($validated['items'] ?? [])
                ->filter(fn (array $item): bool => filled($item['name'] ?? null))
                ->values();

            $itemsTotal = round($items->sum(fn (array $item): float => (float) ($item['total_price'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0)))), 2);
            $total = $itemsTotal > 0 ? $itemsTotal : round((float) ($validated['total_amount'] ?? 0), 2);

            $record = $mess->bazarRecords()->create([
                'purchased_by_member_id' => $validated['purchased_by_member_id'] ?? null,
                'account_id' => $validated['account_id'] ?? null,
                'created_by' => $request->user()->getKey(),
                'purchase_date' => $validated['purchase_date'],
                'category' => $validated['category'] ?? 'general',
                'payment_source' => $validated['payment_source'],
                'total_amount' => $total,
                'notes' => $validated['notes'] ?? null,
                'receipt_path' => $request->hasFile('receipt')
                    ? Storage::disk('public')->putFile('mess-bazar', $request->file('receipt'))
                    : null,
            ]);

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                $record->items()->create([
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? 'pcs',
                    'unit_price' => $unitPrice,
                    'total_price' => round((float) ($item['total_price'] ?? ($quantity * $unitPrice)), 2),
                ]);
            }

            if ($record->account_id !== null) {
                $this->ledger->post($mess, $request->user(), MessTransaction::TYPE_BAZAR, MessTransaction::DIRECTION_DEBIT, (float) $record->total_amount, $record->purchase_date->toDateString(), $record->account, __('student.ledger.bazar'), $record);
            }

            return $record;
        });

        $this->audit->log($mess, $request->user(), 'bazar.created', $record, ['amount' => (float) $record->total_amount], $request->ip());

        return back()->with('toast', __('student.bazar.created'));
    }

    /**
     * Update a bazar purchase.
     */
    public function update(BazarRecordRequest $request, Mess $mess, BazarRecord $bazar): RedirectResponse
    {
        $this->ensureBelongs($mess, $bazar);
        $validated = $request->validated();
        $this->settlements->ensureMonthOpen($mess, $bazar->purchase_date->toDateString(), 'purchase_date');
        $this->settlements->ensureMonthOpen($mess, $validated['purchase_date'], 'purchase_date');

        DB::transaction(function () use ($request, $mess, $bazar, $validated): void {
            $items = collect($validated['items'] ?? [])->filter(fn (array $item): bool => filled($item['name'] ?? null))->values();
            $itemsTotal = round($items->sum(fn (array $item): float => (float) ($item['total_price'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0)))), 2);
            $hasItems = $request->has('items');
            $total = $hasItems && $itemsTotal > 0
                ? $itemsTotal
                : round((float) ($validated['total_amount'] ?? $bazar->total_amount), 2);

            if ($hasItems) {
                $bazar->items()->delete();

                foreach ($items as $item) {
                    $quantity = (float) ($item['quantity'] ?? 1);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);

                    $bazar->items()->create([
                        'name' => $item['name'],
                        'quantity' => $quantity,
                        'unit' => $item['unit'] ?? 'pcs',
                        'unit_price' => $unitPrice,
                        'total_price' => round((float) ($item['total_price'] ?? ($quantity * $unitPrice)), 2),
                    ]);
                }
            }

            if ($request->hasFile('receipt')) {
                if (filled($bazar->receipt_path)) {
                    Storage::disk('public')->delete($bazar->receipt_path);
                }
            }

            $bazar->update([
                'purchased_by_member_id' => $validated['purchased_by_member_id'] ?? null,
                'account_id' => $validated['account_id'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'category' => $validated['category'] ?? 'general',
                'payment_source' => $validated['payment_source'],
                'total_amount' => $total,
                'notes' => $validated['notes'] ?? null,
                'receipt_path' => $request->hasFile('receipt')
                    ? Storage::disk('public')->putFile('mess-bazar', $request->file('receipt'))
                    : $bazar->receipt_path,
            ]);

            $bazar->load('account');
            $this->ledger->removeFor($bazar);

            if ($bazar->account_id !== null) {
                $this->ledger->post($mess, $request->user(), MessTransaction::TYPE_BAZAR, MessTransaction::DIRECTION_DEBIT, (float) $bazar->total_amount, $bazar->purchase_date->toDateString(), $bazar->account, __('student.ledger.bazar'), $bazar);
            }
        });

        $this->audit->log($mess, $request->user(), 'bazar.updated', $bazar, ['amount' => (float) $bazar->total_amount], $request->ip());

        return back()->with('toast', __('student.bazar.updated'));
    }

    /**
     * Delete a bazar purchase.
     */
    public function destroy(Request $request, Mess $mess, BazarRecord $bazar): RedirectResponse
    {
        $this->ensureBelongs($mess, $bazar);
        $this->settlements->ensureMonthOpen($mess, $bazar->purchase_date->toDateString(), 'purchase_date');

        DB::transaction(function () use ($bazar): void {
            $this->ledger->removeFor($bazar);

            if (filled($bazar->receipt_path)) {
                Storage::disk('public')->delete($bazar->receipt_path);
            }

            $bazar->delete();
        });

        $this->audit->log($mess, $request->user(), 'bazar.deleted', $bazar, ['amount' => (float) $bazar->total_amount], $request->ip());

        return back()->with('toast', __('student.bazar.deleted'));
    }

    private function ensureBelongs(Mess $mess, BazarRecord $bazar): void
    {
        abort_unless($bazar->mess_id === $mess->getKey(), 404);
    }

    private function month(Request $request): string
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : now()->format('Y-m');
    }
}
