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
        \App\Models\Campaign::create([
            'title' => 'Help For Hamid',
            'description' => 'Hamid needs urgent medical treatment. Your donation can save his life and help him recover from his illness.',
            'creator_name' => 'Dr. Rahman',
            'creator_email' => 'dr.rahman@example.com',
            'target_amount' => 50000.00,
            'current_amount' => 15000.00,
            'payment_type' => 'bkash',
            'slug' => 'help-for-hamid-abc123'
        ]);

        \App\Models\Campaign::create([
            'title' => 'Education For All',
            'description' => 'Supporting underprivileged children to get quality education and build a better future.',
            'creator_name' => 'Sarah Ahmed',
            'creator_email' => 'sarah@example.com',
            'target_amount' => 100000.00,
            'current_amount' => 25000.00,
            'payment_type' => 'nagad',
            'slug' => 'education-for-all-def456'
        ]);

        \App\Models\Campaign::create([
            'title' => 'Clean Water Project',
            'description' => 'Providing clean drinking water to rural communities in Bangladesh.',
            'creator_name' => 'Water Foundation',
            'creator_email' => 'info@waterfoundation.org',
            'target_amount' => 200000.00,
            'current_amount' => 75000.00,
            'payment_type' => 'bank',
            'slug' => 'clean-water-project-ghi789'
        ]);
    }
}
