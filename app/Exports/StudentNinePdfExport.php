<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\SubjectLoad;
use App\Models\Subject;
use App\Models\User;
use setasign\Fpdi\Fpdi;
use setasign\FpdiFpdf\FpdiFpdf;
use setasign\Fpdi\Fpdf\FpdfTpl;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as PdfDompdf;
use Dompdf\Dompdf as DompdfLib;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use GuzzleHttp\Client;
use Ilovepdf\Ilovepdf;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\PdfReader;
use mikehaertl\pdftk\Pdf;

class StudentNinePdfExport implements ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $studentId;
    protected $class;
    protected $subjectLoads;

    
    public function __construct($studentId)
    {
        $this->studentId = StudentOfClass::where('id', $studentId)->where('school_year_id', SchoolYear::where('current', true)->first()->id)->first()->lrn;
        $class = Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->whereJsonContains('students', $this->studentId)->first();
        $subjectSem1 = SubjectLoad::where('class_id', $class->id)->where('semester', 1)->get();
        $subjectSem2 = SubjectLoad::where('class_id', $class->id)->where('semester', 2)->get();

        if($class->track_course == 'ABM'){
            $this->course = 'Academic Track - Accountancy, Business and Management (ABM)';
        } else if($class->track_course == 'STEM'){
            $this->course = 'Academic Track - Science, Technology, Engineering, and Mathematics (STEM)';
        } else if($class->track_course == 'HUMSS'){
            $this->course = 'Academic Track -Humanities and Social Sciences (HUMSS)';
        } else if($class->track_course == 'GAS'){
            $this->course = 'Academic Track - General Academic Strand (GAS)';
        } else if($class->track_course == 'ADT'){
            $this->course = 'Arts and Design Track';
        } else if($class->track_course == 'ST'){
            $this->course = 'Sports Track';
        } else if($class->track_course == 'TVL - AFA'){
            $this->course = 'TVL Track -  Agricultural-Fishery Arts (AFA)';
        } else if($class->track_course == 'TVL - HE'){
            $this->course = 'TVL Track -  Home Economics (HE)';
        } else if($class->track_course == 'TVL - IA'){
            $this->course = 'TVL Track -  Industrial Arts (IA)';
        } else if($class->track_course == 'TVL - ICT'){
            $this->course = 'TVL Track -  Information and Communications Technology (ICT)';
        }

        

        $this->class = $class;
        $this->studentInfo = StudentOfClass::where('lrn', $this->studentId)->where('name', $this->class->name)->first();

        $this->subjectSem1 = $subjectSem1;
        $this->subjectSem2 = $subjectSem2;

    }

    public function generatePdf()
    {
        // Load the PDF template
        $pdf = new Pdf(public_path('\sf9-form.pdf'), [
            'command' => 'C:\Program Files (x86)\PDFtk Server\bin\pdftk.exe',
            'useExec' => true,
        ]);
        
        $data = $this->populateFormFields();

        // dd($data);
        
        $result = $pdf->fillForm($data)->flatten()->saveAs(public_path('\SF9 - '.$this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname.'.pdf'));

        // Send the file download response
        // return response()->download(public_path('\filled.pdf'), 'filled.pdf')->deleteFileAfterSend();
        return response()->file(public_path('\SF9 - '.$this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname.'.pdf'), [
            'Content-Disposition' => 'inline; filename="filled.pdf"'
        ])->deleteFileAfterSend();
    }

    // Rest of your class methods here...

    protected function populateFormFields()
    {

        $fieldMappings = [];

        // PERSONAL INFORMATION
        $fieldMappings['Name'] = $this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname;
        $fieldMappings['Age'] = $this->studentInfo->age;
        $fieldMappings['Sex'] = $this->studentInfo->gender;
        $fieldMappings['Grade'] = $this->class->grade_level;
        $fieldMappings['Section'] = $this->class->section;
        $fieldMappings['School Year'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1);
        $fieldMappings['LRN'] = $this->studentInfo->lrn;
        $fieldMappings['TrackCourse'] = $this->course;
        $fieldMappings['Teacher'] = User::where('id', $this->class->adviser_id)->first()->name;


// SUBJECT NAMES ==========================================================

        $sem1List = [];
        $sem2List = [];

        foreach ($this->subjectSem1 as $index => $subject) {
            $sem1List[$index]['type'] = Subject::where('id', $subject->subject_id)->first()->subject_type;
            $sem1List[$index]['name'] = Subject::where('id', $subject->subject_id)->first()->subject_name;
            $sem1List[$index]['grade'] = collect($subject->student_grades)->where('name', $this->studentId)->first();
        }

        foreach ($this->subjectSem2 as $index => $subject) {
            $sem2List[$index]['type'] = Subject::where('id', $subject->subject_id)->first()->subject_type;
            $sem2List[$index]['name'] = Subject::where('id', $subject->subject_id)->first()->subject_name;
            $sem2List[$index]['grade'] = collect($subject->student_grades)->where('name', $this->studentId)->first();
        }


        // 1st Semester
        $averages = [];
        // Core Subjects
        $newIndex = 1;
        foreach ($sem1List as $index => $subject) {
            if($subject["type"] == "Core"){
                $fieldMappings["core{$newIndex}"] = $subject["name"];
                $fieldMappings["core{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["core{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["core{$newIndex}G3"] = $subject["grade"]["average"];
                $newIndex++;
                $averages[] = $subject["grade"]["average"] ?? 0;

            }
        }
        // Applied Subjects
        $newIndex = 1;
        foreach ($sem1List as $index => $subject) {
            if($subject["type"] != "Core"){
                $fieldMappings["Ap{$newIndex}"] = $subject["name"];
                $fieldMappings["Ap{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["Ap{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["Ap{$newIndex}G3"] = $subject["grade"]["average"];
                $newIndex++;
                $averages[] = $subject["grade"]["average"] ?? 0;

            }
        }

        if($averages != []){
            $fieldMappings['GA1'] = array_sum($averages) / count($averages);
        }

        // 2nd Semester
        $averages = [];
        // Core Subjects
        $newIndex = 1;
        foreach ($sem2List as $index => $subject) {
            if($subject["type"] == "Core"){
                $fieldMappings["sem2core{$newIndex}"] = $subject["name"];
                $fieldMappings["sem2core{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sem2core{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sem2core{$newIndex}G3"] = $subject["grade"]["average"];
                $newIndex++;
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
        }
        // Applied Subjects
        // dd($sem2List);
        $newIndex = 1;
        foreach ($sem2List as $index => $subject) {
            if($subject["type"] != "Core"){
                $fieldMappings["sem2ap{$newIndex}"] = $subject["name"];
                $fieldMappings["sem2ap{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sem2ap{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sem2ap{$newIndex}G3"] = $subject["grade"]["average"];
                $newIndex++;
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
        }

        if($averages != []){
            $fieldMappings['GA2'] = array_sum($averages) / count($averages);
        }

        return $fieldMappings;
    }
}
