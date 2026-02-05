<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VisitConfirmation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\SmsService;

class VisitorController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // Admin Methods
    public function index()
    {
        $visits = VisitConfirmation::latest()->paginate(10);
        
        $stats = [
            'total' => VisitConfirmation::count(),
            'single' => VisitConfirmation::where('type', 'single')->count(),
            'group' => VisitConfirmation::where('type', 'group')->count(),
            'recent' => VisitConfirmation::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('visits.index', compact('visits', 'stats'));
    }

    public function show($id)
    {
        $visit = VisitConfirmation::with('attendees')->findOrFail($id);
        
        if ($visit->type === 'single') {
            return view('visits.show-single', compact('visit'));
        } else {
            return view('visits.show-group', compact('visit'));
        }
    }

    // Public / Staff Verification Methods
    public function showVerifyPage()
    {
        return view('visits.verify');
    }

    public function verifyStaff(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        session(['verified_staff_email' => $request->email]);

        return redirect()->route('visits.selection');
    }

    public function showSelectionPage()
    {
        if (!session('verified_staff_email')) {
            return redirect()->route('visits.verify')->with('error', 'Please verify your staff email first.');
        }

        return view('visits.selection');
    }

    public function showSingleForm()
    {
        $email = session('verified_staff_email');
        if (!$email) {
            return redirect()->route('visits.verify');
        }
        $staff = User::where('email', $email)->firstOrFail();
        return view('visits.create-single', compact('staff'));
    }

    public function showGroupForm()
    {
        if (!session('verified_staff_email')) {
            return redirect()->route('visits.verify');
        }
        return view('visits.create-group');
    }

    public function store(Request $request)
    {
        $sender_email = session('verified_staff_email');
        if (!$sender_email) {
            return redirect()->route('visits.verify');
        }
        $staff = User::where('email', $sender_email)->first();

        // Common SMS Message Parts
        $smsBody = "Asante sana kwa kutukaribisha.\n" .
                   "EmCa Techonologies LTD inathamini fursa ya kushirikiana nanyi.\n" .
                   "Karibu kwa huduma na bidhaa za kidigitali - TEHAMA.\n" .
                   "Kwa mawasiliano zaidi tupigie 0764 628402 au 0749 719998 au\n" .
                   "Tembelea: www.emca.tech kwa maelezo zaidi.\n" .
                   "Asante.";

        if ($request->type === 'single') {
            $data = $request->validate([
                'visit_date' => 'required|date',
                'location' => 'nullable|string',
                'customer_name' => 'required|string',
                'contact_person' => 'nullable|string',
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
                'representative_name' => 'required|string',
                'representative_title' => 'nullable|string',
                'purpose' => 'nullable|string',
                'feedback' => 'nullable|string',
                'satisfaction_level' => 'nullable|string',
                'customer_signature' => 'nullable|string', // Base64
                'representative_signature' => 'nullable|string', // Base64
            ]);
            
            // Handle Signatures
            $customerSigPath = $this->saveSignature($request->customer_signature, 'signatures/customers');
            $repSigPath = $this->saveSignature($request->representative_signature, 'signatures/staff');

            $visit = VisitConfirmation::create([
                'type' => 'single',
                'visit_date' => $request->visit_date,
                'location' => $request->location,
                'customer_name' => $request->customer_name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'representative_name' => $request->representative_name,
                'representative_title' => $request->representative_title,
                'purpose' => $request->purpose,
                'feedback' => $request->feedback,
                'satisfaction_level' => $request->satisfaction_level,
                'customer_signature_path' => $customerSigPath,
                'representative_signature_path' => $repSigPath,
                'created_by_email' => $sender_email,
            ]);

            // SEND SMS TO CUSTOMER
            if ($request->phone) {
                $this->smsService->sendAndLog($request->phone, $smsBody, 'customer_visit', null, $staff ? $staff->id : null);
            }

            // SEND SMS TO STAFF
            if ($staff && $staff->phone) {
                $staffMessage = "Visit Confirmed with {$request->customer_name}.\n" . $smsBody;
                $this->smsService->sendAndLog($staff->phone, $staffMessage, 'staff_visit', null, $staff->id);
            }

        } elseif ($request->type === 'group') {
            $data = $request->validate([
                'subject' => 'required|string',
                'attendees' => 'required|array',
                'attendees.*.name' => 'required|string',
                'attendees.*.phone' => 'nullable|string',
            ]);
            
            $visit = VisitConfirmation::create([
                'type' => 'group',
                'subject' => $request->subject,
                'visit_date' => now(),
                'created_by_email' => $sender_email,
            ]);

            foreach ($request->attendees as $attendee) {
                $sigPath = null;
                if (isset($attendee['signature']) && $attendee['signature']) {
                     $sigPath = $this->saveSignature($attendee['signature'], 'signatures/attendees');
                }

                $visit->attendees()->create([
                    'name' => $attendee['name'],
                    'institution' => $attendee['institution'] ?? null,
                    'position' => $attendee['position'] ?? null,
                    'phone' => $attendee['phone'] ?? null,
                    'email' => $attendee['email'] ?? null,
                    'signature_path' => $sigPath,
                ]);

                // SEND SMS TO EACH ATTENDEE
                if (!empty($attendee['phone'])) {
                    $this->smsService->sendAndLog($attendee['phone'], $smsBody, 'group_visit', null, $staff ? $staff->id : null);
                }
            }

            // SEND SMS TO STAFF
            if ($staff && $staff->phone) {
                $staffMessage = "Group Visit Attendance Recorded: {$request->subject}.\n" . $smsBody;
                $this->smsService->sendAndLog($staff->phone, $staffMessage, 'staff_visit_group', null, $staff->id);
            }
        }

        return redirect()->route('visits.success');
    }
    
    public function showSuccessPage()
    {
        return view('visits.success');
    }

    private function saveSignature($base64Data, $folder)
    {
        if (!$base64Data) return null;

        // Verify it's a base64 image
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, etc

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                return null;
            }

            $data = base64_decode($data);
            if ($data === false) {
                return null;
            }
        } else {
            return null;
        }

        $filename = Str::random(20) . '.' . $type;
        $path = $folder . '/' . $filename;
        
        Storage::disk('public')->put($path, $data);

        return $path;
    }
}
