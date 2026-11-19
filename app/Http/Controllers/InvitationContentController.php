<?php

namespace App\Http\Controllers;

use App\Models\{Invitation, InvitationGiftOption, InvitationMedia, InvitationMessage, InvitationWishlist, WishlistReservation};
use Illuminate\Http\{RedirectResponse, Response};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Gate, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationContentController extends Controller
{
    public function index(Invitation $invitation): View
    {
        Gate::authorize('view', $invitation);
        $invitation->load(['media','giftOptions','wishlists.activeReservation']);
        $messages = $invitation->messages()->when(request('status'), fn ($q, $status) => $q->where('status', $status))->latest()->paginate(15)->withQueryString();
        return view('dashboard.invitations.content.index', compact('invitation', 'messages'));
    }

    public function messageAction(Invitation $invitation, InvitationMessage $message, string $action): RedirectResponse
    {
        $this->owned($invitation, $message);
        abort_unless(in_array($action, ['approve','reject'], true), 404);
        $message->update(['status' => $action === 'approve' ? 'approved' : 'rejected']);
        return back()->with('status', 'message-updated');
    }

    public function deleteMessage(Invitation $invitation, InvitationMessage $message): RedirectResponse { $this->owned($invitation,$message); $message->delete(); return back()->with('status','message-deleted'); }

    public function upload(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('update', $invitation);
        $request = request();
        $request->validate(['photo'=>'nullable|file|mimes:jpg,jpeg,png,webp|image|max:5120|dimensions:max_width=6000,max_height=6000','url'=>'nullable|url:http,https|max:2048','caption'=>'nullable|string|max:160','type'=>'required|in:photo,video,music']);
        if ($request->type === 'photo') {
            abort_if($invitation->media()->where('type','photo')->count() >= 30, 422, 'Maksimal 30 foto.');
            abort_unless($request->hasFile('photo'), 422, 'Foto wajib dipilih.');
            $media = $this->storePhoto($invitation, $request->file('photo'), $request->input('caption'));
        } else {
            abort_unless($request->filled('url'), 422, 'Link wajib diisi.');
            $media = $invitation->media()->create(['type'=>$request->type,'external_url'=>$request->url,'caption'=>$request->input('caption'),'sort_order'=>$invitation->media()->max('sort_order') + 1]);
        }
        return back()->with('status','media-created');
    }

    public function deleteMedia(Invitation $invitation, InvitationMedia $media): RedirectResponse { $this->owned($invitation,$media); $disk=Storage::disk(config('filesystems.default')); foreach([$media->path,$media->thumbnail_path] as $path) if($path) $disk->delete($path); $media->delete(); return back()->with('status','media-deleted'); }
    public function sortMedia(Invitation $invitation): RedirectResponse { Gate::authorize('update',$invitation); foreach((array)request('media',[]) as $order=>$id) $invitation->media()->whereKey($id)->update(['sort_order'=>$order]); return back()->with('status','media-sorted'); }

    public function gift(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('update',$invitation);
        request()->validate(['type'=>'required|in:bank,e_wallet,qris','label'=>'required|string|max:120','account_value'=>'nullable|string|max:120','account_name'=>'nullable|string|max:120','qr'=>'nullable|file|mimes:jpg,jpeg,png,webp|image|max:5120|dimensions:max_width=6000,max_height=6000','enabled'=>'nullable|boolean']);
        $data=request()->only(['type','label','account_value','account_name']); $data['enabled']=request()->boolean('enabled',true);
        if(request()->hasFile('qr')) $data['qr_path']=$this->storeImage(request()->file('qr'),'gifts/'.$invitation->id);
        $invitation->giftOptions()->create($data); return back()->with('status','gift-created');
    }
    public function deleteGift(Invitation $invitation, InvitationGiftOption $gift): RedirectResponse { $this->owned($invitation,$gift); if($gift->qr_path) Storage::disk(config('filesystems.default'))->delete($gift->qr_path); $gift->delete(); return back(); }

    public function wishlist(Invitation $invitation): RedirectResponse { Gate::authorize('update',$invitation); request()->validate(['name'=>'required|string|max:160','note'=>'nullable|string|max:1000','url'=>'nullable|url:http,https|max:2048']); $invitation->wishlists()->create(request()->only(['name','note','url'])); return back()->with('status','wishlist-created'); }
    public function deleteWishlist(Invitation $invitation, InvitationWishlist $wishlist): RedirectResponse { $this->owned($invitation,$wishlist); $wishlist->delete(); return back(); }
    public function cancelReservation(Invitation $invitation, InvitationWishlist $wishlist, WishlistReservation $reservation): RedirectResponse { $this->owned($invitation,$wishlist); abort_unless($reservation->wishlist_id===$wishlist->id,404); $reservation->update(['active_slot'=>null,'cancelled_at'=>now()]); return back()->with('status','reservation-cancelled'); }

    public function file(Invitation $invitation, InvitationMedia $media, string $variant='original'): Response
    {
        $this->owned($invitation,$media); abort_unless($invitation->status===Invitation::STATUS_PUBLISHED,404); abort_unless($media->type==='photo',404);
        $path=$variant==='thumb' ? $media->thumbnail_path : $media->path; abort_unless($path,404); $disk=Storage::disk(config('filesystems.default')); abort_unless($disk->exists($path),404); return response($disk->get($path),200,['Content-Type'=>$disk->mimeType($path),'Cache-Control'=>'private, no-store']);
    }

    public function previewFile(Invitation $invitation, InvitationMedia $media, string $variant='thumb'): Response
    {
        Gate::authorize('preview', $invitation); abort_unless($media->invitation_id === $invitation->id && $media->type === 'photo', 404);
        $path = $variant === 'original' ? $media->path : $media->thumbnail_path; $disk = Storage::disk(config('filesystems.default'));
        abort_unless($path && $disk->exists($path), 404); return response($disk->get($path), 200, ['Content-Type' => $disk->mimeType($path), 'Cache-Control' => 'private, no-store']);
    }

    public function previewGiftFile(Invitation $invitation, InvitationGiftOption $gift): Response
    {
        Gate::authorize('preview', $invitation); abort_unless($gift->invitation_id === $invitation->id && $gift->qr_path, 404); $disk=Storage::disk(config('filesystems.default')); abort_unless($disk->exists($gift->qr_path),404); return response($disk->get($gift->qr_path),200,['Content-Type'=>$disk->mimeType($gift->qr_path),'Cache-Control'=>'private, no-store']);
    }

    public function publicFile(string $slug, InvitationMedia $media, string $variant='thumb'): Response
    {
        $invitation = Invitation::published()->where('slug', $slug)->firstOrFail();
        abort_unless($media->invitation_id === $invitation->id && $media->type === 'photo', 404);
        $path = $variant === 'original' ? $media->path : $media->thumbnail_path;
        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($path && $disk->exists($path), 404);
        return response($disk->get($path), 200, ['Content-Type' => $disk->mimeType($path), 'Cache-Control' => 'private, no-store']);
    }

    public function giftFile(string $slug, InvitationGiftOption $gift): Response
    {
        $invitation = Invitation::published()->where('slug', $slug)->firstOrFail();
        abort_unless($gift->invitation_id === $invitation->id && $gift->enabled && $gift->qr_path, 404);
        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($disk->exists($gift->qr_path), 404);
        return response($disk->get($gift->qr_path), 200, ['Content-Type' => $disk->mimeType($gift->qr_path), 'Cache-Control' => 'private, no-store']);
    }

    private function storePhoto(Invitation $invitation, UploadedFile $file, ?string $caption): InvitationMedia
    {
        $disk=Storage::disk(config('filesystems.default')); $base='invitations/'.$invitation->id.'/media/'.Str::uuid(); $path=$file->storeAs(dirname($base),basename($base).'.'.$file->extension(),config('filesystems.default')); if(!$path) throw ValidationException::withMessages(['photo'=>'Foto gagal disimpan.']);
        $thumb=$this->thumbnail($disk->get($path),$base.'.jpg'); if(!$thumb){$disk->delete($path); throw ValidationException::withMessages(['photo'=>'Thumbnail gagal dibuat.']);}
        return $invitation->media()->create(['type'=>'photo','path'=>$path,'thumbnail_path'=>$thumb,'caption'=>$caption,'sort_order'=>$invitation->media()->max('sort_order')+1]);
    }
    private function storeImage(UploadedFile $file,string $dir): string { $path=$file->storeAs($dir,Str::uuid().'.'.$file->extension(),config('filesystems.default')); abort_unless($path,422,'File gagal disimpan.'); return $path; }
    private function thumbnail(string $contents,string $path): ?string { $source=@imagecreatefromstring($contents); if(!$source)return null; $width=imagesx($source);$height=imagesy($source);$scale=min(1,1200/$width,900/$height);$target=imagecreatetruecolor(max(1,(int)($width*$scale)),max(1,(int)($height*$scale))); imagecopyresampled($target,$source,0,0,0,0,imagesx($target),imagesy($target),$width,$height); ob_start(); imagejpeg($target,null,82);$data=ob_get_clean();imagedestroy($source);imagedestroy($target); $disk=Storage::disk(config('filesystems.default')); return $disk->put($path,$data) ? $path : null; }
    private function owned(Invitation $invitation, object $resource): void { abort_unless($resource->invitation_id===$invitation->id,404); Gate::authorize('update',$invitation); }
}