<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\SubjectLoad;
use App\Models\Subject;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as PdfDompdf;
use Dompdf\Dompdf as DompdfLib;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use GuzzleHttp\Client;
use Ilovepdf\Ilovepdf;

class StudentTenExport implements ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $studentId;
    protected $class;
    protected $gender;
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

    /**
     * Manually map the data to specific cells in the Excel template and insert new rows.
     *
     * @param string $filePath
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function mapToTemplate(string $filePath): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getSheet(0);
        $worksheetBack = $spreadsheet->getSheet(1);

        $worksheet->getProtection()->setSheet(true);
        $worksheet->getProtection()->setPassword('password'); // Replace 'your_password' with your desired password

        if($this->studentInfo->gender == "M"){
            $this->gender = "MALE";
        } else{
            $this->gender = "MALE";
        }

        $sem1List = [];
        $sem2List = [];
        $sem3List = [];
        $sem4List = [];
        
        if($this->previousClass){
            // PERSONAL INFORMATION
            $worksheet->setCellValue("F7", $this->studentInfo->lname);
            $worksheet->setCellValue("Y7", $this->studentInfo->fname);
            $worksheet->setCellValue("AZ7", $this->studentInfo->mname);
            $worksheet->setCellValue("AN8", $this->gender);
            $worksheet->setCellValue("C8", strval($this->studentId));
            $worksheet->setCellValue("AA8", $this->studentInfo->date_of_birth);

            $worksheetBack->setCellValue("AS24", $this->class->grade_level);
            $worksheetBack->setCellValue("AS46", $this->class->grade_level);
            $worksheetBack->setCellValue("AS5", $this->class->section);
            $worksheetBack->setCellValue("AS48", $this->class->section);
            $worksheetBack->setCellValue("BA4", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
            $worksheetBack->setCellValue("BA46", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
            $worksheetBack->setCellValue("G5", $this->course);
            $worksheetBack->setCellValue("G48", $this->course);
            $worksheetBack->setCellValue("A29", User::where('id', $this->class->adviser_id)->first()->name);
            $worksheetBack->setCellValue("A72", User::where('id', $this->class->adviser_id)->first()->name);

            $worksheet->setCellValue("AS22", $this->previousClass->grade_level);
            $worksheet->setCellValue("AS65", $this->previousClass->grade_level);
            $worksheet->setCellValue("AS24", $this->previousClass->section);
            $worksheet->setCellValue("AS67", $this->previousClass->section);
            $worksheet->setCellValue("BA22", (SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate+1));
            $worksheet->setCellValue("BA65", (SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate+1));
            $worksheet->setCellValue("G24", $this->previousCourse);
            $worksheet->setCellValue("G67", $this->previousCourse);
            $worksheet->setCellValue("A48", User::where('id', $this->previousClass->adviser_id)->first()->name);
            $worksheet->setCellValue("A91", User::where('id', $this->previousClass->adviser_id)->first()->name);

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
            // Details
            $worksheet->setCellValue("F7", $this->studentInfo->lname);
            $worksheet->setCellValue("Y7", $this->studentInfo->fname);
            $worksheet->setCellValue("AZ7", $this->studentInfo->mname);
            $worksheet->setCellValue("AN8", $this->gender);
            $worksheet->setCellValue("AA8", $this->studentInfo->date_of_birth);
            $worksheet->setCellValue("AS22", $this->class->grade_level);
            $worksheet->setCellValue("AS65", $this->class->grade_level);
            $worksheet->setCellValue("AS24", $this->class->section);
            $worksheet->setCellValue("AS67", $this->class->section);
            $worksheet->setCellValue("C8", strval($this->studentId));
            $worksheet->setCellValue("BA22", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
            $worksheet->setCellValue("BA65", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
            $worksheet->setCellValue("G24", $this->course);
            $worksheet->setCellValue("G67", $this->course);
            $worksheet->setCellValue("A48", User::where('id', $this->class->adviser_id)->first()->name);
            $worksheet->setCellValue("A91", User::where('id', $this->class->adviser_id)->first()->name);

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
            $averages = [];
            $row = 30;
            foreach ($sem3List as $index => $subject) {
                $worksheet->setCellValue("A{$row}", $subject["type"]);
                $worksheet->setCellValue("I{$row}", $subject["name"]);
                $worksheet->setCellValue("AT{$row}", $subject["grade"]["1st_quarter_grade"]);
                $worksheet->setCellValue("AY{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $subject["grade"]['average']);
                $worksheet->setCellValue("BI{$row}", $subject["grade"]['remarks']);
                $row++;
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $worksheet->setCellValue("BD42", array_sum($averages) / count($averages));
                $worksheet->setCellValue("BI42", (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed");
            }

            $averages = [];
            $row = 73;
            foreach ($sem4List as $index => $subject) {
                $worksheet->setCellValue("A{$row}", $subject["type"]);
                $worksheet->setCellValue("I{$row}", $subject["name"]);
                $worksheet->setCellValue("AT{$row}", $subject["grade"]["1st_quarter_grade"]);
                $worksheet->setCellValue("AY{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $subject["grade"]['average']);
                $worksheet->setCellValue("BI{$row}", $subject["grade"]['remarks']);
                $row++;
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $worksheet->setCellValue("BD85", array_sum($averages) / count($averages));
                $worksheet->setCellValue("BI85", (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed");
            }

            $averages = [];
            $row = 11;
            foreach ($sem1List as $index => $subject) {
                $worksheetBack->setCellValue("A{$row}", $subject["type"]);
                $worksheetBack->setCellValue("I{$row}", $subject["name"]);
                $worksheetBack->setCellValue("AT{$row}", $subject["grade"]["1st_quarter_grade"]);
                $worksheetBack->setCellValue("AY{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheetBack->setCellValue("BD{$row}", $subject["grade"]['average']);
                $worksheetBack->setCellValue("BI{$row}", $subject["grade"]['remarks']);
                $row++;
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $worksheetBack->setCellValue("BD23", array_sum($averages) / count($averages));
                $worksheetBack->setCellValue("BI23", (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed");
            }

            $averages = [];
            $row = 54;
            foreach ($sem2List as $index => $subject) {
                $worksheetBack->setCellValue("A{$row}", $subject["type"]);
                $worksheetBack->setCellValue("I{$row}", $subject["name"]);
                $worksheetBack->setCellValue("AT{$row}", $subject["grade"]["1st_quarter_grade"]);
                $worksheetBack->setCellValue("AY{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheetBack->setCellValue("BD{$row}", $subject["grade"]['average']);
                $worksheetBack->setCellValue("BI{$row}", $subject["grade"]['remarks']);
                $row++;
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $worksheetBack->setCellValue("BD66", array_sum($averages) / count($averages));
                $worksheetBack->setCellValue("BI66", (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed");
            }
            
        } else{
            $averages = [];
            $row = 30;
            foreach ($sem1List as $index => $subject) {
                $worksheet->setCellValue("A{$row}", $subject["type"]);
                $worksheet->setCellValue("I{$row}", $subject["name"]);
                $worksheet->setCellValue("AT{$row}", $subject["grade"]["1st_quarter_grade"]);
                $worksheet->setCellValue("AY{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $subject["grade"]['average']);
                $worksheet->setCellValue("BI{$row}", $subject["grade"]['remarks']);
                $row++;
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $worksheet->setCellValue("BD42", array_sum($averages) / count($averages));
                $worksheet->setCellValue("BI42", (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed");
            }

            $averages = [];
            $row = 74;
            foreach ($sem2List as $index => $subject) {
                $worksheet->setCellValue("A{$row}", $subject["type"]);
                $worksheet->setCellValue("I{$row}", $subject["name"]);
                $worksheet->setCellValue("AT{$row}", $subject["grade"]["1st_quarter_grade"]);
                $worksheet->setCellValue("AY{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $subject["grade"]['average']);
                $worksheet->setCellValue("BI{$row}", $subject["grade"]['remarks']);
                $row++;
            
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
            if($averages != []){
                $worksheet->setCellValue("BD85", array_sum($averages) / count($averages));
                $worksheet->setCellValue("BI85", (array_sum($averages) / count($averages)) > 74 ? "Passed" : "Failed");
            }
        }
        

        return $spreadsheet;
    }

    /**
     * Generate the file for download.
     *
     * @param string $filePath
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(string $filePath)
    {
        $spreadsheet = $this->mapToTemplate($filePath);
        $tempFilePath = tempnam(sys_get_temp_dir(), 'mapped_students');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFilePath);

        return response()->download($tempFilePath)->deleteFileAfterSend();
    }

}
