<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvitationGiftOption extends Model { protected $fillable = ['invitation_id','type','label','account_value','account_name','qr_path','enabled']; protected $casts = ['enabled'=>'boolean']; public function invitation(){return $this->belongsTo(Invitation::class);} }