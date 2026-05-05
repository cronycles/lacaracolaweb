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

        $utenti->load('role.permissions', 'permissionOverrides', 'permissionDenials');

        // Effective state: checked if the user effectively has the permission
        // (role grant or explicit grant, minus explicit denials)
        $rolePermissionIds   = $utenti->role?->permissions->pluck('id')->toArray() ?? [];
        $overrideIds         = $utenti->permissionOverrides->pluck('id')->toArray();
        $denialIds           = $utenti->permissionDenials->pluck('id')->toArray();
        $effectivePermissionIds = array_values(array_diff(
            array_unique(array_merge($rolePermissionIds, $overrideIds)),
            $denialIds
        ));

        return view('admin.users.edit', [
            'user'                   => $utenti,
            'roles'                  => $roles,
            'permissions'            => $permissions,
            'rolePermissionIds'      => $rolePermissionIds,
            'effectivePermissionIds' => $effectivePermissionIds,
        ]);
    }

    public function update(Request $request, User $utenti): RedirectResponse
    {
        $data = $request->validate([
            'role_id'       => ['nullable', 'exists:roles,id'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $utenti->role_id = $data['role_id'] ?? null;
        $utenti->save();

        // Reload role permissions after potential role change
        $utenti->load('role.permissions');

        $checkedIds     = array_map('intval', $data['permissions'] ?? []);
        $allPermissions = Permission::where('name', '!=', 'manage_users')->get();

        // Build sync payload:
        // - checked + not in role  → explicit grant  (denied=false)
        // - unchecked + in role    → explicit denial (denied=true)
        // - checked + in role      → no record needed (role already grants it)
        // - unchecked + not in role → no record needed (naturally denied)
        $syncData = [];
        foreach ($allPermissions as $perm) {
            $isChecked = in_array($perm->id, $checkedIds);
            $fromRole  = $utenti->role && $utenti->role->permissions->contains('id', $perm->id);

            if ($isChecked && ! $fromRole) {
                $syncData[$perm->id] = ['denied' => false];
            } elseif (! $isChecked && $fromRole) {
                $syncData[$perm->id] = ['denied' => true];
            }
        }

        $utenti->userPermissions()->sync($syncData);

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
