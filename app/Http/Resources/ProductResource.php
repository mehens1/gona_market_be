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
        $guage = $this->whenLoaded('guage') ? new GuageResource($this->guage) : null;

        // Debug statements
        if (is_null($guage)) {
            Log::debug('Guage is null');
        } else {
            Log::debug('Guage ID: ' . $guage->id);
        }


        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'description' => $this->description,
            'image' => $this->image,
            'qtyAvailable' => $this->qty_available,
            'category' => $this->category,
            'guage' => $this->whenLoaded('guage') ? new GuageResource($this->guage) : null,
            'addedBy' => $this->added_by,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
