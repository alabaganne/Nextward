<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'status' => $this->status,
            'cover_letter' => $this->cover_letter,
            'message' => $this->message,
            'attachments' => $this->attachments ?: [],
            'internship' => [
                'id' => $this->internship->id,
                'title' => $this->internship->title,
                'field' => [
					'name' => $this->internship->field->name,
				],
				'city' => [
					'name' => $this->internship->city->name,
				],
                'closing_at' => $this->internship->closing_at->format('F d, Y'),
            ],
            'student' => [
                'id' => $this->student->id,
                'user_id' => $this->student->user->id,
                'name' => $this->student->user->name,
                'email' => $this->student->user->email,
                'image' => $this->student->user->image,
            ],
            'company' => [
                'id' => $this->company->id,
                'user_id' => $this->company->user->id,
                'name' => $this->company->user->name,
                'image' => $this->company->user->image,
            ],
            'created_at' => $this->created_at->format('F d, Y'),
        ];

        return $data;
    }
}
