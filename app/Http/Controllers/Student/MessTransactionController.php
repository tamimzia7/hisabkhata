<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Mess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessTransactionController extends Controller
{
    /**
     * Browse the mess financial ledger.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $month = (string) $request->query('month', now()->format('Y-m'));

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = now()->format('Y-m');
        }

        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $transactions = $mess->transactions()
            ->with(['account', 'creator'])
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('account'), fn ($query) => $query->where('account_id', $request->integer('account')))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('student.transactions.index', [
            'mess' => $mess,
            'month' => $month,
            'transactions' => $transactions,
            'accounts' => $mess->accounts()->orderBy('name')->get(),
            'types' => collect($mess->transactions()->distinct()->orderBy('type')->pluck('type')),
        ]);
    }
}
