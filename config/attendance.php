<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Business HQ Coordinates (Geofence Center)
    |--------------------------------------------------------------------------
    | Update these to match the actual GPS coordinates of your office/business.
    */
    'hq_latitude'  => env('HQ_LATITUDE',  -3.3520992),
    'hq_longitude' => env('HQ_LONGITUDE', 37.3375088),

    /*
    |--------------------------------------------------------------------------
    | HQ display name (map marker, labels, messages)
    |--------------------------------------------------------------------------
    */
    'hq_name' => env('HQ_NAME', 'EmCa HQ'),

    /*
    |--------------------------------------------------------------------------
    | Geofence Radius (metres)
    |--------------------------------------------------------------------------
    | Staff must be within this radius to sign in or out.
    | Default is 70 metres.
    */
    'geofence_radius' => env('GEOFENCE_RADIUS', 70),
];
