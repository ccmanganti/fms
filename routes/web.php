<?php

use Illuminate\Support\Facades\Route;
use App\Exports\ECRSummaryExport;
use App\Exports\StudentOneExport;
use App\Exports\StudentTwoExport;
use App\Exports\StudentNineExport;
use App\Exports\StudentTenExport;
use App\Exports\StudentNinePdfExport;
use App\Exports\StudentTenPdfExport;
use App\Exports\StudentDiplomaExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectLoad;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSf9Job;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/{classid}/export-sf1-first', function ($classId) {
    $class = 'SF1'.' - '.Classes::where('id', $classId)->first()->name;
    $templatePath = public_path('sf1.xlsx');
    $filename = $class.'.xlsx';

    $export = new StudentOneExport($classId);
    return $export->download($templatePath)->setContentDisposition('attachment', $filename);
});

Route::get('/{classid}/export-sf1-second', function ($classId) {
    $class = 'SF1'.' - '.Classes::where('id', $classId)->first()->name;
    $templatePath = public_path('sf1.xlsx');
    $filename = $class.'.xlsx';

    $export = new StudentTwoExport($classId);
    return $export->download($templatePath)->setContentDisposition('attachment', $filename);
});

Route::get('/{studentid}/export-sf9', function ($studentId) {
    $student = Student::where('id', $studentId)->first();
    $studentFileName = 'SF9'.' - '.$student->lname.', '.$student->fname;
    $templatePath = public_path('sf9.xlsx');
    $filename = $studentFileName.'.xlsx';
    $export = new StudentNineExport($studentId);
    return $export->download($templatePath)->setContentDisposition('attachment', $filename);
});

Route::get('/{studentid}/export-sf9-pdf', function ($studentId) {
    $export = new StudentNinePdfExport($studentId);
    return $export->generatePdf();
});

Route::get('/{studentid}/export-sf10', function ($studentId) {
    $student = Student::where('id', $studentId)->first();
    $studentFileName = 'SF10'.' - '.$student->lname.', '.$student->fname;
    $templatePath = public_path('sf10.xlsx');
    $filename = $studentFileName.'.xlsx';
    $export = new StudentTenExport($studentId);
    return $export->download($templatePath)->setContentDisposition('attachment', $filename);
});

Route::get('/{studentid}/export-sf10-pdf', function ($studentId) {
    $export = new StudentTenPdfExport($studentId);
    return $export->generatePdf();
});

Route::get('/{studentid}/export-diploma-pdf', function ($studentId) {
    $export = new StudentDiplomaExport($studentId);
    return $export->generatePdf();
});

Route::get('/e-class-records/{subid}/export', function ($subId) {
    $subject = Subject::where('id', SubjectLoad::where('id', $subId)->first()->subject_id)->first()->subject_name;
    $className = Classes::where('id', SubjectLoad::where('id', $subId)->first()->class_id)->first()->name;
    $class = 'ECR'.' - '.$className.' | '.$subject;
    $templatePath = public_path('ecr-summary.xlsx');
    $filename = $class.'.xlsx';

    $export = new ECRSummaryExport($subId);
    return $export->download($templatePath)->setContentDisposition('attachment', $filename);
});

// Your route definition
// Route::get('/{studentid}/export-sf9', function ($studentId) {
//     $student = Student::where('id', $studentId)->first();
//     $studentFileName = 'SF9'.' - '.$student->lname.', '.$student->fname;
//     $templatePath = public_path('sf9.xlsx');
//     $filename = $studentFileName.'.pdf';
//     $studentId = $student->lrn;

//     // Dispatch the job to the queue
//     GenerateSf9Job::dispatch($studentId, $templatePath);

//     // Return a response indicating that the job has been dispatched
//     // return response()->json(['message' => 'PDF generation has been dispatched to the queue.']);
//     return;
// });

// Route to check the job status and trigger download
Route::get('/{studentid}/download-pdf', function ($studentId) {
    // Check if the download link is available in the session
    if (session()->has('pdf_download_link')) {
        $downloadLink = session('pdf_download_link');
        session()->forget('pdf_download_link'); // Remove the session variable
        return response()->json(['download_link' => $downloadLink]);
    } else {
        return response()->json(['message' => 'PDF generation is still in progress.']);
    }
});


// Route::get('/{studentid}/export-sf10', function ($studentId) {
//     $student = 'SF1'.' - '.Student::where('id', $student)->first()->lname.Student::where('id', $student)->first()->fname;
//     $templatePath = public_path('sf10.xlsx');
//     $filename = $student.'.xlsx';

//     $export = new StudentTenExport($studentId);
//     return $export->download($templatePath)->setContentDisposition('attachment', $filename);
// });


Route::get('/artisan/resetresources', function () {
    Artisan::call('migrate:refresh --path=/database/migrations/2023_07_06_032729_create_classes_table.php');
    Artisan::call('migrate:refresh --path=/database/migrations/2023_07_08_011001_create_subject_loads_table.php');
    Artisan::call('migrate:refresh --path=/database/migrations/2023_07_13_041015_create_e_class_records_table.php');
    Artisan::call('migrate:refresh --path=/database/migrations/2023_08_08_070539_create_student_of_classes_table.php');
});