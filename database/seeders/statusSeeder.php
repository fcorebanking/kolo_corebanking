<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\status;


class statusSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $default = [
      ['name' => 'Active', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Disabled', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Complete', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Restricted', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Fraud Blocked', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Wrong Password Blocked', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Pending', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Dom Account', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Deleted', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Setup fee Payment', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Company Agreement', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Permanent Delete', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'App Delete Request', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Suspended by Subscription', 'created_at' => now(), 'updated_at' => now()],
    ];
    for($i=0;$i<count($default);$i++){
        $name = $default[$i]['name'];
        $check_status = status::where('name',$name)->count();
        if($check_status==0){
            DB::table('statuses')->insert($default[$i]);
        }
    }
  }
}
