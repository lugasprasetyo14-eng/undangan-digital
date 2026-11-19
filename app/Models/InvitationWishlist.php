<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvitationWishlist extends Model { protected $fillable = ['invitation_id','name','note','url','enabled']; protected $casts = ['enabled'=>'boolean']; public function invitation(){return $this->belongsTo(Invitation::class);} public function reservations(){return $this->hasMany(WishlistReservation::class,'wishlist_id');} public function activeReservation(){return $this->hasOne(WishlistReservation::class,'wishlist_id')->whereNotNull('active_slot');} }