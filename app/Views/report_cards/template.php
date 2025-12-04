<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - <?= esc($payload['student']['name'] ?? 'Student') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .report-card {
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #003366;
            padding-bottom: 20px;
        }

        .school-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 24pt;
            font-weight: bold;
            color: #003366;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 18pt;
            color: #666;
            margin-bottom: 10px;
        }

        .term-info {
            font-size: 11pt;
            color: #666;
        }

        .student-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .student-info table {
            width: 100%;
        }

        .student-info td {
            padding: 5px 10px;
        }

        .student-info .label {
            font-weight: bold;
            width: 150px;
        }

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #003366;
            border-bottom: 2px solid #003366;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        table.grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.grades-table th {
            background: #003366;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }

        table.grades-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        table.grades-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .grade-cell {
            font-weight: bold;
            font-size: 13pt;
            text-align: center;
        }

        .comments-box {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            min-height: 100px;
            margin-top: 10px;
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .attendance-item {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .attendance-label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }

        .attendance-value {
            font-size: 20pt;
            font-weight: bold;
            color: #003366;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 10px;
        }

        .summary-item {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
        }

        .summary-label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 16pt;
            font-weight: bold;
            color: #003366;
        }

        .footer {
            margin-top: 40px;
            border-top: 2px solid #003366;
            padding-top: 20px;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 30px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
            padding-top: 5px;
        }

        .signature-label {
            font-size: 10pt;
            color: #666;
        }

        .date {
            text-align: right;
            margin-top: 20px;
            font-size: 10pt;
            color: #666;
        }

        /* Print styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-card {
                padding: 0;
            }

            .section {
                page-break-inside: avoid;
            }
        }

        /* Responsive for preview */
        @media screen and (max-width: 768px) {
            .report-card {
                padding: 20px;
            }

            .attendance-grid,
            .summary-grid,
            .signatures {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="report-card">
        <!-- Header -->
        <div class="header">
            <?php if (!empty($payload['school']['logo'])): ?>
                <img src="<?= esc($payload['school']['logo']) ?>" alt="School Logo" class="school-logo">
            <?php endif; ?>
            <div class="school-name"><?= esc($payload['school']['name'] ?? 'School Name') ?></div>
            <div class="report-title">Report Card</div>
            <div class="term-info">
                <?= esc($payload['term'] ?? 'Term') ?> - <?= esc($payload['academic_year'] ?? 'Academic Year') ?>
            </div>
        </div>

        <!-- Student Info -->
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">Student Name:</td>
                    <td><?= esc($payload['student']['name'] ?? 'N/A') ?></td>
                    <td class="label">Student ID:</td>
                    <td><?= esc($payload['student']['id'] ?? 'N/A') ?></td>
                </tr>
                <?php if (!empty($payload['student']['grade'])): ?>
                <tr>
                    <td class="label">Grade:</td>
                    <td><?= esc($payload['student']['grade']) ?></td>
                    <td class="label">Homeroom:</td>
                    <td><?= esc($payload['student']['homeroom'] ?? 'N/A') ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Sections -->
        <?php if (!empty($payload['sections'])): ?>
            <?php foreach ($payload['sections'] as $section): ?>
                <div class="section">
                    <div class="section-title"><?= esc($section['title'] ?? 'Section') ?></div>

                    <?php if ($section['type'] === 'subjects_grid' && !empty($section['data']['subjects'])): ?>
                        <!-- Subjects Grid -->
                        <table class="grades-table">
                            <thead>
                                <tr>
                                    <?php foreach ($section['data']['columns'] ?? ['subject', 'grade'] as $col): ?>
                                        <th><?= esc(ucfirst(str_replace('_', ' ', $col))) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section['data']['subjects'] as $subject): ?>
                                    <tr>
                                        <?php foreach ($section['data']['columns'] ?? ['subject', 'grade'] as $col): ?>
                                            <td class="<?= $col === 'grade' ? 'grade-cell' : '' ?>">
                                                <?= esc($subject[$col] ?? '-') ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($section['type'] === 'attendance'): ?>
                        <!-- Attendance -->
                        <div class="attendance-grid">
                            <div class="attendance-item">
                                <div class="attendance-label">Days Present</div>
                                <div class="attendance-value"><?= esc($section['data']['days_present'] ?? '0') ?></div>
                            </div>
                            <div class="attendance-item">
                                <div class="attendance-label">Days Absent</div>
                                <div class="attendance-value"><?= esc($section['data']['days_absent'] ?? '0') ?></div>
                            </div>
                            <div class="attendance-item">
                                <div class="attendance-label">Days Tardy</div>
                                <div class="attendance-value"><?= esc($section['data']['days_tardy'] ?? '0') ?></div>
                            </div>
                        </div>

                    <?php elseif ($section['type'] === 'summary'): ?>
                        <!-- Summary -->
                        <div class="summary-grid">
                            <?php foreach ($section['data'] as $key => $value): ?>
                                <div class="summary-item">
                                    <div class="summary-label"><?= esc(ucwords(str_replace('_', ' ', $key))) ?></div>
                                    <div class="summary-value"><?= esc($value) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($section['type'] === 'long_text'): ?>
                        <!-- Comments -->
                        <div class="comments-box">
                            <?= nl2br(esc($section['data']['content'] ?? 'No comments')) ?>
                        </div>

                    <?php elseif ($section['type'] === 'rubric' && !empty($section['data']['items'])): ?>
                        <!-- Rubric -->
                        <table class="grades-table">
                            <thead>
                                <tr>
                                    <th>Criteria</th>
                                    <th style="text-align: center;">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section['data']['items'] as $item): ?>
                                    <tr>
                                        <td><?= esc($item['criteria'] ?? '') ?></td>
                                        <td style="text-align: center;"><?= esc($item['rating'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line">_______________________</div>
                    <div class="signature-label">Teacher Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">_______________________</div>
                    <div class="signature-label">Principal Signature</div>
                </div>
            </div>
            <div class="date">
                Date: <?= date('F j, Y') ?>
            </div>
        </div>
    </div>
</body>
</html>
