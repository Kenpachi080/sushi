<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SendMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return ['message' => "На почту $this->email был отпрввлен код $this->code"];
    }
}
