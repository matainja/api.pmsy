<?php

namespace App\Http\Controllers\Competencies;

use App\Http\Controllers\Controller;
use App\Models\Competencies;
use App\Models\CompetenciesDetails;
use Hamcrest\Arrays\IsArray;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffCompetenciesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //

        try {
            // Validate the request data
            $validatedData = $request->validate([
                'staff_id' => 'required|string|max:20',
                'dept_id' => 'required|string',
                'year' => 'required|numeric',
                'quarter' => 'required|numeric'
            ]);

            $data = Competencies::with('competenciesDetails')
            ->allCheck($validatedData['staff_id'],$validatedData['dept_id'],$validatedData['year'],$validatedData['quarter']);

            if (count($data) === 0) {
                return response()->json([
                    "message" => "There is no data"
                ], 404);
            }
            
            return response()->json([
                "competenciesDetails"=>$data
            ]);

        } catch (ValidationException $e) {
            // Return a JSON response if validation fails
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->all();
        // dd($data);
        // return response()->json($request->all());   

        $competencies = $request->Competencies;

        if (empty($competencies)) {
            # code...
            return response()->json([
                "response" => "enter Competencies to run the api"
            ], 404);
        }

        foreach ($competencies as $competenciey)
         {
            // dd($competenciey);
            $answer = Competencies::updateOrCreate(
                [
                    'competencies' => $competenciey['name'],
                    'staff_id' => $request->staff_id,
                    'dept_id' => $request->dept_id,
                    'year' => $request->year,
                    'quarter' => $request->quarter,
                ],[])->id;

            foreach ($competenciey['details'] as $description) {
                # code...
                // dd($description);
                $result = CompetenciesDetails::updateOrCreate([
                     "competencies_id" => $answer,
                     "title" => $description['title'],
                ],[                  
                    "describe_expectations" => $description['Describe_Expectations'],
                    "min_score" => $description['min_score'],
                    "max_score" => $description['max_score'],
                ]);
            }
            // dd($answer);
        }

        //need to do the second part 
        

        return response()->json([
            "response" => "data updated succesfully"
        ]);
        // dd($competencies);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
