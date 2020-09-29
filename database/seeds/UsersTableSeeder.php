<?php

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users  = [
            [
                'role_id' => '1',
                'name' => 'MD.Admin',
                'username' => 'admin',
                'email' => 'admin@blog.com',
                'password' => Hash::make('rootadmin'),
                'created_at'=> Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'role_id' => '2',
                'name' => 'MD.Author',
                'username' => 'author',
                'email' => 'author@blog.com',
                'password' => Hash::make('rootauthor'),
                'created_at'=> Carbon::now()->format('Y-m-d H:i:s'),
            ]
        ];
            $newUser = User::insert($users);
    }
}
