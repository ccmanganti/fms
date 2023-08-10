<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\StudentOfClass;
use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\User;
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

class StudentTwoExport implements FromCollection, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $classId;

    public function __construct($classId)
    {
        $this->classId = $classId;
        $this->class = Classes::where('id', $this->classId)->first();

        if($this->class->track_course == 'ABM'){
            $this->course = 'Academic Track - Accountancy, Business and Management (ABM)';
        } else if($this->class->track_course == 'STEM'){
            $this->course = 'Academic Track - Science, Technology, Engineering, and Mathematics (STEM)';
        } else if($this->class->track_course == 'HUMSS'){
            $this->course = 'Academic Track -Humanities and Social Sciences (HUMSS)';
        } else if($this->class->track_course == 'GAS'){
            $this->course = 'Academic Track - General Academic Strand (GAS)';
        } else if($this->class->track_course == 'ADT'){
            $this->course = 'Arts and Design Track';
        } else if($this->class->track_course == 'ST'){
            $this->course = 'Sports Track';
        } else if($this->class->track_course == 'TVL - AFA'){
            $this->course = 'TVL Track -  Agricultural-Fishery Arts (AFA)';
        } else if($this->class->track_course == 'TVL - HE'){
            $this->course = 'TVL Track -  Home Economics (HE)';
        } else if($this->class->track_course == 'TVL - IA'){
            $this->course = 'TVL Track -  Industrial Arts (IA)';
        } else if($this->class->track_course == 'TVL - ICT'){
            $this->course = 'TVL Track -  Information and Communications Technology (ICT)';
        }
    }

    public function collection()
    {
        return StudentOfClass::whereIn('lrn', Classes::where('school_year_id', SchoolYear::where('current', true)->first()->id)->where('id', $this->classId)->first()->students)
            ->whereIn('gender', ['M', 'F'])
            ->where('name', $this->class->name)
            ->where('sem_2_status', null)
            ->orderByRaw("FIELD(gender, 'M', 'F'), lname ASC")
            ->get();
    }

    // /**
    //  * @param mixed $student
    //  * @return array
    //  */
    // public function map($student): array
    // {
    //     return [
    //         $student->lrn,
    //         $student->lname,
    //         $student->fname,
    //         $student->mname,
    //         $student->gender,
    //         $student->date_of_birth,
    //         $student->religion,
    //         $student->no_street_purok,
    //         $student->barangay,
    //         $student->municipality,
    //         $student->province,
    //         $student->father_name,
    //         $student->mother_name,
    //         $student->guardian,
    //         $student->relationship,
    //         $student->contact_number,
    //         $student->modality,
    //         $student->remarks,
    //     ];
    // }

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

        $row = 20;
        $data = $this->collection();

        $worksheet->setCellValue("Z9", (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1));
        $worksheet->setCellValue("I9", 'Second Semester');
        $worksheet->setCellValue("I16", $this->class->section);
        $worksheet->setCellValue("AK9", $this->class->grade_level);
        $worksheet->setCellValue("AT26", User::where('id', $this->class->adviser_id)->first()->name);
        $worksheet->setCellValue("AX11", $this->course);
        $worksheet->setCellValue("A36", 'Generated on: '.date('l, F j, Y'));





        foreach ($data as $student) {
            // Insert new row and shift existing rows below
            $worksheet->insertNewRowBefore($row + 1, 1);

            $worksheet->mergeCells("A{$row}:B{$row}");
            $worksheet->mergeCells("C{$row}:G{$row}");
            $worksheet->mergeCells("H{$row}:L{$row}");
            $worksheet->mergeCells("M{$row}:Q{$row}");
            $worksheet->mergeCells("S{$row}:U{$row}");
            $worksheet->mergeCells("V{$row}:W{$row}");
            $worksheet->mergeCells("X{$row}:AA{$row}");
            $worksheet->mergeCells("AB{$row}:AF{$row}");
            $worksheet->mergeCells("AG{$row}:AK{$row}");
            $worksheet->mergeCells("AL{$row}:AM{$row}");
            $worksheet->mergeCells("AN{$row}:AQ{$row}");
            $worksheet->mergeCells("AR{$row}:AV{$row}");
            $worksheet->mergeCells("AW{$row}:AX{$row}");
            $worksheet->mergeCells("AY{$row}:BB{$row}");
            $worksheet->mergeCells("BC{$row}:BD{$row}");
            $worksheet->mergeCells("BE{$row}:BG{$row}");
            $worksheet->mergeCells("BH{$row}:BI{$row}");
            $worksheet->mergeCells("BJ{$row}:BQ{$row}");

            $worksheet->setCellValue("A{$row}", $student->lrn);
            $worksheet->setCellValue("C{$row}", $student->lname);
            $worksheet->setCellValue("H{$row}", $student->fname);
            $worksheet->setCellValue("M{$row}", $student->mname);
            $worksheet->setCellValue("R{$row}", $student->gender);
            $worksheet->setCellValue("S{$row}", $student->date_of_birth);
            $worksheet->setCellValue("X{$row}", $student->religion);
            $worksheet->setCellValue("AB{$row}", $student->no_street_purok);
            $worksheet->setCellValue("AG{$row}", $student->barangay);
            $worksheet->setCellValue("AL{$row}", $student->municipality);
            $worksheet->setCellValue("AN{$row}", $student->province);
            $worksheet->setCellValue("AR{$row}", $student->father_name);
            $worksheet->setCellValue("AW{$row}", $student->mother_name);
            $worksheet->setCellValue("AY{$row}", $student->guardian);
            $worksheet->setCellValue("BC{$row}", $student->relationship);
            $worksheet->setCellValue("BE{$row}", $student->contact_number);
            $worksheet->setCellValue("BH{$row}", $student->modality);
            $worksheet->setCellValue("BJ{$row}", $student->remarks);
            

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
