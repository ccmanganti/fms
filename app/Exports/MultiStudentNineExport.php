<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class MultiStudentNineExport
{
    protected $studentIds;

    public function __construct($studentIds)
    {
        $this->studentIds = $studentIds;
    }


    public function export()
    {
        $zip = new ZipArchive;
        $tempZipPath = tempnam(sys_get_temp_dir(), 'students_zip');
        $zip->open($tempZipPath, ZipArchive::CREATE);

        foreach ($this->studentIds as $studentId) {
            $export = new StudentNineExport($studentId);
            $studentFileName = $export->getStudentFileName();

            // Save the PhpSpreadsheet\Spreadsheet object as a string
            $tempFilePath = tempnam(sys_get_temp_dir(), 'student_sf9');
            $writer = IOFactory::createWriter($export->mapToTemplate(public_path('sf9.xlsx')), 'Xlsx');
            $writer->save($tempFilePath);

            $zip->addFromString($studentFileName . '.xlsx', file_get_contents($tempFilePath));

            // Clean up the temporary file
            unlink($tempFilePath);
        }

        $zip->close();

        return $tempZipPath;
    }
}