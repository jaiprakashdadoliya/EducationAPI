<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
	        [
	            'user_reference' => '1',
	            'name' => 'Chetan',
	            'email' => 'wagh.chetan@fxbytes.com',
	            'mobile' => '9993555498',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'parent',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ],
	        [
	            'user_reference' => '2',
	            'name' => 'Kamal',
	            'email' => 'asawara.kamal@fxbytes.com',
	            'mobile' => '9907250592',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'parent',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ],
	        [
	            'user_reference' => '3',
	            'name' => 'Pramod',
	            'email' => 'batodiya.pramod@fxbytes.com',
	            'mobile' => '8269981958',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'parent',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ],
	        [
	            'user_reference' => '4',
	            'name' => 'Chandrakant',
	            'email' => 'sharma.chandrakant@fxbytes.com',
	            'mobile' => '9009603062',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'parent',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ],
	        [
	            'user_reference' => '5',
	            'name' => 'Devesh',
	            'email' => 'tiwari.devesh@fxbytes.com',
	            'mobile' => '8269104524',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'driver',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ],
	        [
	            'user_reference' => '6',
	            'name' => 'Admin',
	            'email' => 'admin@admin.com',
	            'mobile' => '9876543210',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'admin',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ],
	        [
	            'user_reference' => '7',
	            'name' => 'Toshik',
	            'email' => 'parihar.toshik@fxbytes.com',
	            'mobile' => '8269982684',
	            'password' => bcrypt('Download@123'),
	            'user_type' => 'parent',
	            'school_id' => 1,
	            'created_by' => 1,
	            'updated_by' => 1,
	        ]
        ]);
    }
}
