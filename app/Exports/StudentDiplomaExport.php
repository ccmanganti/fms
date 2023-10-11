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

class StudentDiplomaExport implements ShouldAutoSize
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

    }

    public function generatePdf()
    {
        // Load the PDF template
        $pdf = new Pdf(public_path('sf9-form.pdf'), [
            'command' => '/usr/bin/pdftk',
            'useExec' => true,
        ]);
        
        $data = $this->populateFormFields();

        // dd($data);

        $result = $pdf->fillForm($data)->flatten()->saveAs(public_path('Diploma - '.$this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname.'.pdf'));

        // Send the file download response
        // return response()->download(public_path('\filled.pdf'), 'filled.pdf')->deleteFileAfterSend();
        return response()->file(public_path('Diploma - '.$this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname.'.pdf'), [
            'Content-Disposition' => 'inline; filename="filled.pdf"'
        ])->deleteFileAfterSend();
    }

    // Rest of your class methods here...

    protected function populateFormFields()
    {

        $fieldMappings = [];

        // PERSONAL INFORMATION
        $fieldMappings['name'] = strtoupper($this->studentInfo->lname.', '.$this->studentInfo->fname.' '.$this->studentInfo->mname);
        // $fieldMappings['School Year'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sydate).' - '.(SchoolYear::where('id', $this->class->school_year_id)->first()->sydate+1);
        $fieldMappings['principal'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->principal);
        $fieldMappings['positionTagalogLeft'] = "Punongguro ".(SchoolYear::where('id', $this->class->school_year_id)->first()->position);
        $fieldMappings['positionEnglishLeft'] = "Principal ".(SchoolYear::where('id', $this->class->school_year_id)->first()->position);
        $fieldMappings['positionTagalogRight'] = "Pansangay na Tagapamahala ng mga Paaralan";
        $fieldMappings['positionEnglishRight'] = "Schools Division Superintendent";
        $fieldMappings['sds'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->sds);
        $fieldMappings['lrn'] = $this->studentInfo->lrn;
        $fieldMappings['track '] = $this->course;
        $fieldMappings['Teacher'] = User::where('id', $this->class->adviser_id)->first()->name;
        $fieldMappings['petsa'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->completion);
        $fieldMappings['date'] = (SchoolYear::where('id', $this->class->school_year_id)->first()->completion);

        return $fieldMappings;
    }
}
