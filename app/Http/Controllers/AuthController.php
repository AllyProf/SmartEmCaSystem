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
    public function users(Request $request)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        $query = User::with('creator');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('staff_id', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->ajax()) {
            return view('auth.partials._user_table', compact('users'))->render();
        }

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

    /**
     * Show edit user form
     */
    public function editUser(User $user)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        // Only super admin can edit CEO
        if ($user->role === 'ceo' && !auth()->user()->isSuperAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        return view('auth.edit-user', compact('user'));
    }

    /**
     * Update user details
     */
    public function updateUser(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        // Only super admin can edit CEO unless they edit themselves
        if ($user->role === 'ceo' && !auth()->user()->isSuperAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'role' => 'required|in:ceo,hr,staff',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check permission limits on role assignment
        $authUser = auth()->user();
        if (($authUser->isCeo() || $authUser->isHr()) && $request->role === 'ceo' && $user->role !== 'ceo') {
            return back()->with('error', 'You do not have permission to assign CEO accounts.')->withInput();
        }
        
        if ($authUser->isHr() && !in_array($request->role, ['staff', 'hr'])) {
             return back()->with('error', 'You can only assign Staff or HR roles.')->withInput();
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Toggle User Active Status
     */
    public function toggleUserStatus(User $user)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        // Cannot toggle self
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        // Ceos can only be deactivated by Super Admin
        if ($user->role === 'ceo' && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Only the Super Admin can modify CEO status.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    /**
     * Delete user
     */
    public function destroyUser(User $user)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo() && !auth()->user()->isHr()) {
            abort(403);
        }

        // Cannot delete self
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Controls on deleting higher roles
        if ($user->role === 'ceo' && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Only Super Admin can delete CEO accounts.');
        }

        if ($user->role === 'hr' && auth()->user()->isHr()) {
            return back()->with('error', 'HR cannot delete other HR accounts.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
