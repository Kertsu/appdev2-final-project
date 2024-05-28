<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conversation = Conversation::inRandomOrder()->first();

        $sender_id = $this->faker->randomElement([$conversation->initiator_id, $conversation->recipient_id]);

        return [
            'sender_id' => $sender_id,
            'conversation_id' => $conversation->id,
            'content' => $this->faker->sentence(),
        ];
    }
}
