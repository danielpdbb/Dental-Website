<?php

return [

    /*
    | Clinic opening hours used by booking validation and predictive scheduling.
    | open_days: ISO weekday numbers (1 = Monday … 7 = Sunday).
    */
    'open_days' => [1, 2, 3, 4, 5, 6], // Mon–Sat
    'open_time' => '09:00',
    'close_time' => '17:00',

    // Granularity of bookable slots, in minutes.
    'slot_minutes' => 30,

];
