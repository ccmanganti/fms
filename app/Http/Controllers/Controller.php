<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Exports\StudentNineExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Student;
use Spatie\Pdf\Pdf;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;


    // public function convertAndDownload($studentId)
    // {
    //     // Generate the Excel file using maatwebsite/excel
    //     $export = new StudentNineExport($studentId);
    //     $excelFilePath = $this->exportToExcel($export);

    //     // Convert the Excel file to PDF using spatie/pdf
    //     $pdfFilePath = $this->convertToPdf($excelFilePath);

    //     // Download the PDF
    //     return response()->download($pdfFilePath)->deleteFileAfterSend();
    // }

    // private function exportToExcel($export)
    // {
    //     $excelFilePath = tempnam(storage_path('app'), 'exported_') . '.xlsx';
    //     $exportFormat = \Maatwebsite\Excel\Excel::XLSX;

    //     Excel::store($export, $excelFilePath, $exportFormat, 'local');

    //     return $excelFilePath;
    // }

    // private function convertToPdf($excelFilePath)
    // {
    //     $pdf = new Pdf();
    //     $pdfFilePath = tempnam(storage_path('app'), 'converted_') . '.pdf';

    //     $pdf->addPage($excelFilePath)
    //         ->saveAs($pdfFilePath);

    //     return $pdfFilePath;
    // }
}
