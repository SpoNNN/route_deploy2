<?php

namespace App\Http\Controllers;

class TestController extends Controller
{
    public function test()
    {
        return ['success' => true, 'message' => 'Test OK'];
    }
}