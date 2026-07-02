<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\SmsSchedule;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display the SMS sending form
     */
    public function index()
    {
        $customers = Customer::orderBy('created_at', 'desc')->get();
        $smsLogs = SmsLog::with(['customer', 'sender'])
            ->orderByRaw("CASE WHEN status = 'scheduled' AND scheduled_at IS NOT NULL THEN scheduled_at WHEN status = 'cancelled' THEN updated_at WHEN sent_at IS NOT NULL THEN sent_at ELSE created_at END DESC")
            ->paginate(50);

        $scheduledBatches = SmsSchedule::whereIn('status', ['scheduled', 'paused'])
            ->whereNotNull('scheduled_at')
            ->orderByDesc('scheduled_at')
            ->withCount([
                'logs as total' => fn ($q) => $q->where('status', 'scheduled'),
            ])
            ->get();

        return view('sms.index', compact('customers', 'smsLogs', 'scheduledBatches'));
    }

    /**
     * Show form to send SMS
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('created_at', 'desc')->get();
        $selectedCustomerId = $request->get('customer_id');
        return view('sms.create', compact('customers', 'selectedCustomerId'));
    }

    /**
     * Send SMS
     */
    public function store(Request $request)
    {
        if ($request->send_to === 'all') {
            $user = auth()->user();
            if (!$user || (!$user->isSuperAdmin() && !$user->isCeo())) {
                return back()->with('error', 'Only Super Admin or CEO can send/schedule SMS to ALL customers.')->withInput();
            }
        }

        $validator = Validator::make($request->all(), [
            'send_to' => 'required|in:single,selected,all',
            'phone_number' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    // Only validate if send_to is single and no customer_id is provided
                    if ($request->send_to !== 'single') {
                        return;
                    }
                    
                    // If customer_id is provided, phone_number is optional
                    if ($request->customer_id) {
                        return;
                    }
                    
                    // Phone number is required if no customer is selected
                    if (empty($value)) {
                        $fail('Please select a customer or enter a phone number.');
                        return;
                    }
                    // Remove non-numeric characters for validation
                    $cleanNumber = preg_replace('/[^0-9]/', '', $value);
                    
                    // Check if it's a valid Tanzanian number
                    if (strlen($cleanNumber) < 9 || strlen($cleanNumber) > 12) {
                        $fail('Phone number must be between 9 and 12 digits. Format: 0612345678 or 255612345678');
                    }
                    
                    if (substr($cleanNumber, 0, 1) === '0' && strlen($cleanNumber) !== 10) {
                        $fail('Phone number starting with 0 must be 10 digits (e.g., 0612345678)');
                    }
                    
                    if (substr($cleanNumber, 0, 3) === '255' && strlen($cleanNumber) !== 12) {
                        if (strlen($cleanNumber) === 11) {
                            $fail('Phone number starting with 255 must be 12 digits. You entered ' . strlen($cleanNumber) . ' digits - missing 1 digit.');
                        } else {
                            $fail('Phone number starting with 255 must be 12 digits (e.g., 255612345678).');
                        }
                    }
                },
            ],
            'selected_customers' => 'required_if:send_to,selected|array',
            'selected_customers.*' => 'exists:customers,id',
            'message' => 'required|string|min:1|max:1000',
            'sms_type' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'is_scheduled' => 'nullable|boolean',
            'scheduled_at' => 'required_if:is_scheduled,1|nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $message = $request->message;
        $smsType = $request->sms_type;
        $sendTo = $request->send_to;
        $isScheduled = $request->input('is_scheduled') == '1';
        $scheduledAt = $isScheduled ? $request->input('scheduled_at') : null;
        
        $successCount = 0;
        $failCount = 0;
        $scheduledCount = 0;
        $errors = [];

        try {
            // Replace {year} with current year
            $message = str_replace('{year}', date('Y'), $message);

            $schedule = null;
            if ($isScheduled) {
                $schedule = SmsSchedule::create([
                    'send_to' => $sendTo,
                    'message_template' => $message,
                    'sms_type' => $smsType,
                    'status' => 'scheduled',
                    'scheduled_at' => $scheduledAt,
                    'created_by' => auth()->id(),
                    'meta' => $sendTo === 'selected'
                        ? ['selected_customer_ids' => array_values($request->selected_customers ?? [])]
                        : ($sendTo === 'single'
                            ? ['customer_id' => $request->customer_id, 'phone_number' => $request->phone_number]
                            : null),
                ]);
            }

            if ($sendTo === 'single') {
                // Single SMS
                $phoneNumber = $request->phone_number;
                $customerId = $request->customer_id;

                if ($customerId) {
                    $customer = Customer::find($customerId);
                    if ($customer) {
                        $phoneNumber = $customer->phone_number;
                        if ($customer->name) {
                            $message = str_replace('{name}', $customer->name, $message);
                        }
                    }
                } elseif ($phoneNumber) {
                    $customer = Customer::findOrCreateByPhone($phoneNumber, [], auth()->id());
                    if ($customer) {
                        $customerId = $customer->id;
                        $phoneNumber = $customer->phone_number;
                        if ($customer->name) {
                            $message = str_replace('{name}', $customer->name, $message);
                        }
                    }
                }

                if ($isScheduled) {
                    $smsLog = SmsLog::create([
                        'schedule_id' => $schedule?->id,
                        'customer_id' => $customerId,
                        'phone_number' => $phoneNumber,
                        'message' => $message,
                        'sms_type' => $smsType,
                        'status' => 'scheduled',
                        'scheduled_at' => $scheduledAt,
                        'sent_by' => auth()->id(),
                    ]);
                    $scheduledCount = 1;
                } else {
                    $smsLog = $this->smsService->sendAndLog(
                        $phoneNumber,
                        $message,
                        $smsType,
                        $customerId,
                        auth()->id()
                    );

                    if ($smsLog->status === 'sent') {
                        $successCount = 1;
                    } else {
                        $failCount = 1;
                        $apiResponse = json_decode($smsLog->api_response, true);
                        if (isset($apiResponse['response_data']['messages'][0]['status']['description'])) {
                            $errors[] = $apiResponse['response_data']['messages'][0]['status']['description'];
                        }
                    }
                }
            } elseif ($sendTo === 'selected') {
                // Send to selected customers
                $customerIds = $request->selected_customers ?? [];
                
                if (empty($customerIds)) {
                    return back()->with('error', 'Please select at least one customer.')->withInput();
                }
                
                $customers = Customer::whereIn('id', $customerIds)->get();
                
                if ($customers->isEmpty()) {
                    return back()->with('error', 'No valid customers selected.')->withInput();
                }

                foreach ($customers as $customer) {
                    $personalizedMessage = $message;
                    if ($customer->name) {
                        $personalizedMessage = str_replace('{name}', $customer->name, $personalizedMessage);
                    }

                    if ($isScheduled) {
                        SmsLog::create([
                            'schedule_id' => $schedule?->id,
                            'customer_id' => $customer->id,
                            'phone_number' => $customer->phone_number,
                            'message' => $personalizedMessage,
                            'sms_type' => $smsType,
                            'status' => 'scheduled',
                            'scheduled_at' => $scheduledAt,
                            'sent_by' => auth()->id(),
                        ]);
                        $scheduledCount++;
                    } else {
                        $smsLog = $this->smsService->sendAndLog(
                            $customer->phone_number,
                            $personalizedMessage,
                            $smsType,
                            $customer->id,
                            auth()->id()
                        );

                        if ($smsLog->status === 'sent') {
                            $successCount++;
                        } else {
                            $failCount++;
                            $apiResponse = json_decode($smsLog->api_response, true);
                            if (isset($apiResponse['response_data']['messages'][0]['status']['description'])) {
                                $errors[] = $customer->phone_number . ': ' . $apiResponse['response_data']['messages'][0]['status']['description'];
                            }
                        }
                    }
                }
            } elseif ($sendTo === 'all') {
                // Send to all customers
                $customers = Customer::all();
                
                if ($customers->isEmpty()) {
                    return back()->with('error', 'No customers found in the system.')->withInput();
                }

                foreach ($customers as $customer) {
                    $personalizedMessage = $message;
                    if ($customer->name) {
                        $personalizedMessage = str_replace('{name}', $customer->name, $personalizedMessage);
                    }

                    if ($isScheduled) {
                        SmsLog::create([
                            'schedule_id' => $schedule?->id,
                            'customer_id' => $customer->id,
                            'phone_number' => $customer->phone_number,
                            'message' => $personalizedMessage,
                            'sms_type' => $smsType,
                            'status' => 'scheduled',
                            'scheduled_at' => $scheduledAt,
                            'sent_by' => auth()->id(),
                        ]);
                        $scheduledCount++;
                    } else {
                        $smsLog = $this->smsService->sendAndLog(
                            $customer->phone_number,
                            $personalizedMessage,
                            $smsType,
                            $customer->id,
                            auth()->id()
                        );

                        if ($smsLog->status === 'sent') {
                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    }
                }
            }

            // Return success message
            if ($isScheduled && $scheduledCount > 0) {
                $formattedTime = \Carbon\Carbon::parse($scheduledAt)->format('M d, Y H:i');
                return redirect()->route('sms.index')->with('success', "SMS scheduled successfully! Total scheduled: {$scheduledCount} for {$formattedTime}");
            } elseif ($successCount > 0) {
                $message = "SMS sent successfully! Sent: {$successCount}";
                if ($failCount > 0) {
                    $message .= ", Failed: {$failCount}";
                }
                return redirect()->route('sms.index')->with('success', $message);
            } else {
                $errorMessage = 'Failed to send SMS. ';
                if (!empty($errors)) {
                    $errorMessage .= implode('; ', array_slice($errors, 0, 3));
                }
                return back()->with('error', $errorMessage)->withInput();
            }
        } catch (\Exception $e) {
            \Log::error('SMS Controller Error: ' . $e->getMessage());
            return back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }

    public function cancel(SmsLog $sms)
    {
        if ($sms->status !== 'scheduled') {
            return back()->with('error', 'Only scheduled SMS can be cancelled.');
        }

        $sms->update(['status' => 'cancelled']);

        return redirect()->route('sms.index', ['status' => 'cancelled'])
            ->with('sms_cancelled', 'SMS cancelled successfully.');
    }

    public function cancelBatch(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'required|date',
            'message' => 'required|string',
        ]);

        $scheduledAt = \Carbon\Carbon::parse($request->scheduled_at);

        $count = SmsLog::where('status', 'scheduled')
            ->where('message', $request->message)
            ->whereBetween('scheduled_at', [
                $scheduledAt->copy()->startOfSecond(),
                $scheduledAt->copy()->endOfSecond(),
            ])
            ->update(['status' => 'cancelled']);

        if ($count === 0) {
            return back()->with('error', 'No scheduled SMS found for this batch.');
        }

        return redirect()->route('sms.index', ['status' => 'cancelled'])
            ->with('sms_cancelled', "{$count} SMS messages cancelled.");
    }

    public function editSchedule(SmsSchedule $schedule)
    {
        if (!in_array($schedule->status, ['scheduled', 'paused'], true)) {
            return redirect()->route('sms.index')->with('error', 'Only scheduled/paused batches can be edited.');
        }

        $customers = collect();
        $selectedCustomerIds = [];

        if ($schedule->send_to === 'selected') {
            $customers = Customer::orderBy('created_at', 'desc')->get();
            $selectedCustomerIds = (array) ($schedule->meta['selected_customer_ids'] ?? []);
        }

        return view('sms.edit-schedule', compact('schedule', 'customers', 'selectedCustomerIds'));
    }

    public function updateSchedule(Request $request, SmsSchedule $schedule)
    {
        if (!in_array($schedule->status, ['scheduled', 'paused'], true)) {
            return redirect()->route('sms.index')->with('error', 'Only scheduled/paused batches can be updated.');
        }

        $request->validate([
            'message_template' => 'required|string|min:1|max:1000',
            'sms_type' => 'required|string',
            'scheduled_at' => 'required|date',
            'include_new_customers' => 'nullable|boolean',
            'selected_customers' => 'nullable|array',
            'selected_customers.*' => 'exists:customers,id',
        ]);

        $template = str_replace('{year}', date('Y'), $request->message_template);
        $scheduledAt = $request->scheduled_at;

        $schedule->update([
            'message_template' => $template,
            'sms_type' => $request->sms_type,
            'scheduled_at' => $scheduledAt,
        ]);

        if ($schedule->send_to === 'selected') {
            $newIds = array_values(array_unique(array_map('intval', $request->input('selected_customers', []))));
            if (empty($newIds)) {
                return back()->with('error', 'Please select at least one customer.')->withInput();
            }

            $existingScheduledLogs = SmsLog::with('customer')
                ->where('schedule_id', $schedule->id)
                ->where('status', 'scheduled')
                ->get();

            $existingIds = $existingScheduledLogs
                ->pluck('customer_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $toAdd = array_values(array_diff($newIds, $existingIds));
            $toRemove = array_values(array_diff($existingIds, $newIds));

            if (!empty($toRemove)) {
                SmsLog::where('schedule_id', $schedule->id)
                    ->where('status', 'scheduled')
                    ->whereIn('customer_id', $toRemove)
                    ->update(['status' => 'cancelled']);
            }

            if (!empty($toAdd)) {
                $customersToAdd = Customer::whereIn('id', $toAdd)->get();

                foreach ($customersToAdd as $customer) {
                    $msg = $template;
                    if ($customer->name) {
                        $msg = str_replace('{name}', $customer->name, $msg);
                    }

                    SmsLog::create([
                        'schedule_id' => $schedule->id,
                        'customer_id' => $customer->id,
                        'phone_number' => $customer->phone_number,
                        'message' => $msg,
                        'sms_type' => $request->sms_type,
                        'status' => 'scheduled',
                        'scheduled_at' => $scheduledAt,
                        'sent_by' => auth()->id(),
                    ]);
                }
            }

            $schedule->update([
                'meta' => array_merge((array) ($schedule->meta ?? []), [
                    'selected_customer_ids' => $newIds,
                ]),
            ]);
        }

        // Update pending logs for this schedule.
        $logs = SmsLog::where('schedule_id', $schedule->id)
            ->where('status', 'scheduled')
            ->get();

        foreach ($logs as $log) {
            $msg = $template;
            if ($log->customer && $log->customer->name) {
                $msg = str_replace('{name}', $log->customer->name, $msg);
            }

            $log->update([
                'message' => $msg,
                'sms_type' => $request->sms_type,
                'scheduled_at' => $scheduledAt,
            ]);
        }

        // If "all", optionally add newly registered customers immediately.
        if ($schedule->send_to === 'all' && $request->boolean('include_new_customers')) {
            $existingCustomerIds = SmsLog::where('schedule_id', $schedule->id)
                ->whereNotNull('customer_id')
                ->pluck('customer_id')
                ->all();

            $missingCustomers = Customer::query()
                ->when(!empty($existingCustomerIds), fn ($q) => $q->whereNotIn('id', $existingCustomerIds))
                ->get();

            foreach ($missingCustomers as $customer) {
                $msg = $template;
                if ($customer->name) {
                    $msg = str_replace('{name}', $customer->name, $msg);
                }

                SmsLog::create([
                    'schedule_id' => $schedule->id,
                    'customer_id' => $customer->id,
                    'phone_number' => $customer->phone_number,
                    'message' => $msg,
                    'sms_type' => $request->sms_type,
                    'status' => 'scheduled',
                    'scheduled_at' => $scheduledAt,
                    'sent_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('sms.index')->with('success', 'Scheduled SMS batch updated successfully.');
    }

    public function cancelSchedule(SmsSchedule $schedule)
    {
        if (!in_array($schedule->status, ['scheduled', 'paused'], true)) {
            return redirect()->route('sms.index')->with('error', 'Only scheduled/paused batches can be cancelled.');
        }

        $schedule->update(['status' => 'cancelled']);

        SmsLog::where('schedule_id', $schedule->id)
            ->where('status', 'scheduled')
            ->update(['status' => 'cancelled']);

        return redirect()->route('sms.index', ['status' => 'cancelled'])
            ->with('sms_cancelled', 'Scheduled batch cancelled.');
    }

    public function pauseSchedule(SmsSchedule $schedule)
    {
        if ($schedule->status !== 'scheduled') {
            return back()->with('error', 'Only scheduled batches can be paused.');
        }

        $schedule->update(['status' => 'paused']);

        return redirect()->route('sms.index')->with('success', 'Scheduled batch paused.');
    }

    public function resumeSchedule(SmsSchedule $schedule)
    {
        if ($schedule->status !== 'paused') {
            return back()->with('error', 'Only paused batches can be resumed.');
        }

        $schedule->update(['status' => 'scheduled']);

        return redirect()->route('sms.index')->with('success', 'Scheduled batch resumed.');
    }

    /**
     * Get SMS templates
     */
    public function getTemplates(Request $request)
    {
        $type = $request->get('type', 'engagement');

        $templates = [
            'engagement' => [
                'Karibu! Asante kwa kutembelea ofisi yetu. Tunathamini biashara yako na tunatarajia kukuhudumia.',
                'Hello {name}, thank you for choosing us. We value your trust and are here to assist you.',
                'Dear {name}, we hope you had a great experience with us. Please feel free to reach out if you need anything.',
                'Asante {name} kwa kutuamini. Tunaendelea kukuhudumia kwa ubora.',
            ],
            'holiday' => [
                // Nyerere Day (October 14)
                'Heri ya Siku ya Mwalimu Nyerere! Tunakukumbuka na kukushukuru kwa mchango wako.',
                'Happy Nyerere Day! Celebrating the legacy of Mwalimu Julius Nyerere.',
                
                // Independence Day (December 9)
                'Heri ya Sikukuu ya Uhuru wa Tanzania! Tunapongeza Taifa letu na watu wake.',
                'Happy Independence Day! Celebrating Tanzania\'s freedom and unity.',
                
                // New Year
                'Heri ya Mwaka Mpya! Tunakutakia mwaka mrefu wa mafanikio, afya na furaha.',
                'Happy New Year! We wish you a year filled with success, health, and happiness.',
                'Mwaka Mpya wa {year}! Tunakutakia baraka na mafanikio mengi.',
                
                // Christmas
                'Heri ya Krismasi! Tunakutakia siku za furaha na amani pamoja na familia yako.',
                'Merry Christmas! Wishing you joy, peace, and blessings this Christmas season.',
                'Krismasi Njema {name}! Mungu akubariki na familia yako.',
                
                // Easter
                'Heri ya Pasaka! Tunakutakia siku za furaha na amani.',
                'Happy Easter! May this season bring you peace and joy.',
                
                // General Holidays
                'Happy Holidays! Wishing you and your family joy, peace, and prosperity this season.',
                'Season\'s Greetings! May this festive season bring you happiness and success.',
            ],
            'custom' => [
                'Thank you for your visit. We appreciate your business.',
                'Asante kwa kutembelea ofisi yetu. Tunathamini biashara yako.',
            ],
        ];

        return response()->json([
            'templates' => $templates[$type] ?? $templates['custom']
        ]);
    }

    /**
     * View SMS logs
     */
    public function logs()
    {
        $smsLogs = SmsLog::with(['customer', 'sender'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sms.logs', compact('smsLogs'));
    }
}
