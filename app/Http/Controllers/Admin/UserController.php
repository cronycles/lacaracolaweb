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
use Illuminate\Validation\Rule;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'telegram_chat_id' => ['nullable', 'string', 'max:64'],
            'tax_code' => ['nullable', 'string', 'max:16'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_zip' => ['nullable', 'string', 'max:10'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'payment_beneficiary' => ['nullable', 'string', 'max:255'],
            'payment_iban' => ['nullable', 'string', 'max:34'],
            'payment_bic' => ['nullable', 'string', 'max:11'],
            'payment_enabled' => ['boolean'],
        ]);

        $isHostOwner = Role::whereKey($data['role_id'] ?? null)->where('name', 'host_owner')->exists();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'] ?? null,
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'tax_code' => $isHostOwner ? ($data['tax_code'] ?? null) : null,
            'address_street' => $isHostOwner ? ($data['address_street'] ?? null) : null,
            'address_zip' => $isHostOwner ? ($data['address_zip'] ?? null) : null,
            'address_city' => $isHostOwner ? ($data['address_city'] ?? null) : null,
            'payment_beneficiary' => $isHostOwner ? ($data['payment_beneficiary'] ?? null) : null,
            'payment_iban' => $isHostOwner ? ($data['payment_iban'] ?? null) : null,
            'payment_bic' => $isHostOwner ? ($data['payment_bic'] ?? null) : null,
            'payment_enabled' => $isHostOwner && (bool) ($data['payment_enabled'] ?? false),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utente creato con successo.');
    }

    public function edit(User $utenti): View
    {
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::where('name', '!=', 'manage_users')->orderBy('name')->get();

        $utenti->load('role.permissions', 'permissionOverrides', 'permissionDenials');

        // Effective state: checked if the user effectively has the permission
        // (role grant or explicit grant, minus explicit denials)
        $rolePermissionIds = $utenti->role?->permissions->pluck('id')->toArray() ?? [];
        $overrideIds = $utenti->permissionOverrides->pluck('id')->toArray();
        $denialIds = $utenti->permissionDenials->pluck('id')->toArray();
        $effectivePermissionIds = array_values(array_diff(
            array_unique(array_merge($rolePermissionIds, $overrideIds)),
            $denialIds
        ));

        return view('admin.users.edit', [
            'user' => $utenti,
            'roles' => $roles,
            'permissions' => $permissions,
            'rolePermissionIds' => $rolePermissionIds,
            'effectivePermissionIds' => $effectivePermissionIds,
        ]);
    }

    public function update(Request $request, User $utenti): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($utenti->id)],
            'role_id' => ['nullable', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'telegram_chat_id' => ['nullable', 'string', 'max:64'],
            'telegram_notifications_enabled' => ['boolean'],
            'tax_code' => ['nullable', 'string', 'max:16'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_zip' => ['nullable', 'string', 'max:10'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'payment_beneficiary' => ['nullable', 'string', 'max:255'],
            'payment_iban' => ['nullable', 'string', 'max:34'],
            'payment_bic' => ['nullable', 'string', 'max:11'],
            'payment_enabled' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $utenti->email = $data['email'];
        $utenti->role_id = $data['role_id'] ?? null;
        $utenti->phone = $data['phone'] ?? null;
        $utenti->telegram_chat_id = $data['telegram_chat_id'] ?? null;
        $utenti->telegram_notifications_enabled = (bool) ($data['telegram_notifications_enabled'] ?? false);
        $isHostOwner = Role::whereKey($utenti->role_id)->where('name', 'host_owner')->exists();
        $utenti->tax_code = $isHostOwner ? ($data['tax_code'] ?? null) : null;
        $utenti->address_street = $isHostOwner ? ($data['address_street'] ?? null) : null;
        $utenti->address_zip = $isHostOwner ? ($data['address_zip'] ?? null) : null;
        $utenti->address_city = $isHostOwner ? ($data['address_city'] ?? null) : null;
        $utenti->payment_beneficiary = $isHostOwner ? ($data['payment_beneficiary'] ?? null) : null;
        $utenti->payment_iban = $isHostOwner ? ($data['payment_iban'] ?? null) : null;
        $utenti->payment_bic = $isHostOwner ? ($data['payment_bic'] ?? null) : null;
        $utenti->payment_enabled = $isHostOwner && (bool) ($data['payment_enabled'] ?? false);
        $utenti->save();

        // Reload role permissions after potential role change
        $utenti->load('role.permissions');

        $checkedIds = array_map('intval', $data['permissions'] ?? []);
        $allPermissions = Permission::where('name', '!=', 'manage_users')->get();

        // Build sync payload:
        // - checked + not in role  → explicit grant  (denied=false)
        // - unchecked + in role    → explicit denial (denied=true)
        // - checked + in role      → no record needed (role already grants it)
        // - unchecked + not in role → no record needed (naturally denied)
        $syncData = [];
        foreach ($allPermissions as $perm) {
            $isChecked = in_array($perm->id, $checkedIds);
            $fromRole = $utenti->role && $utenti->role->permissions->contains('id', $perm->id);

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
