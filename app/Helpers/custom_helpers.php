<?php

use App\Models\OverallAssessment;
use App\Models\User;

if (!function_exists('return_123')) {

    function transferCheck($staff_id)
    {

        $quarter = (int) ceil(date('n') / 3);
        $currentYear = (int) date('Y');

        $response = OverallAssessment::where('staff_id',$staff_id)
        ->where('year',$currentYear)->where('quater',$quarter)
        ->whereNotNull('sub_total_rating_expectations')
        ->whereNotNull('sub_total_rating_competencies')
        ->whereNotNull('sub_total_rating_operations')->exists();

        if ($response) {
            return 1;
        } else {
            return 0;
        }
       
    }
}



     function generateUniqueStaffID()
    {
        do {
            $staff_id = 'STAFF' . mt_rand(10000, 999999); // Generates a random 5 or 6 digit number
        } while (User::where('staff_id', $staff_id)->exists());

        return $staff_id;
    }


