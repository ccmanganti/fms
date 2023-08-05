<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Philbrgy;
use File;

class PhilbrgySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Philbrgy::truncate();
  
        $json = File::get("database/data/refbrgy.json");
        $barangay = json_decode($json);
  
        foreach ($barangay as $key => $value) {
            Philbrgy::create([
                "id" => $value->id,
                "brgyCode" => $value->brgyCode,
                "brgyDesc" => strtoupper($value->brgyDesc),
                "regCode" => $value->regCode,
                "provCode" => $value->provCode,
                "citymunCode" => $value->citymunCode,
            ]);
        }
    }
}
