<?php
namespace App\Helpers;

use App\Models\User;

class StaffHelper
{
    /**
     * Generate a unique staff ID in the format STAFF + 5-6 digit number
     */
    public static function generateUniqueStaffID()
    {
        do {
            $staff_id = 'STAFF' . mt_rand(10000, 999999); // Generates a random 5-6 digit number
        } while (User::where('staff_id', $staff_id)->exists());

        return $staff_id;
    }
}
