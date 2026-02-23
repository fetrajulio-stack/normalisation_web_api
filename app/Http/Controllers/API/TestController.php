<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function testPg()
    {
        $users = DB::connection('pgsql_auth')
            ->table('personnel')
            ->limit(1)
            ->get();

        return $users;
    }
}
