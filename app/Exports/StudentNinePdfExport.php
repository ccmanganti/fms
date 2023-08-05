<?php

namespace App\Exports;

use App\Models\Student;
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
        $this->studentId = Student::where('id', $studentId)->first()->lrn;
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

        $studentGradeCore1 = [];
        $studentGradeCore2 = [];

        $studentGradeApplied1 = [];
        $studentGradeApplied2 = [];
        
        $subjectNameCore1 = [];
        $subjectNameCore2 = [];
        
        $subjectNameApplied1 = [];
        $subjectNameApplied2 = [];


        foreach ($subjectSem1 as $subject) {
            $studentGrade = collect($subject->student_grades)->where('name', $this->studentId)->first();
            $subjectType = Subject::where('id', $subject->subject_id)->first()->subject_type;

            if($subjectType == 'Core'){
                $studentGradeCore1[] = $studentGrade;
                $subjectNameCore1[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
            } else{
                $studentGradeApplied1[] = $studentGrade;
                $subjectNameApplied1[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
            }
            
            // $sN1[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
        }
        foreach ($subjectSem2 as $subject) {
            $studentGrade = collect($subject->student_grades)->where('name', $this->studentId)->first();
            $subjectType = Subject::where('id', $subject->subject_id)->first()->subject_type;
            
            if($subjectType == 'Core'){
                $studentGradeCore2[] = $studentGrade;
                $subjectNameCore2[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
            } else{
                $studentGradeApplied2[] = $studentGrade;
                $subjectNameApplied2[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
            }
        }

        // dd($subjectLoads[0]);
        // dd((collect($subjectLoads[0]->student_grades)->where('name', $this->studentId)->first()));

        $this->class = $class;
        $this->studentInfo = Student::where('lrn', $this->studentId)->first();

        $this->studentGradeCore1 = $studentGradeCore1;
        $this->subjectNameCore1 = $subjectNameCore1;
        
        $this->studentGradeCore2 = $studentGradeCore2;
        $this->subjectNameCore2 = $subjectNameCore2;

        $this->studentGradeApplied1 = $studentGradeApplied1;
        $this->subjectNameApplied1 = $subjectNameApplied1;

        $this->studentGradeApplied2 = $studentGradeApplied2;
        $this->subjectNameApplied2 = $subjectNameApplied2;

        // dd($this->subjectNameCore1);

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

        // Subjects 1st Sem: Core
        foreach ($this->subjectNameCore1 as $index => $name) {
            $newIndex = $index+1;
            $fieldMappings["core{$newIndex}"] = $name;
        }
        // Subjects 1st Sem: Applied
        foreach ($this->subjectNameCore2 as $index => $name) {
            $newIndex = $index+1;
            $fieldMappings["sem2core{$newIndex}"] = $name;
        }
        
        // Subjects 2nd Sem: Core
        foreach ($this->subjectNameApplied1 as $index => $name) {
            $newIndex = $index+1;
            $fieldMappings["Ap{$newIndex}"] = $name;
        }
        // Subjects 2nd Sem: Applied
        foreach ($this->subjectNameApplied2 as $index => $name) {
            $newIndex = $index+1;
            $fieldMappings["sem2ap{$newIndex}"] = $name;
        }

// SUBJECT GRADES ==========================================================


        // Grades 1st Sem: Core
        foreach ($this->studentGradeCore1 as $index => $grade) {
            $newIndex = $index+1;
            $fieldMappings["core{$newIndex}G1"] = $grade['1st_quarter_grade'];
            $fieldMappings["core{$newIndex}G2"] = $grade['2nd_quarter_grade'];
            $fieldMappings["core{$newIndex}G3"] = $grade['average'];
        }
        // Grades 1st Sem: Applied
        foreach ($this->studentGradeApplied1 as $index => $grade) {
            $newIndex = $index+1;
            $fieldMappings["Ap{$newIndex}G1"] = $grade['1st_quarter_grade'];
            $fieldMappings["Ap{$newIndex}G2"] = $grade['2nd_quarter_grade'];
            $fieldMappings["Ap{$newIndex}G3"] = $grade['average'];
        }

        // Grades 2nd Sem: Core
        foreach ($this->studentGradeCore2 as $index => $grade) {
            $newIndex = $index+1;
            $fieldMappings["sem2core{$newIndex}G1"] = $grade['1st_quarter_grade'];
            $fieldMappings["sem2core{$newIndex}G2"] = $grade['2nd_quarter_grade'];
            $fieldMappings["sem2core{$newIndex}G3"] = $grade['average'];
        }
        // Grades 2nd Sem: Applied
        foreach ($this->studentGradeApplied2 as $index => $grade) {
            $newIndex = $index+1;
            $fieldMappings["sem2Ap{$newIndex}G1"] = $grade['1st_quarter_grade'];
            $fieldMappings["sem2Ap{$newIndex}G2"] = $grade['2nd_quarter_grade'];
            $fieldMappings["sem2Ap{$newIndex}G3"] = $grade['average'];
        }

        return $fieldMappings;
    }
}
