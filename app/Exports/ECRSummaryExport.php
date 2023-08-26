<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectLoad;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdfv6;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class ECRSummaryExport implements FromCollection, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $subId;

    public function __construct($subId)
    {
        $this->classId = $subId;
        $this->subject = SubjectLoad::where('id', $this->classId)->first();
        $this->class = Classes::where('id', $this->subject->class_id)->first();
    }

    public function collection()
    {   
        // dd(Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->first()->students);
        // dd(Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->where('id', $this->class->id)->first()->students);
        return StudentOfClass::whereIn('lrn', Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->where('id', $this->class->id)->first()->students)
            ->whereIn('gender', ['M', 'F'])
            ->where('name', $this->class->name)
            ->where(function ($query){
                $query->where('sem_1_status', null)
                ->orWhere('sem_1_status', 0);
            })
            // ->where('sem_1_status', null)
            ->orderByRaw("FIELD(gender, 'M', 'F'), lname ASC")
            ->get();
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
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->getProtection()->setSheet(true);
        $worksheet->getProtection()->setPassword('password'); // Replace 'your_password' with your desired password

        $row = 17;
        $data = $this->collection();
        $adviserFooter = 44+count($data);
        // dd($data[0]->fname);

        $worksheet->setCellValue("S10", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
        $worksheet->setCellValue("AC10", $this->class->grade_level);
        $worksheet->setCellValue("AI10", $this->class->track_course.' '.$this->class->grade_level.'-'.$this->class->section);
        $worksheet->setCellValue("Q12", Subject::where('id', $this->subject->subject_id)->first()->subject_name);
        $worksheet->setCellValue("AC12", User::where('id', $this->subject->teacher_id)->first()->name);
        $worksheet->setCellValue("A{$adviserFooter}", User::where('id', $this->subject->teacher_id)->first()->name);


        $previousGender = null;
        $currentNum = 1;
        foreach ($data as $index => $student) {
            // Insert new row and shift existing rows below
            $worksheet->insertNewRowBefore($row + 1, 1);

            if ($previousGender === "M" && $student->gender === "F") {
                $worksheet->mergeCells("A{$row}:AJ{$row}");
                $currentNum = 1;
                $row++;
            }

            $student_grades = $this->subject->student_grades;
            $collection = new Collection($student_grades);
            $student_grade = $collection->where('name', $student->lrn)->first();
            
            
            $worksheet->mergeCells("G{$row}:N{$row}");
            $worksheet->mergeCells("O{$row}:Q{$row}");
            $worksheet->mergeCells("W{$row}:Z{$row}");
            $worksheet->mergeCells("AA{$row}:AD{$row}");
            $worksheet->mergeCells("AE{$row}:AF{$row}");
            $worksheet->mergeCells("AI{$row}:AJ{$row}");

            $worksheet->setCellValue("A{$row}", $currentNum);
            $worksheet->setCellValue("B{$row}", $student->lname);
            $worksheet->setCellValue("D{$row}", $student->fname);
            $worksheet->setCellValue("E{$row}", $student->mname[0]);
            $worksheet->setCellValue("F{$row}", $student->gender);

            // dd($student_grade['1st_quarter_grade']);
            $worksheet->setCellValue("G{$row}", $student_grade['1st_quarter_grade']);
            $worksheet->setCellValue("O{$row}", $student_grade['2nd_quarter_grade']);
            $worksheet->setCellValue("W{$row}", $student_grade['average']);
            $worksheet->setCellValue("AA{$row}", $student_grade['average']);
            $worksheet->setCellValue("AE{$row}", $student_grade['remarks']);
            $worksheet->setCellValue("AI{$row}", $student_grade['description']);

            
            $previousGender = $student->gender;
            $currentNum++;
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

    


}
