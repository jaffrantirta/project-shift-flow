<?php

namespace App\Filament\Pages\Reports\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsReport
{
    abstract protected function getPdfView(): string;

    abstract protected function getPdfData(): array;

    abstract protected function getCsvFilename(): string;

    abstract protected function getCsvHeaders(): array;

    abstract protected function getCsvRow(mixed $record): array;

    abstract protected function getCsvRecords(): Collection;

    protected function getPdfFilename(): string
    {
        return str_replace('.csv', '.pdf', $this->getCsvFilename());
    }

    protected function getPdfTitle(): string
    {
        return static::$title ?? 'Report';
    }

    protected function getPdfStats(): array
    {
        return [];
    }

    protected function exportCsv(): StreamedResponse
    {
        $records = $this->getCsvRecords();

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->getCsvHeaders());
            foreach ($records as $record) {
                fputcsv($handle, $this->getCsvRow($record));
            }
            fclose($handle);
        }, $this->getCsvFilename(), ['Content-Type' => 'text/csv']);
    }

    protected function exportPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = array_merge($this->getPdfData(), [
            'title'    => $this->getPdfTitle(),
            'subtitle' => null,
            'stats'    => $this->getPdfStats(),
        ]);

        $pdf = Pdf::loadView($this->getPdfView(), $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'     => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        $output = $pdf->output();
        $filename = $this->getPdfFilename();

        return response()->streamDownload(
            fn() => print($output),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->action(fn() => $this->exportCsv()),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(fn() => $this->exportPdf()),
            ])
            ->label('Export')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->button(),
        ];
    }
}
