<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User as User;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (gettype($this->resource) == 'object') {
            $id = $this->id;
        } else {
            $id = $this->resource;
        }
        $user = User::where('id', '=', $id)->first();
        $user->avatar = env("APP_URL", '127.0.0.1'). '/storage/'.$user->avatar;
        return $user;
    }
}
