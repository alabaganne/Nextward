<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        $messages = [
            "Hello! I saw your opportunity posting and I am very interested. Could you tell me more about the day-to-day responsibilities?",
            "Thank you for your application. We would like to schedule an interview. Are you available this week?",
            "I have reviewed your CV and I am impressed by your projects. When can you start?",
            "Could you please send me your portfolio or any code samples you have worked on?",
            "The role is full-time. The team is based in Tunis. Does that work for you?",
            "We have received your cover letter. We will get back to you within the next few days.",
            "Hello, I just wanted to follow up on my application submitted last week. Any updates?",
            "Our team reviewed your profile and we think you would be a great fit. Can we hop on a quick call?",
            "Yes, I am available for an interview on Wednesday or Thursday afternoon.",
            "Thank you so much for the opportunity! I am very excited to join your team.",
            "The technical interview will involve a small coding task. We will send the details by email.",
            "I have a few questions about the tech stack you use. Is this a good time to discuss?",
            "We work with React on the front-end and Node.js on the back-end. Does that match your experience?",
            "Looking forward to meeting you. The interview will be at our Tunis office.",
            "Congratulations! We would like to offer you the position. Please confirm your availability.",
        ];

        $userIds = User::pluck('id')->toArray();

        if (count($userIds) < 2) {
            return ['from_id' => 1, 'to_id' => 2, 'text' => $this->faker->randomElement($messages), 'read_at' => now()];
        }

        do {
            $from = $this->faker->randomElement($userIds);
            $to   = $this->faker->randomElement($userIds);
        } while ($from === $to);

        return [
            'from_id' => $from,
            'to_id'   => $to,
            'text'    => $this->faker->randomElement($messages),
            'read_at' => now(),
        ];
    }
}
