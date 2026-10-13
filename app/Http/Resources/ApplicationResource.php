<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        $candidate = $request->user()?->isCandidate() && $request->user()->userable_id === $this->student_id;

        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'status' => $this->status,
            'cover_letter' => $this->cover_letter,
            'message' => $this->message,
            'attachments' => $this->resource->attachmentLinks(),
            'candidate_notes' => $candidate ? $this->candidate_notes : null,
            'applied_at' => $this->applied_at?->format('Y-m-d'),
            'internship' => [
                'id' => $this->internship->id,
                'title' => $this->internship->title,
                'kind' => $this->internship->kind,
                'public_url' => $this->internship->public_url,
                'field' => ['name' => $this->internship->field?->name],
                'city' => ['name' => $this->internship->location ?: $this->internship->city?->name ?: $this->internship->country_code],
                'closing_at' => $this->internship->closing_at?->format('F d, Y'),
            ],
            'student' => [
                'id' => $this->student->id,
                'user_id' => $this->student->user?->id,
                'name' => $this->student->user?->name,
                'email' => $this->student->user?->email,
                'phone_number' => $this->student->user?->phone_number,
                'image' => $this->student->user?->image,
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
