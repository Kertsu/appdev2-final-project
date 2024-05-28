<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userIds = User::pluck('id')->toArray();

        $initiator_id = $this->faker->randomElement($userIds);
        $recipient_id = $this->faker->randomElement(array_diff($userIds, [$initiator_id]));

        return [
            'initiator_id' => $initiator_id,
            'recipient_id' => $recipient_id,
            'initiator_username' => 'Whisp_' . Str::random(8),
        ];
    }
}
