<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Facades\Log;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'description' => $this->description,
            'image' => $this->image,
            'qtyAvailable' => $this->qty_available,
            'category' => $this->whenLoaded('category') ? new CategoryResource($this->category) : null,
            'guage' => $this->whenLoaded('guage') ? new GuageResource($this->guage) : null,
            'addedBy' => $this->whenLoaded('added_by') ? new UserResource($this->added_by) : null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
