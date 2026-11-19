<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class InvitationMessage extends Model { use HasFactory; protected $fillable = ['invitation_id','guest_id','name','message','status']; public function invitation(){return $this->belongsTo(Invitation::class);} public function guest(){return $this->belongsTo(InvitationGuest::class);} }