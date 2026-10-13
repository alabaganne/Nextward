<?php

namespace App\Http\Resources;

use App\Models\Application;
use Illuminate\Http\Resources\Json\JsonResource;

class InternshipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
		$user = auth()->user();
		$student = $user && $user->isStudent() ? $user->userable : null;

		$application = $student ? Application::where('student_id', $student->id)
									->where('internship_id', $this->id)
									->first() : null;

        return [
			'id' => $this->id,
			'title' => $this->title,
			'description' => $this->description,
			'closing_at' => $this->closing_at->format('F d, Y'),
			'is_open' => $this->isOpen(),
			'work_type' => $this->work_type,
			'duration_weeks' => $this->duration_weeks,
			'term' => $this->term,
			'pay' => $this->pay_amount ? ['amount' => $this->pay_amount, 'unit' => $this->pay_unit] : null,
			'created_at' => $this->created_at->diffForHumans(),
			'company' => [
				'id' => $this->company->id,
				'user_id' => $this->company->user->id,
				'image' => $this->company->user->image,
				'name' => $this->company->user->name
			],
			'city' => [
				'name' => $this->city->name,
			],
			'field' => [
				'name' => $this->field->name,
			],
			'skills' => $this->skills->pluck('name'),
			'attachments' => $this->attachments ?: [],
			'applications_count' => $this->when($this->applications_count !== null, $this->applications_count),
			'is_new' => $this->created_at->gt(now()->subWeek()),
			'application' => $application ? [
				'created_at' => $application->created_at->format('F d, Y')
			] : null,
			'liked' => $student ? $student->likes->contains($this->id) : null,
		];
    }
}
