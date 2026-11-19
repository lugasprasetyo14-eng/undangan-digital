<?php

use App\Models\{Invitation, InvitationGuest, InvitationGiftOption, InvitationMedia, InvitationMessage, InvitationWishlist, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('renders all owner content panels empty and populated without exposing them to another account', function () {
    $owner = User::factory()->create();
    $invitation = Invitation::factory()->create(['user_id' => $owner->id]);
    $url = route('dashboard.invitations.content.index', $invitation);
    $this->actingAs($owner)->get($url)->assertOk()->assertSee('Belum ada ucapan.')
        ->assertSee('Filter status')->assertSee('Tambah media')->assertSee('Simpan tujuan')->assertSee('Tambah wishlist');

    foreach (['pending', 'approved', 'rejected'] as $status) {
        $invitation->messages()->create(['name' => 'QA '.$status, 'message' => 'Doa '.$status, 'status' => $status]);
    }
    $invitation->media()->create(['type' => 'photo', 'path' => 'qa/photo.jpg', 'thumbnail_path' => 'qa/thumb.jpg', 'caption' => 'Foto QA']);
    $invitation->media()->create(['type' => 'music', 'external_url' => 'https://example.com/qa.mp3']);
    $invitation->giftOptions()->create(['type' => 'bank', 'label' => 'Bank QA', 'enabled' => true]);
    $invitation->wishlists()->create(['name' => 'Item tersedia']);
    $item = $invitation->wishlists()->create(['name' => 'Item dipesan']);
    $guest = InvitationGuest::factory()->create(['invitation_id' => $invitation->id]);
    $item->reservations()->create(['guest_id' => $guest->id, 'reserved_at' => now(), 'active_slot' => 1]);

    $this->get($url)->assertOk()->assertSee('Setujui')->assertSee('Tolak')
        ->assertSee('Doa pending')->assertSee('Doa approved')->assertSee('Doa rejected')
        ->assertSee('Tambah media')->assertSee('Foto QA')->assertSee('Music')
        ->assertSee('Simpan tujuan')->assertSee('Bank QA')->assertSee('Tambah wishlist')
        ->assertSee('Tersedia')->assertSee('Sudah dipesan')->assertSee('Batalkan reservasi');
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
});

it('moderates public messages and rejects preview writes', function () {
    $owner = User::factory()->create(); $invitation = Invitation::factory()->published()->create(['user_id'=>$owner->id]);
    $this->post(route('invitations.messages.store',$invitation->slug), ['name'=>'A','message'=>'Doa'])->assertRedirect()->assertSessionHas('status','message-pending');
    $message = InvitationMessage::first(); expect($message->status)->toBe('pending');
    $this->actingAs($owner)->post(route('dashboard.invitations.content.messages.action',[$invitation,$message,'approve']))->assertRedirect();
    $this->get(route('invitations.public',$invitation->slug))->assertSee('Doa');
    $this->actingAs($owner)->get(route('dashboard.invitations.preview',$invitation))->assertOk()->assertDontSee('method="POST" action="'.route('invitations.messages.store',$invitation->slug).'"');
});

it('scopes message moderation and media access to the invitation and published state', function () {
    Storage::fake('local'); $owner=User::factory()->create(); $other=User::factory()->create(); $one=Invitation::factory()->published()->create(['user_id'=>$owner->id]); $two=Invitation::factory()->published()->create(['user_id'=>$other->id]); $message=InvitationMessage::create(['invitation_id'=>$one->id,'name'=>'x','message'=>'y']);
    $this->actingAs($other)->post(route('dashboard.invitations.content.messages.action',[$two,$message,'approve']))->assertNotFound();
    Storage::disk('local')->put('invitations/1/media/a.jpg','x'); $media=InvitationMedia::create(['invitation_id'=>$one->id,'type'=>'photo','path'=>'invitations/1/media/a.jpg','thumbnail_path'=>'invitations/1/media/a.jpg']);
    $this->get(route('invitations.media.file',[$one->slug,$media,'thumb']))->assertOk(); $one->update(['status'=>'draft']); $this->get(route('invitations.media.file',[$one->slug,$media,'thumb']))->assertNotFound();
});

it('keeps wishlist reservation personal, prevents second active reservation, and reopens after cancellation', function () {
    $owner=User::factory()->create(); $invitation=Invitation::factory()->published()->create(['user_id'=>$owner->id]); $first=InvitationGuest::factory()->create(['invitation_id'=>$invitation->id]); $second=InvitationGuest::factory()->create(['invitation_id'=>$invitation->id]); $item=InvitationWishlist::create(['invitation_id'=>$invitation->id,'name'=>'Kamera','enabled'=>true]);
    $this->post(route('invitations.wishlist.reserve',[$invitation->slug,$item]),['to'=>$first->rsvp_token])->assertRedirect()->assertSessionHas('status','wishlist-reserved');
    $this->post(route('invitations.wishlist.reserve',[$invitation->slug,$item]),['to'=>$second->rsvp_token])->assertRedirect()->assertSessionHasErrors('wishlist');
    $reservation=$item->activeReservation; $this->actingAs($owner)->post(route('dashboard.invitations.content.wishlist.cancel',[$invitation,$item,$reservation]))->assertRedirect();
    $this->post(route('invitations.wishlist.reserve',[$invitation->slug,$item]),['to'=>$second->rsvp_token])->assertRedirect()->assertSessionHas('status','wishlist-reserved');
    $this->post(route('invitations.wishlist.reserve',[$invitation->slug,$item]),[])->assertNotFound();
});

it('renders personal reservation feedback after success conflict and retry across all themes', function (string $theme) {
    $invitation = Invitation::factory()->published()->create([
        'theme_id' => \App\Models\Theme::factory()->create(['slug' => $theme])->id,
    ]);
    $first = InvitationGuest::factory()->create(['invitation_id' => $invitation->id]);
    $second = InvitationGuest::factory()->create(['invitation_id' => $invitation->id]);
    $item = $invitation->wishlists()->create(['name' => 'Kamera QA', 'enabled' => true]);
    $post = route('invitations.wishlist.reserve', [$invitation->slug, $item]);
    $success = 'Reservasi berhasil. Hadiah ini sudah kamu pesan.';
    $conflict = 'Hadiah ini sudah dipesan tamu lain. Silakan pilih hadiah lain.';
    $personal = fn ($guest) => route('invitations.public', ['slug' => $invitation->slug, 'to' => $guest->rsvp_token]);

    $this->get($personal($first))->assertSee('Pesan untuk kami');
    $this->get($personal($second))->assertSee('Pesan untuk kami');
    $response = $this->withHeader('Referer', 'https://example.com/untrusted')->post($post, ['to' => $first->rsvp_token]);
    $response->assertRedirect($personal($first));
    $page = $this->followRedirects($response)->assertOk()->assertSee('Untuk '.$first->name)->assertSee($success)
        ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    $public = route('invitations.public', $invitation->slug);
    $page->assertSee('<link rel="canonical" href="'.$public.'">', false)
        ->assertSee('<meta property="og:url" content="'.$public.'">', false)
        ->assertSee('https://wa.me/?text='.urlencode($public), false);
    $reservation = $item->activeReservation()->firstOrFail();
    expect($item->reservations()->whereNotNull('active_slot')->count())->toBe(1);

    $response = $this->post($post, ['to' => $second->rsvp_token]);
    $response->assertRedirect($personal($second));
    $this->followRedirects($response)->assertOk()->assertSee('Untuk '.$second->name)->assertSee($conflict)
        ->assertDontSee($first->name)->assertDontSee($first->email);
    expect($item->activeReservation()->firstOrFail()->id)->toBe($reservation->id);
    expect($reservation->refresh()->guest_id)->toBe($first->id);

    $response = $this->post($post, ['to' => $first->rsvp_token]);
    $response->assertRedirect($personal($first));
    $this->followRedirects($response)->assertOk()->assertSee($success)->assertDontSee($conflict);
    expect($item->reservations()->count())->toBe(1);
    $this->post($post)->assertNotFound();
    $this->post($post, ['to' => 'invalid'])->assertNotFound();
    $foreign = InvitationGuest::factory()->create();
    $this->post($post, ['to' => $foreign->rsvp_token])->assertNotFound();
})->with(['elegant-ivory', 'modern-minimal', 'romantic-blush']);

it('rejects invalid upload extensions and cleans media files when invitation is deleted', function () {
    Storage::fake('local'); $owner=User::factory()->create(); $invitation=Invitation::factory()->create(['user_id'=>$owner->id]);
    $this->actingAs($owner)->post(route('dashboard.invitations.content.media.store',$invitation),['type'=>'photo','photo'=>UploadedFile::fake()->create('x.svg',10,'image/svg+xml')])->assertSessionHasErrors('photo');
    Storage::disk('local')->put('invitations/1/media/x.jpg','x'); InvitationMedia::create(['invitation_id'=>$invitation->id,'type'=>'photo','path'=>'invitations/1/media/x.jpg','thumbnail_path'=>'invitations/1/media/x.jpg']); $this->actingAs($owner)->delete(route('dashboard.invitations.destroy',$invitation)); expect(Storage::disk('local')->exists('invitations/1/media/x.jpg'))->toBeFalse();
});