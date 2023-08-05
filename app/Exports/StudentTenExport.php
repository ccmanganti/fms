<?php

namespace App\Exports;

use App\Models\Student;
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
        $this->studentId = $studentId;
        // dd(Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->first()->whereIn('students', [$this->studentId]));
        $class = Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->whereJsonContains('students', $studentId)->first();
        // dd($class->grade_level);
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


        // IF CLASS IS GRADE 12, POPULATE THE FRONT PART OF SF10 WITH PREVIOUS GRADES
        if($this->class->grade_level == "12"){
            $previousSY = SchoolYear::where('sydate', (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate)-1)->first();
            $classExist = Classes::where('school_year_id', $previousSY->id)->first();
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

                $previousStudentGradeCore1 = [];
                $previousStudentGradeCore2 = [];

                $previousStudentGradeApplied1 = [];
                $previousStudentGradeApplied2 = [];
                
                $previousSubjectNameCore1 = [];
                $previousSubjectNameCore2 = [];
                
                $previousSubjectNameApplied1 = [];
                $previousSubjectNameApplied2 = [];


                foreach ($previousSubjectSem1 as $subject) {
                    $studentGrade = collect($subject->student_grades)->where('name', $this->studentId)->first();
                    $subjectType = Subject::where('id', $subject->subject_id)->first()->subject_type;

                    if($subjectType == 'Core'){
                        $previousStudentGradeCore1[] = $studentGrade;
                        $previousSubjectNameCore1[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                    } else{
                        $previousStudentGradeApplied1[] = $studentGrade;
                        $previousSubjectNameApplied1[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                    }
                    
                    // $sN1[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                }
                foreach ($previousSubjectSem2 as $subject) {
                    $studentGrade = collect($subject->student_grades)->where('name', $this->studentId)->first();
                    $subjectType = Subject::where('id', $subject->subject_id)->first()->subject_type;
                    
                    if($subjectType == 'Core'){
                        $previousStudentGradeCore2[] = $studentGrade;
                        $previousSubjectNameCore2[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                    } else{
                        $previousStudentGradeApplied2[] = $studentGrade;
                        $previousSubjectNameApplied2[] = Subject::where('id', $subject->subject_id)->first()->subject_name;
                    }
                }
                
                $this->previousClass = $previousClass;

                $this->previousStudentGradeCore1 = $previousStudentGradeCore1;
                $this->previousSubjectNameCore1 = $previousSubjectNameCore1;
                
                $this->previousStudentGradeCore2 = $previousStudentGradeCore2;
                $this->previousSubjectNameCore2 = $previousSubjectNameCore2;

                $this->previousStudentGradeApplied1 = $previousStudentGradeApplied1;
                $this->previousSubjectNameApplied1 = $previousSubjectNameApplied1;

                $this->previousStudentGradeApplied2 = $previousStudentGradeApplied2;
                $this->previousSubjectNameApplied2 = $previousSubjectNameApplied2;
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
        
        if($this->class->grade_level == "12"){
            // Details
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

            // 1st Semester Core Subjects
            $row = 11;
            foreach ($this->subjectNameCore1 as $index => $subject) {
                $worksheetBack->setCellValue("A{$row}", "Core");
                $worksheetBack->setCellValue("I{$row}", $subject);
                $worksheetBack->setCellValue("AT{$row}", $this->studentGradeCore1[$index]['1st_quarter_grade']);
                $worksheetBack->setCellValue("AY{$row}", $this->studentGradeCore1[$index]['2nd_quarter_grade']);
                $worksheetBack->setCellValue("BD{$row}", $this->studentGradeCore1[$index]['average']);
                $row++;
            }

            // 2nd Semester Applied Subjects
            foreach ($this->subjectNameApplied1 as $index => $subject) {
                $worksheetBack->setCellValue("A{$row}", "Applied");
                $worksheetBack->setCellValue("I{$row}", $subject);
                $worksheetBack->setCellValue("AT{$row}", $this->studentGradeApplied1[$index]['1st_quarter_grade']);
                $worksheetBack->setCellValue("AY{$row}", $this->studentGradeApplied1[$index]['2nd_quarter_grade']);
                $worksheetBack->setCellValue("BD{$row}", $this->studentGradeApplied1[$index]['average']);
                $row++;
            }
            
            $row = 54;
            foreach ($this->subjectNameCore2 as $index => $subject) {
                $worksheetBack->setCellValue("A{$row}", "Core");
                $worksheetBack->setCellValue("I{$row}", $subject);
                $worksheetBack->setCellValue("AT{$row}", $this->studentGradeCore2[$index]['1st_quarter_grade']);
                $worksheetBack->setCellValue("AY{$row}", $this->studentGradeCore2[$index]['2nd_quarter_grade']);
                $worksheetBack->setCellValue("BD{$row}", $this->studentGradeCore2[$index]['average']);
                $row++;
            }

            foreach ($this->subjectNameApplied2 as $index => $subject) {
                $worksheetBack->setCellValue("A{$row}", "Applied");
                $worksheetBack->setCellValue("I{$row}", $subject);
                $worksheetBack->setCellValue("AT{$row}", $this->studentGradeApplied2[$index]['1st_quarter_grade']);
                $worksheetBack->setCellValue("AY{$row}", $this->studentGradeApplied2[$index]['2nd_quarter_grade']);
                $worksheetBack->setCellValue("BD{$row}", $this->studentGradeApplied2[$index]['average']);
                // dd($this->studentGradeCore1[$index]);
                $row++;
            }

            if($this->previousClass){
                    // Details
                $worksheet->setCellValue("F7", $this->studentInfo->lname);
                $worksheet->setCellValue("Y7", $this->studentInfo->fname);
                $worksheet->setCellValue("AZ7", $this->studentInfo->mname);
                $worksheet->setCellValue("AN8", $this->gender);
                $worksheet->setCellValue("AA8", $this->studentInfo->date_of_birth);
                $worksheet->setCellValue("AS22", $this->previousClass->grade_level);
                $worksheet->setCellValue("AS65", $this->previousClass->grade_level);
                $worksheet->setCellValue("AS24", $this->previousClass->section);
                $worksheet->setCellValue("AS67", $this->previousClass->section);
                $worksheet->setCellValue("C8", strval($this->studentId));
                $worksheet->setCellValue("BA22", (SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate+1));
                $worksheet->setCellValue("BA65", (SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->previousClass->school_year_id)->first()->sydate+1));
                $worksheet->setCellValue("G24", $this->course);
                $worksheet->setCellValue("G67", $this->course);
                $worksheet->setCellValue("A48", User::where('id', $this->previousClass->adviser_id)->first()->name);
                $worksheet->setCellValue("A91", User::where('id', $this->previousClass->adviser_id)->first()->name);

                // 1st Semester Core Subjects
                $row = 30;
                foreach ($this->previousSubjectNameCore1 as $index => $subject) {
                    $worksheet->setCellValue("A{$row}", "Core");
                    $worksheet->setCellValue("I{$row}", $subject);
                    $worksheet->setCellValue("AT{$row}", $this->previousStudentGradeCore1[$index]['1st_quarter_grade']);
                    $worksheet->setCellValue("AY{$row}", $this->previousStudentGradeCore1[$index]['2nd_quarter_grade']);
                    $worksheet->setCellValue("BD{$row}", $this->previousStudentGradeCore1[$index]['average']);
                    // dd($this->studentGradeCore1[$index]);
                    $row++;
                }

                // 2nd Semester Applied Subjects
                foreach ($this->previousSubjectNameApplied1 as $index => $subject) {
                    $worksheet->setCellValue("A{$row}", "Applied");
                    $worksheet->setCellValue("I{$row}", $subject);
                    $worksheet->setCellValue("AT{$row}", $this->previousStudentGradeApplied1[$index]['1st_quarter_grade']);
                    $worksheet->setCellValue("AY{$row}", $this->previousStudentGradeApplied1[$index]['2nd_quarter_grade']);
                    $worksheet->setCellValue("BD{$row}", $this->previousStudentGradeApplied1[$index]['average']);
                    // dd($this->studentGradeCore1[$index]);
                    $row++;
                }
                
                $row = 73;
                foreach ($this->previousSubjectNameCore2 as $index => $subject) {
                    $worksheet->setCellValue("A{$row}", "Core");
                    $worksheet->setCellValue("I{$row}", $subject);
                    $worksheet->setCellValue("AT{$row}", $this->previousStudentGradeCore2[$index]['1st_quarter_grade']);
                    $worksheet->setCellValue("AY{$row}", $this->previousStudentGradeCore2[$index]['2nd_quarter_grade']);
                    $worksheet->setCellValue("BD{$row}", $this->previousStudentGradeCore2[$index]['average']);
                    // dd($this->studentGradeCore1[$index]);
                    $row++;
                }

                // 2nd Semester Applied Subjects
                foreach ($this->previousSubjectNameApplied2 as $index => $subject) {
                    $worksheet->setCellValue("A{$row}", "Applied");
                    $worksheet->setCellValue("I{$row}", $subject);
                    $worksheet->setCellValue("AT{$row}", $this->previousStudentGradeApplied2[$index]['1st_quarter_grade']);
                    $worksheet->setCellValue("AY{$row}", $this->previousStudentGradeApplied2[$index]['2nd_quarter_grade']);
                    $worksheet->setCellValue("BD{$row}", $this->previousStudentGradeApplied2[$index]['average']);
                    // dd($this->studentGradeCore1[$index]);
                    $row++;
                }
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

            // 1st Semester Core Subjects
            $row = 30;
            foreach ($this->subjectNameCore1 as $index => $subject) {
                $worksheet->setCellValue("A{$row}", "Core");
                $worksheet->setCellValue("I{$row}", $subject);
                $worksheet->setCellValue("AT{$row}", $this->studentGradeCore1[$index]['1st_quarter_grade']);
                $worksheet->setCellValue("AY{$row}", $this->studentGradeCore1[$index]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $this->studentGradeCore1[$index]['average']);
                // dd($this->studentGradeCore1[$index]);
                $row++;
            }

            // 2nd Semester Applied Subjects
            foreach ($this->subjectNameApplied1 as $index => $subject) {
                $worksheet->setCellValue("A{$row}", "Applied");
                $worksheet->setCellValue("I{$row}", $subject);
                $worksheet->setCellValue("AT{$row}", $this->studentGradeApplied1[$index]['1st_quarter_grade']);
                $worksheet->setCellValue("AY{$row}", $this->studentGradeApplied1[$index]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $this->studentGradeApplied1[$index]['average']);
                // dd($this->studentGradeCore1[$index]);
                $row++;
            }
            
            $row = 73;
            foreach ($this->subjectNameCore2 as $index => $subject) {
                $worksheet->setCellValue("A{$row}", "Core");
                $worksheet->setCellValue("I{$row}", $subject);
                $worksheet->setCellValue("AT{$row}", $this->studentGradeCore2[$index]['1st_quarter_grade']);
                $worksheet->setCellValue("AY{$row}", $this->studentGradeCore2[$index]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $this->studentGradeCore2[$index]['average']);
                // dd($this->studentGradeCore1[$index]);
                $row++;
            }

            // 2nd Semester Applied Subjects
            foreach ($this->subjectNameApplied2 as $index => $subject) {
                $worksheet->setCellValue("A{$row}", "Applied");
                $worksheet->setCellValue("I{$row}", $subject);
                $worksheet->setCellValue("AT{$row}", $this->studentGradeApplied2[$index]['1st_quarter_grade']);
                $worksheet->setCellValue("AY{$row}", $this->studentGradeApplied2[$index]['2nd_quarter_grade']);
                $worksheet->setCellValue("BD{$row}", $this->studentGradeApplied2[$index]['average']);
                // dd($this->studentGradeCore1[$index]);
                $row++;
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
