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
    public function index()
    {
        $users = User::where('role', '!=', 'superadmin')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'leader',
            'status' => 'active',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Prevent editing other superadmins
        if ($user->role === 'superadmin') {
            abort(403, 'Cannot edit superadmin users.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Prevent editing other superadmins
        if ($user->role === 'superadmin') {
            abort(403, 'Cannot edit superadmin users.');
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
    public function destroy(User $user)
    {
        // Prevent deleting superadmins
        if ($user->role === 'superadmin') {
            abort(403, 'Cannot delete superadmin users.');
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
