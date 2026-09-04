<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'region' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $region = isset($validated['region']) ? trim($validated['region']) : null;
        $search = isset($validated['search']) ? trim($validated['search']) : null;

        $query = Customer::with(['creator', 'followUps']);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($region !== null && $region !== '') {
            $query->where('location', $region);
        }

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like, $search) {
                $q->where('name', 'like', $like)
                    ->orWhere('phone_number', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('visiting_purpose', 'like', $like)
                    ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') like ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(created_at, '%b %d, %Y') like ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(created_at, '%M %d, %Y') like ?", [$like]);

                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
                    $q->orWhereDate('created_at', $search);
                }
            });
        }

        $customers = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $stats = [
            'total' => Customer::count(),
            'today' => Customer::where('created_at', '>=', now()->startOfDay())->count(),
            'this_week' => Customer::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'filtered' => $customers->total(),
        ];

        $regions = Customer::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        if ($request->ajax()) {
            return view('customers._results', compact('customers', 'dateFrom', 'dateTo', 'region', 'search'));
        }

        return view('customers.index', compact('customers', 'stats', 'regions', 'dateFrom', 'dateTo', 'region', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|unique:customers,phone_number|min:9',
            'location' => 'nullable|string|max:255',
            'visiting_purpose' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $customer = Customer::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'location' => $request->location,
            'visiting_purpose' => $request->visiting_purpose,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Customer added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::with(['creator', 'smsLogs.sender', 'followUps.assignedUser'])
            ->findOrFail($id);

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|unique:customers,phone_number,' . $id . '|min:9',
            'location' => 'nullable|string|max:255',
            'visiting_purpose' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $customer->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'location' => $request->location,
            'visiting_purpose' => $request->visiting_purpose,
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully!');
    }
}
