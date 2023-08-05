<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Philmuni;
use File;

class PhilmuniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Philmuni::truncate();
  
        $json = File::get("database/data/refcitymun.json");
        $municipality = json_decode($json);
  
        foreach ($municipality as $key => $value) {
            Philmuni::create([
                "id" => $value->id,
                "psgcCode" => $value->psgcCode,
                "citymunDesc" => strtoupper($value->citymunDesc),
                "regDesc" => $value->regDesc,
                "provCode" => $value->provCode,
                "provCode" => $value->provCode,
                "citymunCode" => $value->citymunCode,
            ]);
        }
    }
}
