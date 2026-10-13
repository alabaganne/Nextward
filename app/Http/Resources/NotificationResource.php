<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = $this->data ?? [];
        $data['body_text'] = html_entity_decode(
            strip_tags((string) ($data['body'] ?? '')),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        return [
            'id' => $this->id,
            'data' => $data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at->diffForHumans()
        ];
    }
}
