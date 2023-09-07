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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class StudentNineExport implements ShouldAutoSize
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

    /**
     * Manually map the data to specific cells in the Excel template and insert new rows.
     *
     * @param string $filePath
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function mapToTemplate(string $filePath): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $spreadsheet = IOFactory::load($filePath);
        // $worksheet = $spreadsheet->getActiveSheet();
        $worksheet = $spreadsheet->getSheet(0);
        $worksheetFront = $spreadsheet->getSheet(1);

        $worksheet->getProtection()->setSheet(true);
        $worksheet->getProtection()->setPassword('password'); // Replace 'your_password' with your desired password


        // Details
        $worksheetFront->setCellValue("Q10", strtoupper($this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname));
        $worksheetFront->setCellValue("U11", $this->studentInfo->gender);
        $worksheetFront->setCellValue("Q11", $this->studentInfo->age);
        $worksheetFront->setCellValue("Q12", $this->class->grade_level);
        $worksheetFront->setCellValue("V12", $this->class->section);
        $worksheetFront->setCellValue("R13", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
        $worksheetFront->setCellValue("O22", (SchoolYear::where('id', $this->class->school_year_id)->first()->principal));
        $worksheetFront->setCellValue("O30", (SchoolYear::where('id', $this->class->school_year_id)->first()->principal));
        $worksheetFront->setCellValue("S14", $this->class->track_course);
        $worksheetFront->setCellValue("U13", strval($this->studentId));
        $worksheetFront->setCellValue("V21", User::where('id', $this->class->adviser_id)->first()->name);
        $worksheetFront->setCellValue("V30", User::where('id', $this->class->adviser_id)->first()->name);

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
        // Core Subjects
        $averages = [];
        $row = 7;
        foreach ($sem1List as $index => $subject) {
            if($subject["type"] == "Core"){
                $worksheet->setCellValue("A{$row}", $subject["name"]);
                $worksheet->setCellValue("F{$row}", $subject["grade"]['1st_quarter_grade']);
                $worksheet->setCellValue("M{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("S{$row}", $subject["grade"]['average']);
                $row++;
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
        }
        // Applied Subjects
        $row = 16;
        foreach ($sem1List as $index => $subject) {
            if($subject["type"] != "Core"){
                $worksheet->setCellValue("A{$row}", $subject["name"]);
                $worksheet->setCellValue("F{$row}", $subject["grade"]['1st_quarter_grade']);
                $worksheet->setCellValue("M{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("S{$row}", $subject["grade"]['average']);
                $row++;
                $averages[] = $subject["grade"]["average"] ?? 0;

            }
        }

        if($averages != []){
            $worksheet->setCellValue("S24", array_sum($averages) / count($averages));
        }

        // 2nd Semester
        // Core Subjects
        $averages = [];
        $row = 30;
        foreach ($sem2List as $index => $subject) {
            if($subject["type"] == "Core"){
                $worksheet->setCellValue("A{$row}", $subject["name"]);
                $worksheet->setCellValue("F{$row}", $subject["grade"]['1st_quarter_grade']);
                $worksheet->setCellValue("M{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("S{$row}", $subject["grade"]['average']);
                $row++;
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
        }
        // Applied Subjects
        $row = 39;
        foreach ($sem2List as $index => $subject) {
            if($subject["type"] != "Core"){
                $worksheet->setCellValue("A{$row}", $subject["name"]);
                $worksheet->setCellValue("F{$row}", $subject["grade"]['1st_quarter_grade']);
                $worksheet->setCellValue("M{$row}", $subject["grade"]['2nd_quarter_grade']);
                $worksheet->setCellValue("S{$row}", $subject["grade"]['average']);
                $row++;
                $averages[] = $subject["grade"]["average"] ?? 0;
            }
        }

        if($averages != []){
            $worksheet->setCellValue("S47", array_sum($averages) / count($averages));
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

    public function generatePdf(string $filePath)
    {
        $spreadsheet = $this->mapToTemplate($filePath);
        $tempPdfFilePath = tempnam(sys_get_temp_dir(), 'mapped_students_pdf');
        $pdfWriter = new PdfDompdf($spreadsheet);
        $pdfWriter->save($tempPdfFilePath);

        $fileContents = file_get_contents($tempPdfFilePath);

        // Set the appropriate headers for PDF downloads
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sf9.pdf"',
        ];

        return response($fileContents, 200, $headers)->deleteFileAfterSend();
    }

}
