<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as PdfDompdf;
use App\Exports\StudentNineExport;
use Illuminate\Support\Facades\Storage;

class GenerateSf9Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $studentId;
    protected $templatePath;

    /**
     * Create a new job instance.
     *
     * @param string $studentId
     * @param string $templatePath
     */
    public function __construct($studentId, $templatePath)
    {
        $this->studentId = $studentId;
        $this->templatePath = $templatePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $export = new StudentNineExport($this->studentId);
        $filePath = $export->generatePdf($this->templatePath);
        
        // Store the generated PDF in a storage location accessible to users
        $destinationPath = 'pdfs/' . now()->format('Y-m-d') . '/';
        $filename = $this->studentId . '.pdf';
        Storage::disk('public')->makeDirectory($destinationPath);
        Storage::disk('public')->move($filePath, $destinationPath . $filename);

        // Dispatch an event or store the download link somewhere for the user to access later
        // You can use a database table or any other storage mechanism for this purpose.

        // For this example, we'll assume you are storing the download link in a session variable.
        session(['pdf_download_link' => Storage::url($destinationPath . $filename)]);
    }
}
