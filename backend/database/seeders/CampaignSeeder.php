<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Campaign::firstOrCreate(
            ['slug' => 'help-for-hamid-abc123'],
            [
            'title' => 'Help For Hamid',
            'description' => 'Hamid needs urgent medical treatment. Your donation can save his life and help him recover from his illness.',
            'creator_name' => 'Dr. Rahman',
            'creator_email' => 'dr.rahman@example.com',
            'creator_phone' => '+8801711000000',
            'target_amount' => 50000.00,
            'current_amount' => 15000.00,
            'payment_type' => 'bkash',
        ]);

        \App\Models\Campaign::firstOrCreate(
            ['slug' => 'education-for-all-def456'],
            [
            'title' => 'Education For All',
            'description' => 'Supporting underprivileged children to get quality education and build a better future.',
            'creator_name' => 'Sarah Ahmed',
            'creator_email' => 'sarah@example.com',
            'creator_phone' => '+8801811000000',
            'target_amount' => 100000.00,
            'current_amount' => 25000.00,
            'payment_type' => 'nagad',
        ]);

        \App\Models\Campaign::firstOrCreate(
            ['slug' => 'clean-water-project-ghi789'],
            [
            'title' => 'Clean Water Project',
            'description' => 'Providing clean drinking water to rural communities in Bangladesh.',
            'creator_name' => 'Water Foundation',
            'creator_email' => 'info@waterfoundation.org',
            'creator_phone' => '+8801911000000',
            'target_amount' => 200000.00,
            'current_amount' => 75000.00,
            'payment_type' => 'bank',
        ]);

        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 50; $i++) {
            $title = $faker->sentence(3);
            \App\Models\Campaign::create([
                'title' => $title,
                'description' => $faker->paragraph,
                'creator_name' => $faker->name,
                'creator_email' => $faker->email,
                'creator_phone' => $faker->phoneNumber,
                'target_amount' => $faker->randomFloat(2, 10000, 500000),
                'current_amount' => $faker->randomFloat(2, 0, 10000),
                'payment_type' => $faker->randomElement(['bkash', 'nagad', 'bank', 'rocket']),
                'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
                'status' => 'approved',
                'is_active' => true,
                'user_id' => 1, // Assumptions: User ID 1 exists (usually created by AdminUserSeeder or manually) - wait, looking at the code, existing seeds don't set user_id. Let's check the migration or model.
            ]);
        }
    }
}
