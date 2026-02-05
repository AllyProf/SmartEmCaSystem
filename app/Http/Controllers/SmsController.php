<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsLog;
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
        $customers = Customer::orderBy('name', 'asc')->get();
        $smsLogs = SmsLog::with(['customer', 'sender'])
            ->orderBy('created_at', 'desc')
            ->paginate(50); // Increased to 50 per page

        // Get statistics
        $stats = [
            'total' => SmsLog::count(),
            'sent' => SmsLog::where('status', 'sent')->count(),
            'failed' => SmsLog::where('status', 'failed')->count(),
        ];

        return view('sms.index', compact('customers', 'smsLogs', 'stats'));
    }

    /**
     * Show form to send SMS
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('name', 'asc')->get();
        $selectedCustomerId = $request->get('customer_id');
        return view('sms.create', compact('customers', 'selectedCustomerId'));
    }

    /**
     * Send SMS
     */
    public function store(Request $request)
    {
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
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $message = $request->message;
        $smsType = $request->sms_type;
        $sendTo = $request->send_to;
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        try {
            // Replace {year} with current year
            $message = str_replace('{year}', date('Y'), $message);

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
                }

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

            // Return success message
            if ($successCount > 0) {
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
