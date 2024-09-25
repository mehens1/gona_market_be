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
            'first_name' => $this->userDetail->first_name ?? null,
            'last_name' => $this->userDetail->last_name ?? null,
            'state' => $this->userDetail->state ? $this->userDetail->state : null,
            'lga' => $this->userDetail->lga ? $this->userDetail->lga : null,
        ];
    }
}
