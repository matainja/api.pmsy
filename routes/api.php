<?php

use App\Http\Controllers\AdminControllers\AuthController as AdminControllersAuthController;
use App\Http\Controllers\AdminControllers\Department\DepartmentController;
use App\Http\Controllers\AdminControllers\StaffController;
use App\Http\Controllers\Department\UserDepartmentController;
use App\Http\Controllers\EmployeeSubTaskController;
use App\Http\Controllers\personalInfo\PersonalInfoController;
// use App\Http\Controllers\PlanningController;
// use App\Http\Controllers\Planning\PlanningController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Competencies\StaffCompetenciesController;
use App\Http\Controllers\Competencies\SupervisorCompetenciesController;
use App\Http\Controllers\Department\SupervisorDepartmentController;
use App\Http\Controllers\Ministry\MinistriesController;
use App\Http\Controllers\Ministry\UserTransferController;
use App\Http\Controllers\Planning\PlanningController;
use App\Http\Controllers\Planning\SupervisorPlanningController;
use App\Models\EmployeeSubTask;

Route::controller(AuthController::class)->group(function () {
  Route::post('login', 'login')->name('auth');
  Route::post('register', 'register');
  // Route::post('logout', 'logout');
  Route::post('refresh', 'refresh');
});

// admin login api route
Route::post('admin/login', [AdminControllersAuthController::class, 'login'])->name('adminlogin');

// Route::resource('planning', PlanningController::class);

// // Route::resource('employeetask',EmployeeSubTaskController::class);

// Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);

//signup for user 
Route::post('/signup', [StaffController::class, 'signUp']);

Route::middleware('auth:api')->group(function () {
  // Resource routes

  // Route::resource('employeetask', EmployeeSubTaskController::class);
  Route::resource('personalinfo', PersonalInfoController::class);
  Route::post('security', [PersonalInfoController::class, 'security'])->name('security');

  Route::resource('department', UserDepartmentController::class);


  Route::prefix('planning')->name('planning.')->group(function () {
    Route::resource('/', PlanningController::class);
    Route::get('employeetask', [PlanningController::class, 'employeeTask'])->name('employeeTask');
    Route::get('data', [PlanningController::class, 'data'])->name('data');
  });

  Route::prefix('competencies')->name('competencies')->group(function () {
    route::resource('/', StaffCompetenciesController::class);
  });

  Route::prefix('supervisor')->name('supervisor')->group(function () {
    Route::resource('/', SupervisorDepartmentController::class);
    Route::get('stafflist', [SupervisorDepartmentController::class, 'stafflist'])->name('stafflist');

    //planning or we can also call it form A controller for supervisor 
    Route::resource('planning', SupervisorPlanningController::class);

    Route::resource('competencies', SupervisorCompetenciesController::class);
  });

  // Route::middleware('role:admin')->group(function () {
  Route::prefix('admin')->name('admin')->group(function () {

    Route::resource('/', AdminControllersAuthController::class);

    Route::resource('staff', StaffController::class);

    // Route::put('')

    Route::resource('department', DepartmentController::class);

    Route::post('/department/{department}/assignstaff', [DepartmentController::class, 'assignStaff'])->name('department.assignstaff');

    Route::get('/department/{department}/assignedStaff', [DepartmentController::class, 'assignedStaff'])->name('department.assignedStaff');

    Route::post('/department/{department}/assignall', [DepartmentController::class, 'assignAll'])->name('department.assignall');

    Route::get('/department/{department}/alreadyassignedlist', [DepartmentController::class, 'alreadyAssignedList'])->name('department.alreadyAssignedList');

    Route::get('/department/{department}/team/getall', [DepartmentController::class, 'teamGetAll'])->name('department.teamGetAll');
  });
  // });


  // Logout route
  Route::post('/logout', [AuthController::class, 'logout']);

  Route::prefix('user')->group(function () {
    Route::get('release', [UserTransferController::class, 'release_user_show']);

    Route::post('release', [UserTransferController::class, 'release_user'])->name('release_user');
    Route::post('ministry/assign', [UserTransferController::class, 'assinde_user']);
  });
});

// Route::get('/test', function () {
//     dd(123);
// });

Route::get('/guzzle', [StaffController::class, 'getData']);

Route::get('/test', function () {
  return transferCheck('STAFF0000001');
});
