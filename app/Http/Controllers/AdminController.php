<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, Invitation, Invoice, Package, Theme, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin', [
            'counts' => [
                'users' => User::where('role', '!=', 'admin')->count(),
                'drafts' => Invitation::where('status', Invitation::STATUS_DRAFT)->count(),
                'published' => Invitation::where('status', Invitation::STATUS_PUBLISHED)->count(),
                'invoices' => Invoice::query()->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            ],
            'recentInvoices' => Invoice::with('invitation.user')->latest()->paginate(10, ['*'], 'dashboard_page'),
        ]);
    }

    public function users(Request $request): View
    {
        $users = User::query()->where('role', '!=', 'admin')->when($request->input('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))->latest()->paginate(15)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function user(User $user): View
    {
        abort_if($user->isAdmin(), 404);
        return view('admin.users.show', ['user' => $user, 'invitations' => $user->invitations()->with('theme')->latest()->get()]);
    }

    public function invitations(Request $request): View
    {
        $invitations = Invitation::with(['user', 'theme'])->when($request->input('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%")))->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))->latest()->paginate(15)->withQueryString();
        return view('admin.invitations.index', compact('invitations'));
    }

    public function invitation(Invitation $invitation): View
    {
        return view('admin.invitations.show', compact('invitation'));
    }

    public function unpublish(Request $request, Invitation $invitation): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($invitation, $data) {
            $before = ['status' => $invitation->status, 'published_at' => optional($invitation->published_at)->toISOString()];
            $invitation->update(['status' => Invitation::STATUS_DRAFT, 'published_at' => null]);
            AuditLog::create(['actor_id' => request()->user()->id, 'action' => 'invitation.unpublish', 'target_type' => Invitation::class, 'target_id' => $invitation->id, 'reason' => $data['reason'], 'changes' => ['before' => $before, 'after' => ['status' => 'draft', 'published_at' => null]]]);
        });
        return back()->with('status', 'Undangan berhasil di-unpublish.');
    }

    public function themes(): View
    {
        return view('admin.themes.index', ['themes' => Theme::orderBy('sort_order')->get()]);
    }

    public function updateTheme(Request $request, Theme $theme): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        DB::transaction(function () use ($theme, $data) {
            $before = ['is_active' => $theme->is_active];
            $theme->update(['is_active' => $data['is_active']]);
            AuditLog::create(['actor_id' => request()->user()->id, 'action' => 'theme.availability_updated', 'target_type' => Theme::class, 'target_id' => $theme->id, 'changes' => ['before' => $before, 'after' => ['is_active' => $theme->is_active]]]);
        });
        return back()->with('status', 'Ketersediaan tema diperbarui.');
    }

    public function packages(): View
    {
        return view('admin.packages.index', ['packages' => Package::latest()->paginate(15)]);
    }

    public function updatePackage(Request $request, Package $package): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'price' => ['required', 'integer', 'min:0'], 'is_active' => ['required', 'boolean']]);
        DB::transaction(function () use ($package, $data) {
            $before = $package->only(['name', 'price', 'is_active']);
            $package->update($data);
            AuditLog::create(['actor_id' => request()->user()->id, 'action' => 'package.updated', 'target_type' => Package::class, 'target_id' => $package->id, 'changes' => ['before' => $before, 'after' => $package->only(['name', 'price', 'is_active'])]]);
        });
        return back()->with('status', 'Paket diperbarui.');
    }

    public function invoices(Request $request): View
    {
        $invoices = Invoice::with(['invitation.user'])->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))->when($request->input('q'), fn ($q, $term) => $q->where('reference', 'like', "%{$term}%"))->latest()->paginate(15)->withQueryString();
        return view('admin.invoices.index', compact('invoices'));
    }

    public function invoice(Invoice $invoice): View
    {
        return view('admin.invoices.show', compact('invoice'));
    }

    public function auditLogs(): View
    {
        return view('admin.audit.index', ['logs' => AuditLog::with('actor')->latest()->paginate(20)]);
    }
}