<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nom'       => 'Admin Jappo',
            'telephone' => '771234567',
            'password'  => bcrypt('secret123'),
            'role'      => 'administrateur',
            'actif'     => true,
        ]);
    
    {
        User::create([
            'nom'       => 'Admin THioro',
            'telephone' => '783936249',
            'password'  => bcrypt('secret123'),
            'role'      => 'administrateur',
            'actif'     => true,
        ]);
    }
    }  
}