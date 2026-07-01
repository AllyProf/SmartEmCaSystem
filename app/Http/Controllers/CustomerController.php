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
    public function index()
    {
        $customers = Customer::with(['creator', 'followUps'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20);

        $stats = [
            'total' => Customer::count(),
            'today' => Customer::where('created_at', '>=', now()->startOfDay())->count(),
            'this_week' => Customer::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('customers.index', compact('customers', 'stats'));
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
