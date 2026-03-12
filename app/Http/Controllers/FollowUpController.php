<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowUpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $followUps = FollowUp::with(['customer', 'assignedUser', 'creator'])
            ->orderBy('visit_date', 'desc')
            ->paginate(20);

        return view('follow-ups.index', compact('followUps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('name', 'asc')->get();
        $users = \App\Models\User::whereIn('role', ['ceo', 'staff'])->orderBy('name', 'asc')->get();
        $selectedCustomerId = $request->get('customer_id');
        return view('follow-ups.create', compact('customers', 'users', 'selectedCustomerId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'         => 'required|exists:customers,id',
            'visit_date'          => 'required|date',
            'visit_purpose'       => 'nullable|string|max:1000',
            'notes'               => 'nullable|string|max:2000',
            'status'              => 'required|in:pending,completed,cancelled',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today',
            'assigned_to'         => 'nullable|exists:users,id',
            'reminder_date'       => 'nullable|date',
            'remind_via'          => 'nullable|in:assigned_user,customer,both',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        FollowUp::create([
            'customer_id'         => $request->customer_id,
            'visit_date'          => $request->visit_date,
            'visit_purpose'       => $request->visit_purpose,
            'notes'               => $request->notes,
            'status'              => $request->status,
            'next_follow_up_date' => $request->next_follow_up_date,
            'assigned_to'         => $request->assigned_to,
            'created_by'          => auth()->id(),
            'reminder_date'       => $request->reminder_date,
            'remind_via'          => $request->remind_via ?? 'assigned_user',
        ]);

        return redirect()->route('follow-ups.index')
            ->with('success', 'Follow-up created successfully! SMS reminder will be sent on the reminder date.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $followUp = FollowUp::with(['customer', 'assignedUser', 'creator'])->findOrFail($id);
        return view('follow-ups.show', compact('followUp'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $followUp = FollowUp::findOrFail($id);
        $customers = Customer::orderBy('name', 'asc')->get();
        $users = \App\Models\User::whereIn('role', ['ceo', 'staff'])->orderBy('name', 'asc')->get();
        return view('follow-ups.edit', compact('followUp', 'customers', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $followUp = FollowUp::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_id'         => 'required|exists:customers,id',
            'visit_date'          => 'required|date',
            'visit_purpose'       => 'nullable|string|max:1000',
            'notes'               => 'nullable|string|max:2000',
            'status'              => 'required|in:pending,completed,cancelled',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today',
            'assigned_to'         => 'nullable|exists:users,id',
            'reminder_date'       => 'nullable|date',
            'remind_via'          => 'nullable|in:assigned_user,customer,both',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // If the reminder date changed, reset the sent flag so it fires again
        $reminderChanged = $followUp->reminder_date != $request->reminder_date;

        $followUp->update([
            'customer_id'         => $request->customer_id,
            'visit_date'          => $request->visit_date,
            'visit_purpose'       => $request->visit_purpose,
            'notes'               => $request->notes,
            'status'              => $request->status,
            'next_follow_up_date' => $request->next_follow_up_date,
            'assigned_to'         => $request->assigned_to,
            'reminder_date'       => $request->reminder_date,
            'remind_via'          => $request->remind_via ?? 'assigned_user',
            'reminder_sent_at'    => $reminderChanged ? null : $followUp->reminder_sent_at,
        ]);

        return redirect()->route('follow-ups.index')
            ->with('success', 'Follow-up updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $followUp = FollowUp::findOrFail($id);
        $followUp->delete();

        return redirect()->route('follow-ups.index')
            ->with('success', 'Follow-up deleted successfully!');
    }
}
