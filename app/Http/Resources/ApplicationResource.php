<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        $candidate = $request->user()?->isCandidate() && $request->user()->userable_id === $this->candidate_id;

        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'status' => $this->status,
            'cover_letter' => $this->cover_letter,
            'message' => $this->message,
            'attachments' => $this->resource->attachmentLinks(),
            'candidate_notes' => $candidate ? $this->candidate_notes : null,
            'applied_at' => $this->applied_at?->format('Y-m-d'),
            'opportunity' => [
                'id' => $this->opportunity->id,
                'title' => $this->opportunity->title,
                'kind' => $this->opportunity->kind,
                'public_url' => $this->opportunity->public_url,
                'field' => ['name' => $this->opportunity->field?->name],
                'city' => ['name' => $this->opportunity->location ?: $this->opportunity->city?->name ?: $this->opportunity->country_code],
                'closing_at' => $this->opportunity->closing_at?->format('F d, Y'),
            ],
            'candidate' => [
                'id' => $this->candidate->id,
                'user_id' => $this->candidate->user?->id,
                'name' => $this->candidate->user?->name,
                'email' => $this->candidate->user?->email,
                'phone_number' => $this->candidate->user?->phone_number,
                'image' => $this->candidate->user?->image,
            ],
            'company' => [
                'id' => $this->company->id,
                'user_id' => $this->channel === 'internal' ? $this->company->user?->id : null,
                'name' => $this->company->display_name,
                'image' => $this->company->logo,
                'email' => $this->channel === 'internal' ? $this->company->user?->email : null,
                'phone_number' => $this->channel === 'internal' ? $this->company->user?->phone_number : null,
                'city' => ['name' => $this->company->city?->name],
            ],
            'created_at' => $this->created_at->format('F d, Y'),
            'updated_at' => $this->updated_at->diffForHumans(),
        ];
    }
}
