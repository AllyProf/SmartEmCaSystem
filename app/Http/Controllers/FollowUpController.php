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
        
        $customerHistory = null;
        if ($selectedCustomerId) {
            $customerHistory = FollowUp::with('assignedUser')->where('customer_id', $selectedCustomerId)
                                       ->orderBy('created_at', 'desc')
                                       ->take(5)
                                       ->get();
        }
        
        return view('follow-ups.create', compact('customers', 'users', 'selectedCustomerId', 'customerHistory'));
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
            'status'              => 'required|string|max:255',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today',
            'next_follow_up_time' => 'nullable|date_format:H:i',
            'collaborators'       => 'nullable|array',
            'collaborators.*'     => 'exists:users,id',
            'reminder_date'       => 'nullable|date',
            'reminder_time'       => 'nullable|date_format:H:i',
            'reminder_message'    => 'nullable|string|max:1000',
            'remind_via'          => 'nullable|in:assigned_user,customer,both',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $collaborators = $request->collaborators ?? [];
        $assignedTo = !empty($collaborators) ? $collaborators[0] : null;

        FollowUp::create([
            'customer_id'         => $request->customer_id,
            'visit_date'          => $request->visit_date,
            'visit_purpose'       => $request->visit_purpose,
            'notes'               => $request->notes,
            'status'              => $request->status,
            'next_follow_up_date' => $request->next_follow_up_date,
            'next_follow_up_time' => $request->next_follow_up_time,
            'assigned_to'         => $assignedTo,
            'collaborators'       => $collaborators,
            'created_by'          => auth()->id(),
            'reminder_date'       => $request->reminder_date,
            'reminder_time'       => $request->reminder_time,
            'reminder_message'    => $request->reminder_message,
            'remind_via'          => $request->remind_via ?? 'assigned_user',
        ]);

        if ($request->has('schedule_next')) {
            return redirect()->route('follow-ups.create', ['customer_id' => $request->customer_id])
                ->with('success', 'Conversation logged! Now create the pending follow-up for the future.');
        }

        return redirect()->route('follow-ups.index')
            ->with('success', 'Follow-up created successfully!');
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
            'status'              => 'required|string|max:255',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today',
            'next_follow_up_time' => 'nullable|date_format:H:i:s,H:i',
            'collaborators'       => 'nullable|array',
            'collaborators.*'     => 'exists:users,id',
            'reminder_date'       => 'nullable|date',
            'reminder_time'       => 'nullable|date_format:H:i:s,H:i',
            'reminder_message'    => 'nullable|string|max:1000',
            'remind_via'          => 'nullable|in:assigned_user,customer,both',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // If the reminder date or time changed, reset the sent flag so it fires again
        $reminderChanged = ($followUp->reminder_date != $request->reminder_date) || ($followUp->reminder_time != $request->reminder_time);

        $collaborators = $request->collaborators ?? [];
        $assignedTo = !empty($collaborators) ? $collaborators[0] : null;

        $followUp->update([
            'customer_id'         => $request->customer_id,
            'visit_date'          => $request->visit_date,
            'visit_purpose'       => $request->visit_purpose,
            'notes'               => $request->notes,
            'status'              => $request->status,
            'next_follow_up_date' => $request->next_follow_up_date,
            'next_follow_up_time' => $request->next_follow_up_time,
            'assigned_to'         => $assignedTo,
            'collaborators'       => $collaborators,
            'reminder_date'       => $request->reminder_date,
            'reminder_time'       => $request->reminder_time,
            'reminder_message'    => $request->reminder_message,
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
