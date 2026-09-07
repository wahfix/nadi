<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Services\SequentialNumberService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_code' => app(SequentialNumberService::class)->generateCustomerCode(),
            'full_name' => fake()->name(),
            'national_id_number' => fake()->numerify('35'.str_repeat('#', 14)),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-21 years')->format('Y-m-d'),
            'gender' => fake()->randomElement([Customer::GENDER_MALE, Customer::GENDER_FEMALE]),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('08##########'),
            'status' => Customer::STATUS_ACTIVE,
        ];
    }
}