<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Shared\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(protected QrCodeService $qrCodeService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('users.view');

        $users = User::query()
            ->where('role', UserRole::CustomerIndividual)
            ->withCount('vehicles')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('users.manage');

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('users.manage');

        $data = $request->validated();
        $codes = $this->qrCodeService->generatePair('USER');

        User::query()->create([
            ...$data,
            'role' => UserRole::CustomerIndividual,
            'status' => $data['status'] ?? UserStatus::Active->value,
            'qr_code' => $codes['qr_code'],
            'nfc_code' => $codes['nfc_code'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function show(User $user): View
    {
        $this->authorize('users.view');
        abort_unless($user->role === UserRole::CustomerIndividual, 404);

        $user->load(['vehicles', 'subscriptions.package', 'operations.service', 'wallet']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('users.manage');
        abort_unless($user->role === UserRole::CustomerIndividual, 404);

        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::CustomerIndividual, 404);

        $data = $request->validated();

        if ($request->user()->can('users.manage')) {
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $user->update($data);
        } elseif ($request->user()->can('users.activate')) {
            $user->update(['status' => $data['status']]);
        } else {
            abort(403);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('users.manage');
        abort_unless($user->role === UserRole::CustomerIndividual, 404);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->authorize('users.activate');
        abort_unless($user->role === UserRole::CustomerIndividual, 404);

        $user->update([
            'status' => $user->status === UserStatus::Active
                ? UserStatus::Inactive
                : UserStatus::Active,
        ]);

        return back()->with('success', 'تم تحديث حالة المستخدم.');
    }

    public function regenerateCodes(User $user): RedirectResponse
    {
        $this->authorize('users.manage');
        abort_unless($user->role === UserRole::CustomerIndividual, 404);

        $user->update($this->qrCodeService->generatePair('USER'));

        return back()->with('success', 'تم توليد رموز QR/NFC جديدة.');
    }
}
