<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\PasswordOtpReset;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->with('error', 'Invalid email or password.')->withInput();
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show user management (for CEO and Super Admin)
     */
    public function users()
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        $users = User::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('auth.users', compact('users'));
    }

    /**
     * Show create user form
     */
    public function createUser()
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }
        return view('auth.create-user');
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:ceo,hr,staff',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Only Super Admin can create CEO, CEO/HR can only create HR/staff
        $user = auth()->user();
        if (($user->isCeo() || $user->isHr()) && $request->role === 'ceo') {
            return back()->with('error', 'You do not have permission to create CEO accounts.')->withInput();
        }
        
        // Let's also say HR can only create staff, but allow CEO to create HR. Or HR can create HR as well.
        if ($user->isHr() && !in_array($request->role, ['staff', 'hr'])) {
             return back()->with('error', 'You can only create Staff or HR accounts.')->withInput();
        }

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'created_by' => auth()->id(),
        ]);

        // Auto-generate a staff ID (e.g., EMC005)
        $newUser->update([
            'staff_id' => 'EMC' . str_pad($newUser->id, 3, '0', STR_PAD_LEFT)
        ]);


        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Show Forgot Password Form
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send OTP to Phone
     */
    public function sendOtp(Request $request, SmsService $smsService)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
        ]);

        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        PasswordOtpReset::updateOrCreate(
            ['phone' => $request->phone],
            [
                'otp' => $otp,
                'expires_at' => $expiresAt,
                'is_verified' => false
            ]
        );

        $message = "Your SmartEmCa password reset OTP is: {$otp}. It expires in 10 minutes.";
        $smsService->sendAndLog($request->phone, $message, 'password_reset_otp');

        session(['reset_phone' => $request->phone]);

        return redirect()->route('password.otp.verify')->with('success', 'OTP has been sent to your phone number.');
    }

    /**
     * Show Verify OTP Form
     */
    public function showVerifyOtpForm()
    {
        if (!session('reset_phone')) {
            return redirect()->route('password.request');
        }
        return view('auth.verify-otp');
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $phone = session('reset_phone');
        $reset = PasswordOtpReset::where('phone', $phone)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$reset) {
            return back()->with('error', 'Invalid or expired OTP.');
        }

        $reset->update(['is_verified' => true]);

        return redirect()->route('password.reset')->with('success', 'OTP verified. Now you can change your password.');
    }

    /**
     * Show Reset Password Form
     */
    public function showResetPasswordForm()
    {
        $phone = session('reset_phone');
        $reset = PasswordOtpReset::where('phone', $phone)->where('is_verified', true)->first();

        if (!$reset) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $phone = session('reset_phone');
        $reset = PasswordOtpReset::where('phone', $phone)->where('is_verified', true)->first();

        if (!$reset) {
            return redirect()->route('password.request');
        }

        $user = User::where('phone', $phone)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
            
            // Cleanup
            $reset->delete();
            session()->forget('reset_phone');

            return redirect()->route('login')->with('success', 'Password reset successfully. You can now login.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    /**
     * Reset Staff Device ID (Phone-Lock)
     */
    public function resetDevice(User $user)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        $user->update(['device_id' => null]);

        return back()->with('success', "Device lock has been reset for {$user->name}. They can now login from a new phone.");
    }
}
