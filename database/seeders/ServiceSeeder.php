<?php

namespace Database\Seeders;

use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Dental Cleaning', 'category' => ServiceCategory::Preventive, 'description' => 'Professional scaling and polishing to remove plaque and tartar.', 'duration_minutes' => 45, 'price' => 800],
            ['name' => 'Tooth Extraction', 'category' => ServiceCategory::Surgical, 'description' => 'Simple or surgical removal of a damaged or impacted tooth.', 'duration_minutes' => 30, 'price' => 1200],
            ['name' => 'Composite Filling', 'category' => ServiceCategory::Restorative, 'description' => 'Tooth-coloured resin restoration for cavities.', 'duration_minutes' => 45, 'price' => 1500],
            ['name' => 'Root Canal Treatment', 'category' => ServiceCategory::Restorative, 'description' => 'Complete cleaning, shaping and sealing of infected root canals.', 'duration_minutes' => 90, 'price' => 5000],
            ['name' => 'Dental Crown', 'category' => ServiceCategory::Restorative, 'description' => 'Porcelain or zirconia cap that restores a broken or weakened tooth.', 'duration_minutes' => 60, 'price' => 7500],
            ['name' => 'Teeth Whitening', 'category' => ServiceCategory::Cosmetic, 'description' => 'In-office bleaching for a noticeably brighter smile.', 'duration_minutes' => 60, 'price' => 4500],
            ['name' => 'Dental Implant', 'category' => ServiceCategory::Surgical, 'description' => 'Titanium post with crown — a permanent replacement for a missing tooth.', 'duration_minutes' => 120, 'price' => 35000],
            ['name' => 'Orthodontic Braces', 'category' => ServiceCategory::Orthodontic, 'description' => 'Metal or ceramic braces to align teeth and correct your bite.', 'duration_minutes' => 60, 'price' => 30000],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service + ['is_active' => true]);
        }
    }
}
