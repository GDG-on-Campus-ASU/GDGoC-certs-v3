<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Superadmins can see all non-superadmin users
        // Admins can only see leaders
        if ($currentUser->role === 'superadmin') {
            $users = User::whereIn('role', ['leader', 'admin'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $users = User::where('role', 'leader')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        // Define validation rules
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];

        // Only superadmins can assign roles
        if ($currentUser->role === 'superadmin') {
            $rules['role'] = ['required', Rule::in(['leader', 'admin'])];
        }

        $validated = $request->validate($rules);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->status = 'active';

        // Manual assignment of role since it's removed from fillable
        $user->role = $currentUser->role === 'superadmin' && isset($validated['role'])
            ? $validated['role']
            : 'leader';

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user)
    {
        $currentUser = $request->user();

        // Prevent editing superadmins
        if ($user->role === 'superadmin') {
            abort(403, 'Cannot edit superadmin users.');
        }

        // Admins cannot edit other admins
        if ($currentUser->role === 'admin' && $user->role === 'admin') {
            abort(403, 'Cannot edit other admin users.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        // Prevent editing superadmins
        if ($user->role === 'superadmin') {
            abort(403, 'Cannot edit superadmin users.');
        }

        // Admins cannot edit other admins
        if ($currentUser->role === 'admin' && $user->role === 'admin') {
            abort(403, 'Cannot edit other admin users.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'org_name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['required', Rule::in(['active', 'suspended', 'terminated'])],
            'termination_reason' => ['required_if:status,terminated', 'nullable', 'string'],
        ]);

        $user->name = $validated['name'];
        $user->org_name = $validated['org_name'];
        $user->status = $validated['status'];
        $user->termination_reason = $validated['status'] === 'terminated'
            ? $validated['termination_reason']
            : null;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        // Prevent deleting superadmins
        if ($user->role === 'superadmin') {
            abort(403, 'Cannot delete superadmin users.');
        }

        // Admins cannot delete other admins
        if ($currentUser->role === 'admin' && $user->role === 'admin') {
            abort(403, 'Cannot delete other admin users.');
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            abort(403, 'Cannot delete your own account.');
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            abort(403, 'Cannot delete your own account.');
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            abort(403, 'Cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
