<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Philprovince;
use File;

class PhilprovinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Philprovince::truncate();
  
        $json = File::get("database/data/refprovince.json");
        $province = json_decode($json);
  
        foreach ($province as $key => $value) {
            Philprovince::create([
                "id" => $value->id,
                "psgcCode" => $value->psgcCode,
                "provDesc" => strtoupper($value->provDesc),
                "regCode" => $value->regCode,
                "provCode" => $value->provCode,
            ]);
        }
    }
}
