<?php

namespace App\Libraries;

class SimplePdfGenerator
{
    /**
     * Generate a minimalist PDF invoice to keep MVP dependency-free.
     */
    public function generateInvoicePdf(array $invoice, array $student): string
    {
        $directory = WRITEPATH . 'invoices';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = sprintf(
            '%s_%s.pdf',
            $this->slugify($invoice['invoice_number'] ?? ('INV-' . time())),
            $invoice['student_id']
        );

        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $lines = $this->buildInvoiceLines($invoice, $student);
        $pdfContent = $this->renderSimplePdf($lines);

        file_put_contents($path, $pdfContent);

        // Return path relative to writable for downstream consumption
        return 'invoices/' . $filename;
    }

    /**
     * Generate a lightweight report card PDF file.
     */
    public function generateReportPdf(array $reportCard, array $student, array $scores, array $exam): string
    {
        $directory = WRITEPATH . 'report_cards';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = sprintf(
            'report-%s-%s.pdf',
            $reportCard['student_id'],
            $reportCard['exam_id']
        );

        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $lines = $this->buildReportLines($reportCard, $student, $scores, $exam);
        $pdfContent = $this->renderSimplePdf($lines);
        file_put_contents($path, $pdfContent);

        return 'report_cards/' . $filename;
    }

    private function buildInvoiceLines(array $invoice, array $student): array
    {
        $lines = [];
        $y = 770;

        $lines[] = ['Invoice', $y, 18, true];
        $y -= 30;

        $lines[] = ['Invoice #: ' . ($invoice['invoice_number'] ?? 'N/A'), $y];
        $y -= 20;
        $lines[] = ['Issued At: ' . ($invoice['issued_at'] ?? ''), $y];
        $y -= 20;
        $lines[] = ['Due Date: ' . ($invoice['due_date'] ?? ''), $y];
        $y -= 30;

        $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        $lines[] = ['Bill To: ' . $studentName, $y];
        $y -= 20;
        if (!empty($student['parent_email'])) {
            $lines[] = ['Parent Email: ' . $student['parent_email'], $y];
            $y -= 20;
        }
        if (!empty($student['parent_phone'])) {
            $lines[] = ['Parent Phone: ' . $student['parent_phone'], $y];
            $y -= 30;
        } else {
            $y -= 20;
        }

        $amountDue = number_format((float) ($invoice['amount_due'] ?? 0), 2);
        $amountPaid = number_format((float) ($invoice['amount_paid'] ?? 0), 2);
        $balance = number_format((float) ($invoice['amount_due'] ?? 0) - (float) ($invoice['amount_paid'] ?? 0), 2);

        $lines[] = ['Amount Due: $' . $amountDue, $y];
        $y -= 20;
        $lines[] = ['Amount Paid: $' . $amountPaid, $y];
        $y -= 20;
        $lines[] = ['Balance: $' . $balance, $y];
        $y -= 40;

        $lines[] = ['Thank you for learning with EdQuill!', $y];

        return $lines;
    }

    private function buildReportLines(array $reportCard, array $student, array $scores, array $exam): array
    {
        $lines = [];
        $y = 770;

        $lines[] = ['Report Card', $y, 18, true];
        $y -= 30;

        $lines[] = ['Exam: ' . ($exam['name'] ?? ''), $y];
        $y -= 20;
        if (!empty($exam['term'])) {
            $lines[] = ['Term: ' . $exam['term'], $y];
            $y -= 20;
        }
        if (!empty($exam['exam_date'])) {
            $lines[] = ['Exam Date: ' . $exam['exam_date'], $y];
            $y -= 30;
        } else {
            $y -= 20;
        }

        $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        $lines[] = ['Student: ' . $studentName, $y];
        $y -= 20;
        if (!empty($student['grade_name'])) {
            $lines[] = ['Grade: ' . $student['grade_name'], $y];
            $y -= 20;
        }
        if (!empty($student['attendance_percentage'])) {
            $lines[] = ['Attendance: ' . $student['attendance_percentage'] . '%', $y];
            $y -= 30;
        } else {
            $y -= 20;
        }

        $lines[] = ['Subject Scores', $y, 14, true];
        $y -= 25;

        foreach ($scores as $score) {
            $subjectLine = sprintf(
                '%s: %s / %s',
                $score['subject'] ?? 'Subject',
                $score['score'] ?? '0',
                $score['max_score'] ?? '0'
            );
            $lines[] = [$subjectLine, $y];
            $y -= 18;

            if (!empty($score['teacher_comments'])) {
                $lines[] = ['Notes: ' . $score['teacher_comments'], $y];
                $y -= 18;
            }
        }

        $y -= 20;
        $lines[] = ['Generated At: ' . ($reportCard['generated_at'] ?? date('Y-m-d H:i')), $y];
        $y -= 20;
        if (!empty($reportCard['share_token'])) {
            $lines[] = ['Share Code: ' . $reportCard['share_token'], $y];
        }

        return $lines;
    }

    /**
     * Render a single-page PDF with text lines.
     */
    private function renderSimplePdf(array $lines): string
    {
        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n";
        $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";

        $stream = "BT\n";
        foreach ($lines as $line) {
            [$text, $y, $fontSize, $bold] = $line + [null, null, 12, false];
            if ($text === null) {
                continue;
            }

            if ($bold) {
                $stream .= "/F1 18 Tf\n";
            } else {
                $stream .= sprintf("/F1 %d Tf\n", (int) $fontSize);
            }

            $stream .= sprintf("1 0 0 1 60 %.2f Tm (%s) Tj\n", $y, $this->escapePdfText($text));
        }
        $stream .= "ET\n";

        $objects[] = sprintf("5 0 obj << /Length %d >> stream\n%sendstream\nendobj\n", strlen($stream), $stream);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $currentOffset = strlen($pdf);

        foreach ($objects as $object) {
            $offsets[] = $currentOffset;
            $pdf .= $object;
            $currentOffset = strlen($pdf);
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function escapePdfText(?string $text): string
    {
        $replacements = [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
            "\r" => ' ',
            "\n" => ' ',
        ];
        return strtr($text ?? '', $replacements);
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-') ?: 'invoice';
    }
}
