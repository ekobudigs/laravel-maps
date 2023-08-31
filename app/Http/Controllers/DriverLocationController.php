<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\DriverLocationUpdated;

class DriverLocationController extends Controller
{
    public function updateLocation(Request $request)
    {
        $driver = $request->input('driver');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        event(new DriverLocationUpdated($driver, $latitude, $longitude));

        return response()->json(['message' => 'Location updated']);
    }
}
