<?php

namespace App\Http\Controllers\Ministry;

use App\Http\Controllers\Controller;
use App\Models\HistoryTransfer;
use App\Models\User;
use App\Models\UserDetail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTransferController extends Controller
{



    public function release_user(Request $request)
    {
        DB::beginTransaction(); // Start the transaction

        try {
            // Custom validation with messages
            $validator = Validator::make($request->all(), [
                'staff_id' => 'required|exists:users,staff_id',
                'remarks' => 'nullable',
                'reason' => 'nullable',
                'recomanded_ministry' => 'nullable',
            ], [
                'staff_id.required' => 'The staff ID is required.',
                'staff_id.exists' => 'The provided staff ID does not exist in our records.',
            ]);

            // Check for validation failure
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Retrieve the base URL and token from the configuration
            $baseUrl = config('services.ministry_api.base_url');
            $token = config('services.ministry_api.token');
            $ministry_id = config('services.ministry_api.ministry_id');

            // Include ministry_id in the request data
            $requestData = $validator->validated();
            $requestData['ministry_id'] = $ministry_id;

            // Check if user is already released
            $updatedUser = User::where('staff_id', $requestData['staff_id'])->first();
            if ($updatedUser->active_status === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already released',
                ], 422);
            }

            // Call the external API before inserting into the database
            $client = new Client();
            $response = $client->request('POST', $baseUrl . '/v1/user/callback/release', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            // Parse the response body as JSON
            $data = json_decode($response->getBody(), true);
            $statusCode = $response->getStatusCode();

            // If the API response is not 200, rollback the transaction and return error
            if ($statusCode !== 200) {
                DB::rollBack(); // Rollback any DB changes
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to release user from the external API',
                    'api_response' => $data
                ], $statusCode);
            }

            // Update user active status
            $updatedUser->active_status = 0;
            $updatedUser->save();

            // Insert into history_transfer table
            HistoryTransfer::create([
                'staff_id' => $requestData['staff_id'],
                'reason' => $requestData['reason'] ?? null,
                'remarks' => $requestData['remarks'] ?? null,
                'ministry_id' => $requestData['recomanded_ministry'] ?? null,
            ]);

            DB::commit(); // Commit the transaction since everything succeeded

            return response()->json([
                "message" => "User released successfully and history recorded."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback all changes if any error occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'line' => $e->getLine(),
                'error' => $e->getMessage()
            ], 403);
        }
    }
    public function assinde_user(Request $request)
    {
        DB::beginTransaction(); // Start the transaction

        try {
            // Custom validation with messages
            $validator = Validator::make($request->all(), [
                'staff_id' => 'required',
                'ippis_no' => 'required',
            ], [
                'staff_id.required' => 'The staff ID is required.',
                'staff_id.exists' => 'The provided staff ID does not exist in our records.',
                'ippis_no.required' => 'The ippis no is required.',
                'ippis_no.exists' => 'The provided ippis no does not exist in our records.',

            ]);

            // dd($request->all());

            // Check for validation failure
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Retrieve the base URL and token from the configuration
            $baseUrl = config('services.ministry_api.base_url');
            $token = config('services.ministry_api.token');
            $ministry_id = config('services.ministry_api.ministry_id');

            // Include ministry_id in the request data
            $requestData = $validator->validated();
            $requestData['ministry_id'] = $ministry_id;

            // Check if user is already released
            $updatedUser = User::where('staff_id', $requestData['staff_id'])->first();
            if (isset($updatedUser->active_status) && $updatedUser->active_status === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already assinde',
                ], 422);
            }

            // Call the external API before inserting into the database
            $client = new Client();
            $response = $client->request('POST', $baseUrl . '/v1/user/callback/assign', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'json' => $requestData,
                'http_errors' => false, // Prevents exceptions on non-200 responses
            ]);
            $data = json_decode($response->getBody(), true);
            // Parse the response body as JSON
            // dd($data);
            $statusCode = $response->getStatusCode();
            // If the API response is not 200, rollback the transaction and return error
            // dd($statusCode);
            if ($statusCode === 200) {


                if ($updatedUser) {
                    // Update user active status
                    $updatedUser->active_status = 1;
                    $updatedUser->password = Hash::make($request->password);
                    $updatedUser->save();
                } else {
                    // need to insert here the info

                    $user = User::create([
                        'ippis_no' => $data['user']['ippis_no'],
                        'staff_id' => $data['user']['staff_id'],
                        'active_status' => 1,
                        'F_name' => $data['user']['F_name'],
                        'M_name' => $data['user']['M_name'],
                        'L_name' => $data['user']['L_name'],
                        'email' => $data['user']['email'],
                        'phone' => $data['user']['phone'],
                        'password' => Hash::make(rand(5)),
                        'designation' => $data['user']['designation'],
                        'cadre' => $data['user']['cadre']
                    ]);


                    $userDetails = UserDetail::create([
                        'staff_id' => $data['user']['user_details']['staff_id'],
                        'gender' => $data['user']['user_details']['gender'],
                        'designation' => $data['user']['user_details']['designation'],
                        'cadre' => $data['user']['user_details']['cadre'],
                        'date_of_current_posting' => $data['user']['user_details']['date_of_current_posting'],
                        'date_of_MDA_posting' => $data['user']['user_details']['date_of_MDA_posting'],
                        'date_of_last_promotion' => $data['user']['user_details']['date_of_last_promotion'],
                        'job_title' => $data['user']['user_details']['job_title'],
                        'grade_level' => $data['user']['user_details']['grade_level'],
                        'recovery_email' => $data['user']['user_details']['recovery_email'],
                        'created_by' => $data['user']['user_details']['created_by'],
                        'type' => "user",
                    ]);

                    return response(['message' => "user created & assigned succesfully"]);
                }
            } else {
                DB::rollBack(); // Rollback any DB changes
                return response()->json([
                    'status' => 'error',
                    'message' => $data['message'],
                ], $statusCode);
            }



            // // Insert into history_transfer table
            // HistoryTransfer::create([
            //     'staff_id' => $requestData['staff_id'],
            //     'reason' => $requestData['reason'] ?? null,
            //     'remarks' => $requestData['remarks'] ?? null,
            //     'ministry_id' => $requestData['recomanded_ministry'] ?? null,
            // ]);

            DB::commit(); // Commit the transaction since everything succeeded

            return response()->json([
                "message" => "User Assigned Successfully On This Ministry."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback all changes if any error occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'line' => $e->getLine(),
                'error' => $e->getMessage()
            ], 403);
        }
    }


    public function release_user_show(Request $request)
    {

        try {


            // Retrieve the base URL and token from the configuration
            $baseUrl = config('services.ministry_api.base_url');
            $token = config('services.ministry_api.token');
            $ministry_id = config('services.ministry_api.ministry_id');

            // Include ministry_id in the request data

            $requestData['ministry_id'] = $ministry_id;

            // Call the external API before inserting into the database
            $client = new Client();
            $response = $client->request('get', $baseUrl . '/v1/user/release', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],

            ]);

            // Parse the response body as JSON
            $data = json_decode($response->getBody(), true);
            $statusCode = $response->getStatusCode();

            return response()->json([
                "users" => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'line' => $e->getLine(),
                'error' => $e->getMessage()
            ], 403);
        }
    }
}
