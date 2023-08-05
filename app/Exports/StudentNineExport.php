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
    protected $sG1;
    protected $sG2;
    protected $sN1;
    protected $sN2;

    
    public function __construct($studentId)
    {
        $this->studentId = $studentId;
        // dd(Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->first()->whereIn('students', [$this->studentId]));
        $class = Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->first()->whereJsonContains('students', $studentId)->first();
        $subjectSem1 = SubjectLoad::where('class_id', $class->id)->where('semester', 1)->get();
        $subjectSem2 = SubjectLoad::where('class_id', $class->id)->where('semester', 2)->get();
        
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
        $worksheetFront->setCellValue("Q10", $this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname);
        $worksheetFront->setCellValue("U11", $this->studentInfo->gender);
        $worksheetFront->setCellValue("Q11", $this->studentInfo->age);
        $worksheetFront->setCellValue("Q12", $this->class->grade_level);
        $worksheetFront->setCellValue("V12", $this->class->section);
        $worksheetFront->setCellValue("R13", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
        $worksheetFront->setCellValue("S14", $this->class->track_course);
        $worksheetFront->setCellValue("U13", strval($this->studentId));
        $worksheetFront->setCellValue("V21", User::where('id', $this->class->adviser_id)->first()->name);
        $worksheetFront->setCellValue("V30", User::where('id', $this->class->adviser_id)->first()->name);

        // 1st Semester Core Subjects
        $row = 7;
        foreach ($this->subjectNameCore1 as $index => $subject) {
            $worksheet->setCellValue("A{$row}", $subject);
            $worksheet->setCellValue("F{$row}", $this->studentGradeCore1[$index]['1st_quarter_grade']);
            $worksheet->setCellValue("M{$row}", $this->studentGradeCore1[$index]['2nd_quarter_grade']);
            $worksheet->setCellValue("S{$row}", $this->studentGradeCore1[$index]['average']);
            // dd($this->studentGradeCore1[$index]);
            $row++;

        }

        // 1st Semester Applied Subjects
        $row = 16;
        foreach ($this->subjectNameApplied1 as $index => $subject) {
            $worksheet->setCellValue("A{$row}", $subject);
            $worksheet->setCellValue("F{$row}", $this->studentGradeApplied1[$index]['1st_quarter_grade']);
            $worksheet->setCellValue("M{$row}", $this->studentGradeApplied1[$index]['2nd_quarter_grade']);
            $worksheet->setCellValue("S{$row}", $this->studentGradeApplied1[$index]['average']);
            // dd($this->studentGradeCore1[$index]);
            $row++;
        }

        // 2nd Semester Core Subjects
        $row = 30;
        foreach ($this->subjectNameCore2 as $index => $subject) {
            $worksheet->setCellValue("A{$row}", $subject);
            $worksheet->setCellValue("F{$row}", $this->studentGradeCore2[$index]['1st_quarter_grade']);
            $worksheet->setCellValue("M{$row}", $this->studentGradeCore2[$index]['2nd_quarter_grade']);
            $worksheet->setCellValue("S{$row}", $this->studentGradeCore2[$index]['average']);
            // dd($this->studentGradeCore1[$index]);
            $row++;

        }

        // 2nd Semester Applied Subjects
        $row = 39;
        foreach ($this->subjectNameApplied2 as $index => $subject) {
            $worksheet->setCellValue("A{$row}", $subject);
            $worksheet->setCellValue("F{$row}", $this->studentGradeApplied2[$index]['1st_quarter_grade']);
            $worksheet->setCellValue("M{$row}", $this->studentGradeApplied2[$index]['2nd_quarter_grade']);
            $worksheet->setCellValue("S{$row}", $this->studentGradeApplied2[$index]['average']);
            // dd($this->studentGradeCore1[$index]);
            $row++;
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

        // Set the appropriate headers for PDF download
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sf9.pdf"',
        ];

        return response($fileContents, 200, $headers)->deleteFileAfterSend();
    }

}
