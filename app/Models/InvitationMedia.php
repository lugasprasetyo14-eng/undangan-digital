<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvitationMedia extends Model { protected $fillable = ['invitation_id','type','path','thumbnail_path','external_url','caption','sort_order']; public function invitation(){return $this->belongsTo(Invitation::class);} }