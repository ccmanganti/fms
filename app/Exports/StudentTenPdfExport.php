<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\SubjectLoad;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as PdfDompdf;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use mikehaertl\pdftk\Pdf;

class StudentTenPdfExport implements ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $studentId;
    protected $class;
    protected $subjectLoads;
    protected $previousClass;
    
    public function __construct($studentId)
    {
        $this->studentId = StudentOfClass::where('id', $studentId)->where('school_year_id', SchoolYear::where('current', true)->first()->id)->first()->lrn;
        // $this->studentId = StudentOfClass::where('id', $studentId)->first()->lrn;
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
        // $this->studentInfo = StudentOfClass::where('lrn', $this->studentId)->first();

        $this->subjectSem1 = $subjectSem1;
        $this->subjectSem2 = $subjectSem2;

        if($this->class->grade_level == "12"){
            $previousSY = SchoolYear::where('sydate', (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate)-1)->first();
            if($previousSY){
                $classExist = Classes::where('school_year_id', $previousSY->id)->first();
            } else{
                $classExist = null;
            }
            
            if($classExist){
                $previousClass = Classes::where('school_year_id', $previousSY->id)->whereJsonContains('students', $this->studentId)->first();
                $previousSubjectSem1 = SubjectLoad::where('class_id', $previousClass->id)->where('semester', 1)->get();
                $previousSubjectSem2 = SubjectLoad::where('class_id', $previousClass->id)->where('semester', 2)->get();

                if($previousClass->track_course == 'ABM'){
                    $this->previousCourse = 'Academic Track - Accountancy, Business and Management (ABM)';
                } else if($previousClass->track_course == 'STEM'){
                    $this->previousCourse = 'Academic Track - Science, Technology, Engineering, and Mathematics (STEM)';
                } else if($previousClass->track_course == 'HUMSS'){
                    $this->previousCourse = 'Academic Track -Humanities and Social Sciences (HUMSS)';
                } else if($previousClass->track_course == 'GAS'){
                    $this->previousCourse = 'Academic Track - General Academic Strand (GAS)';
                } else if($previousClass->track_course == 'ADT'){
                    $this->previousCourse = 'Arts and Design Track';
                } else if($previousClass->track_course == 'ST'){
                    $this->previousCourse = 'Sports Track';
                } else if($previousClass->track_course == 'TVL - AFA'){
                    $this->previousCourse = 'TVL Track -  Agricultural-Fishery Arts (AFA)';
                } else if($previousClass->track_course == 'TVL - HE'){
                    $this->previousCourse = 'TVL Track -  Home Economics (HE)';
                } else if($previousClass->track_course == 'TVL - IA'){
                    $this->previousCourse = 'TVL Track -  Industrial Arts (IA)';
                } else if($previousClass->track_course == 'TVL - ICT'){
                    $this->previousCourse = 'TVL Track -  Information and Communications Technology (ICT)';
                }

                $this->previousClass = $previousClass;
                $this->previousSubjectSem1 = $previousSubjectSem1;
                $this->previousSubjectSem2 = $previousSubjectSem2;
            }
        }

    }

    public function generatePdf()
    {
        // Load the PDF template
        $pdf = new Pdf(public_path('\sf10-form.pdf'), [
            'command' => 'C:\Program Files (x86)\PDFtk Server\bin\pdftk.exe',
            'useExec' => true,
        ]);
        
        $data = $this->populateFormFields();

        // dd($data);
        
        $result = $pdf->fillForm($data)->flatten()->saveAs(public_path('\SF10 - '.$this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname.'.pdf'));

        // Send the file download response
        // return response()->download(public_path('\filled.pdf'), 'filled.pdf')->deleteFileAfterSend();
        return response()->file(public_path('\SF10 - '.$this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname.'.pdf'), [
            'Content-Disposition' => 'inline; filename="filled.pdf"'
        ])->deleteFileAfterSend();
    }

    protected function populateFormFields()
    {

        $fieldMappings = [];

// SUBJECT NAMES ==========================================================
        $sem1List = [];
        $sem2List = [];
        $sem3List = [];
        $sem4List = [];

        if($this->previousClass){
            // PERSONAL INFORMATION
            $fieldMappings['LName'] = $this->studentInfo->lname;
            $fieldMappings['FName'] = $this->studentInfo->fname;
            $fieldMappings['MName'] = $this->studentInfo->mname;
            $fieldMappings['Sex'] = $this->studentInfo->gender;
            $fieldMappings['LRN'] = $this->studentInfo->lrn;
            $fieldMappings['Birth'] = $this->studentInfo->date_of_birth;
            
            $fieldMappings['Grade3'] = $this->class->grade_level;
            $fieldMappings['Grade4'] = $this->class->grade_level;            
            $fieldMappings['Section3'] = $this->class->section;
            $fieldMappings['Section4'] = $this->class->section;
            $fieldMappings['School Year3'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1);
            $fieldMappings['School Year4'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1);
            $fieldMappings['TrackCourse3'] = $this->course;
            $fieldMappings['TrackCourse4'] = $this->course;
            $fieldMappings['Teacher2'] = User::where('id', $this->class->adviser_id)->first()->name;

            $fieldMappings['Grade1'] = $this->previousClass->grade_level;
            $fieldMappings['Grade2'] = $this->previousClass->grade_level;
            $fieldMappings['Section1'] = $this->previousClass->section;
            $fieldMappings['Section2'] = $this->previousClass->section;
            $fieldMappings['School Year1'] = (SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate+1);
            $fieldMappings['School Year2'] = (SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate+1);
            $fieldMappings['TrackCourse1'] = $this->previousCourse;
            $fieldMappings['TrackCourse2'] = $this->previousCourse;
            $fieldMappings['Teacher'] = User::where('id', $this->previousClass->adviser_id)->first()->name;

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

            foreach ($this->previousSubjectSem1 as $index => $subject) {
                $sem3List[$index]['type'] = Subject::where('id', $subject->subject_id)->first()->subject_type;
                $sem3List[$index]['name'] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                $sem3List[$index]['grade'] = collect($subject->student_grades)->where('name', $this->studentId)->first();
            }
            foreach ($this->previousSubjectSem2 as $index => $subject) {
                $sem4List[$index]['type'] = Subject::where('id', $subject->subject_id)->first()->subject_type;
                $sem4List[$index]['name'] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                $sem4List[$index]['grade'] = collect($subject->student_grades)->where('name', $this->studentId)->first();
            }

        } else{
            // PERSONAL INFORMATION
            $fieldMappings['LName'] = $this->studentInfo->lname;
            $fieldMappings['FName'] = $this->studentInfo->fname;
            $fieldMappings['MName'] = $this->studentInfo->mname;
            $fieldMappings['Sex'] = $this->studentInfo->gender;
            $fieldMappings['LRN'] = $this->studentInfo->lrn;
            $fieldMappings['Birth'] = $this->studentInfo->date_of_birth;
            $fieldMappings['TrackCourse1'] = $this->course;
            $fieldMappings['TrackCourse2'] = $this->course;
            $fieldMappings['Grade1'] = $this->class->grade_level;
            $fieldMappings['Grade2'] = $this->class->grade_level;
            $fieldMappings['School Year1'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1);
            $fieldMappings['School Year2'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1);
            $fieldMappings['Section1'] = $this->class->section;
            $fieldMappings['Section2'] = $this->class->section;
            $fieldMappings['Teacher'] = User::where('id', $this->class->adviser_id)->first()->name;
            
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
        }

        
        
        $typeOrder = [
            'Core' => 1,
            'Applied' => 2,
            'Specialized' => 3,
        ];
        
        if($this->previousClass){
            if($sem1List != []){
                usort($sem1List, function ($a, $b) use ($typeOrder) {
                    return $typeOrder[$a['type']] - $typeOrder[$b['type']];
                });
            } if($sem2List != []){
                usort($sem2List, function ($a, $b) use ($typeOrder) {
                    return $typeOrder[$a['type']] - $typeOrder[$b['type']];
                });
            } if($sem3List != []){
                usort($sem3List, function ($a, $b) use ($typeOrder) {
                    return $typeOrder[$a['type']] - $typeOrder[$b['type']];
                });
            } if($sem4List != []){
                usort($sem4List, function ($a, $b) use ($typeOrder) {
                    return $typeOrder[$a['type']] - $typeOrder[$b['type']];
                });
            }
        } else{
            if($sem1List != []){
                usort($sem1List, function ($a, $b) use ($typeOrder) {
                    return $typeOrder[$a['type']] - $typeOrder[$b['type']];
                });
            } if($sem2List != []){
                usort($sem2List, function ($a, $b) use ($typeOrder) {
                    return $typeOrder[$a['type']] - $typeOrder[$b['type']];
                });
            }
        }


        if($this->previousClass){
            // First Year First Sem
            $averages = [];
            foreach ($sem3List as $index => $subject) {
                $newIndex = $index+1;
                $fieldMappings["type{$newIndex}"] = $subject["type"];
                $fieldMappings["sub{$newIndex}"] = $subject["name"];
                $fieldMappings["sub{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sub{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sub{$newIndex}G3"] = $subject["grade"]["average"];
                $fieldMappings["sub{$newIndex}A"] = $subject["grade"]["remarks"];
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $fieldMappings['GA1'] = array_sum($averages) / count($averages);
                // $fieldMappings['TA1'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                if(array_sum($averages) / count($averages) == 0){
                    $fieldMappings['TA1'] = "";
                } else{
                    $fieldMappings['TA1'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                }
            }
            

            // First Year Second Sem
            $averages = [];
            foreach ($sem4List as $index => $subject) {
                $newIndex = $index+1;
                $fieldMappings["sem2type{$newIndex}"] = $subject["type"];
                $fieldMappings["sem2sub{$newIndex}"] = $subject["name"];
                $fieldMappings["sem2sub{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sem2sub{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sem2sub{$newIndex}G3"] = $subject["grade"]["average"];
                $fieldMappings["sem2sub{$newIndex}A"] = $subject["grade"]["remarks"];
            
                $averages[] = $subject["grade"]["average"] ?? 0;

            }

            if($averages != []){
                $fieldMappings['GA2'] = array_sum($averages) / count($averages);
                // $fieldMappings['TA2'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                if(array_sum($averages) / count($averages) == 0){
                    $fieldMappings['TA2'] = "";
                } else{
                    $fieldMappings['TA2'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                }
            }

            // Second Year First Sem
            $averages = [];
            foreach ($sem1List as $index => $subject) {
                $newIndex = $index+1;
                $fieldMappings["sem3type{$newIndex}"] = $subject["type"];
                $fieldMappings["sem3sub{$newIndex}"] = $subject["name"];
                $fieldMappings["sem3sub{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sem3sub{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sem3sub{$newIndex}G3"] = $subject["grade"]["average"];
                $fieldMappings["sem3sub{$newIndex}A"] = $subject["grade"]["remarks"];
            
                $averages[] = $subject["grade"]["average"] ?? 0;

            }

            if($averages != []){
                $fieldMappings['GA3'] = array_sum($averages) / count($averages);
                // $fieldMappings['TA3'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                if(array_sum($averages) / count($averages) == 0){
                    $fieldMappings['TA3'] = "";
                } else{
                    $fieldMappings['TA3'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                }
            }


            // Second Year Second Sem
            $averages = [];
            foreach ($sem2List as $index => $subject) {
                $newIndex = $index+1;
                $fieldMappings["sem4type{$newIndex}"] = $subject["type"];
                $fieldMappings["sem4sub{$newIndex}"] = $subject["name"];
                $fieldMappings["sem4sub{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sem4sub{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sem4sub{$newIndex}G3"] = $subject["grade"]["average"];
                $fieldMappings["sem4sub{$newIndex}A"] = $subject["grade"]["remarks"];
            
                $averages[] = $subject["grade"]["average"] ?? 0;

            }

            if($averages != []){
                $fieldMappings['GA4'] = array_sum($averages) / count($averages);
                // $fieldMappings['TA4'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                if(array_sum($averages) / count($averages) == 0){
                    $fieldMappings['TA4'] = "";
                } else{
                    $fieldMappings['TA4'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                }
            }
        } else{
            $averages = [];
            foreach ($sem1List as $index => $subject) {
                $newIndex = $index+1;
                $fieldMappings["type{$newIndex}"] = $subject["type"];
                $fieldMappings["sub{$newIndex}"] = $subject["name"];
                $fieldMappings["sub{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sub{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sub{$newIndex}G3"] = $subject["grade"]["average"];
                $fieldMappings["sub{$newIndex}A"] = $subject["grade"]["remarks"];
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $fieldMappings['GA1'] = array_sum($averages) / count($averages);
                if(array_sum($averages) / count($averages) == 0){
                    $fieldMappings['TA1'] = "";
                } else{
                    $fieldMappings['TA1'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                }
                
            }
            

            // First Year Second Sem
            $averages = [];
            foreach ($sem2List as $index => $subject) {
                $newIndex = $index+1;
                $fieldMappings["sem2type{$newIndex}"] = $subject["type"];
                $fieldMappings["sem2sub{$newIndex}"] = $subject["name"];
                $fieldMappings["sem2sub{$newIndex}G1"] = $subject["grade"]["1st_quarter_grade"];
                $fieldMappings["sem2sub{$newIndex}G2"] = $subject["grade"]["2nd_quarter_grade"];
                $fieldMappings["sem2sub{$newIndex}G3"] = $subject["grade"]["average"];
                $fieldMappings["sem2sub{$newIndex}A"] = $subject["grade"]["remarks"];
            
                $averages[] = $subject["grade"]["average"] ?? 0;

            }

            if($averages != []){
                $fieldMappings['GA2'] = array_sum($averages) / count($averages);
                // $fieldMappings['TA2'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                if(array_sum($averages) / count($averages) == 0){
                    $fieldMappings['TA2'] = "";
                } else{
                    $fieldMappings['TA2'] = (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed";
                }
            }
        }
        
        return $fieldMappings;
    }
}
