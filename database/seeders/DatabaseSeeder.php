<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // $user = User::factory()->create([
        //     'name' => 'Admin',
        //     'email' => 'admin@gmail.com',
        //     'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        //     'remember_token' => Str::random(10),

        // ]);
        // $role = Role::create(['name' => 'Superadmin']);
        // Role::create(['name' => 'Principal']);
        // Role::create(['name' => 'Adviser']);
        // Role::create(['name' => 'Subject Teacher']);
        // $user->assignRole($role);

        // Create the initial admin user
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);

        // Create roles
        $superadminRole = Role::create(['name' => 'Superadmin']);
        $principalRole = Role::create(['name' => 'Principal']);
        $adviserRole = Role::create(['name' => 'Adviser']);
        $subjectTeacherRole = Role::create(['name' => 'Subject Teacher']);

        // Assign roles to the admin user
        $user->assignRole($superadminRole);

        $adviserUser = User::factory()->create([
            'name' => 'Adviser User',
            'email' => 'adviser@example.com',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);
        $adviserUser->assignRole($adviserRole);

        $teacherUser = User::factory()->create([
            'name' => 'Subject Teacher User',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);
        $teacherUser->assignRole($subjectTeacherRole);

        // Create more users and assign roles
        $principalUser = User::factory()->create([
            'name' => 'Principal User',
            'email' => 'principal@example.com',
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);
        $principalUser->assignRole($principalRole);
    }
}
