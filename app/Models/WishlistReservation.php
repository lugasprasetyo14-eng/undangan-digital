<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WishlistReservation extends Model { protected $fillable = ['wishlist_id','guest_id','reserved_at','cancelled_at','active_slot']; protected $casts = ['reserved_at'=>'datetime','cancelled_at'=>'datetime']; public function wishlist(){return $this->belongsTo(InvitationWishlist::class,'wishlist_id');} public function guest(){return $this->belongsTo(InvitationGuest::class);} }