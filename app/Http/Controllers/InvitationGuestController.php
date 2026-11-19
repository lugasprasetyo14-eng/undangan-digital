<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationGuestRequest;
use App\Http\Requests\UpdateInvitationGuestRequest;
use App\Models\Invitation;
use App\Models\InvitationGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use chillerlan\QRCode\QRCode;

class InvitationGuestController extends Controller
{
    public function index(Invitation $invitation): View
    {
        Gate::authorize('view', $invitation);
        $guests = $invitation->guests()->when(request('q'), fn ($query, $q) => $query->where('name', 'like', '%'.$q.'%'))
            ->when(request('status'), fn ($query, $status) => $query->where('rsvp_status', $status))->latest()->paginate(15)->withQueryString();
        $stats = $this->stats($invitation);

        return view('dashboard.invitations.guests.index', compact('invitation', 'guests', 'stats'));
    }

    public function create(Invitation $invitation): View
    {
        Gate::authorize('view', $invitation);
        return view('dashboard.invitations.guests.form', ['invitation' => $invitation, 'guest' => null]);
    }

    public function store(StoreInvitationGuestRequest $request, Invitation $invitation): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        $invitation->guests()->create($request->validated());
        return to_route('dashboard.invitations.guests.index', $invitation)->with('status', 'guest-created');
    }

    public function edit(Invitation $invitation, InvitationGuest $guest): View
    {
        $this->authorizeGuest($invitation, $guest, 'view');
        return view('dashboard.invitations.guests.form', compact('invitation', 'guest'));
    }

    public function update(UpdateInvitationGuestRequest $request, Invitation $invitation, InvitationGuest $guest): RedirectResponse
    {
        $this->authorizeGuest($invitation, $guest, 'update');
        DB::transaction(function () use ($guest, $request) {
            $locked = InvitationGuest::whereKey($guest->id)->lockForUpdate()->firstOrFail();
            if ($request->integer('max_guests') < $locked->attending_count) {
                throw ValidationException::withMessages(['max_guests' => 'Batas tamu lebih kecil dari jumlah yang sudah dikonfirmasi. Ubah RSVP terlebih dahulu.']);
            }
            $locked->update($request->validated());
        });
        return to_route('dashboard.invitations.guests.index', $invitation)->with('status', 'guest-updated');
    }

    public function destroy(Invitation $invitation, InvitationGuest $guest): RedirectResponse
    {
        $this->authorizeGuest($invitation, $guest, 'delete');
        $guest->delete();
        return back()->with('status', 'guest-deleted');
    }

    public function export(Invitation $invitation): Response
    {
        Gate::authorize('view', $invitation);
        $guests = $invitation->guests()->orderBy('name')->get();
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Nama', 'Kategori', 'Kontak', 'Maksimal Tamu', 'Status RSVP', 'Jumlah Hadir', 'Check-in']);
        foreach ($guests as $guest) {
            fputcsv($handle, [self::csvSafe($guest->name), self::csvSafe($guest->category), self::csvSafe($guest->phone ?: $guest->email), $guest->max_guests, $guest->rsvp_status, $guest->attending_count, $guest->checked_in_at ? 'Ya' : 'Belum']);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="daftar-tamu-'.$invitation->slug.'.csv"']);
    }

    public function qr(Invitation $invitation, InvitationGuest $guest): Response
    {
        $this->authorizeGuest($invitation, $guest, 'view');
        return response($guest->qrImage(), 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store']);
    }

    public function verify(Invitation $invitation, string $token): View
    {
        Gate::authorize('view', $invitation);
        $guest = $invitation->guests()->where('qr_token', $token)->firstOrFail();
        return view('dashboard.invitations.guests.verify', compact('invitation', 'guest'));
    }

    public function checkIn(Invitation $invitation, string $token): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        abort_unless($invitation->status === Invitation::STATUS_PUBLISHED, 404);
        $guest = $invitation->guests()->where('qr_token', $token)->firstOrFail();
        $updated = $invitation->guests()->whereKey($guest->id)->whereNull('checked_in_at')->update(['checked_in_at' => now()]);
        return to_route('dashboard.invitations.guests.verify', [$invitation, $token])->with('status', $updated ? 'guest-checked-in' : 'guest-checkin-existing');
    }

    private function authorizeGuest(Invitation $invitation, InvitationGuest $guest, string $ability): void
    {
        abort_unless($guest->invitation_id === $invitation->id, 404);
        Gate::authorize($ability, $guest);
    }

    private function stats(Invitation $invitation): array
    {
        $query = $invitation->guests();
        return ['total' => (clone $query)->count(), 'pending' => (clone $query)->where('rsvp_status', 'pending')->count(), 'attending' => (clone $query)->where('rsvp_status', 'attending')->count(), 'not_attending' => (clone $query)->where('rsvp_status', 'not_attending')->count(), 'people' => (clone $query)->where('rsvp_status', 'attending')->sum('attending_count')];
    }

    private static function csvSafe(?string $value): string
    {
        $value = (string) $value;
        return preg_match('/^[\s\x00-\x1F]*[=+\-@]|^[\t\r\n]/', $value) ? "'".$value : $value;
    }
}