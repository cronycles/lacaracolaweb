<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('role')->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id'  => ['nullable', 'exists:roles,id'],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id'  => $data['role_id'] ?? null,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente creato con successo.');
    }

    public function edit(User $utenti): View
    {
        $roles       = Role::orderBy('name')->get();
        $permissions = Permission::where('name', '!=', 'manage_users')->orderBy('name')->get();

        $utenti->load('role', 'permissionOverrides');

        return view('admin.users.edit', [
            'user'        => $utenti,
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, User $utenti): RedirectResponse
    {
        $data = $request->validate([
            'role_id'     => ['nullable', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $utenti->role_id = $data['role_id'] ?? null;
        $utenti->save();

        // Sync per-user permission overrides (manage_users is excluded from the form)
        $utenti->permissionOverrides()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente aggiornato.');
    }

    public function destroy(Request $request, User $utenti): RedirectResponse
    {
        if ($utenti->id === $request->user()->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Non puoi eliminare il tuo stesso account.');
        }

        $utenti->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente eliminato.');
    }
}
