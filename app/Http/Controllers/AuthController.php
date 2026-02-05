<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo()) {
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
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo()) {
            abort(403);
        }
        return view('auth.create-user');
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isCeo()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:ceo,staff',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Only Super Admin can create CEO, CEO can only create staff
        $user = auth()->user();
        if ($user->isCeo() && $request->role === 'ceo') {
            return back()->with('error', 'You do not have permission to create CEO accounts.')->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }
}
