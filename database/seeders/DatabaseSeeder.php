<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        DB::table("users")->insert([
            "username" => "nghĩalc123",
            "name" => "La Trung Nghĩa",
            "email" => "kurobakarma@gmail.com",
            "street_address" => "Lao Cai",
            "phone" => "1951941056",
            "password" => Hash::make("Thideptrai@12"),
            "role" => "customer"
        ]);

        DB::table("users")->insert([
            "username" => "teolc123",
            "name" => "La Trung Nghẽo",
            "email" => "ad@gmail.com",
            "street_address" => "TQB",
            "phone" => "0709292929",
            "password" => Hash::make("123456789"),
            "role" => "admin"
        ]);


        DB::table("sizes")->insert([
            "sizeName" => "S",
        ]);
        DB::table("sizes")->insert([
            "sizeName" => "M",
        ]);
        DB::table("sizes")->insert([
            "sizeName" => "L",
        ]);
        DB::table("sizes")->insert([
            "sizeName" => "XL",
        ]);
        DB::table("sizes")->insert([
            "sizeName" => "XXL",
        ]);
        DB::table("sizes")->insert([
            "sizeName" => "XXXL",
        ]);


        DB::table("colors")->insert([
            "colorName" => "Đỏ",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Xanh nước",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Hồng",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Tím ",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Đen",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Trắng",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Xanh lá",
        ]);
        DB::table("colors")->insert([
            "colorName" => "Cam",
        ]);


    }
}
