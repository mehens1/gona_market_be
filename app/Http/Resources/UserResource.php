<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'first_name' => optional($this->userDetail)->first_name,
            'last_name' => optional($this->userDetail)->last_name,
            'address' => optional($this->userDetail)->address,
            'image_url' => optional($this->userDetail)->image_url,
            'state' => optional($this->userDetail)->state,
            'lga' => optional($this->userDetail)->lga,
        ];
    }
}
