<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - <?= esc($student_name ?? 'Student') ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(135deg, #003366 0%, #004d99 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 30px 20px;
        }

        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #333;
        }

        .message-box {
            background: #f9f9f9;
            border-left: 4px solid #003366;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .message-box p {
            margin: 10px 0;
            line-height: 1.8;
        }

        .student-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .student-info table {
            width: 100%;
        }

        .student-info td {
            padding: 8px 0;
            font-size: 14px;
        }

        .student-info .label {
            font-weight: bold;
            color: #003366;
            width: 140px;
        }

        .cta-button {
            display: inline-block;
            background: #003366;
            color: #ffffff !important;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }

        .cta-button:hover {
            background: #004d99;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .footer {
            background: #f4f4f4;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
        }

        .footer p {
            margin: 5px 0;
        }

        .footer a {
            color: #003366;
            text-decoration: none;
        }

        .divider {
            height: 1px;
            background: #ddd;
            margin: 25px 0;
        }

        .highlight {
            color: #003366;
            font-weight: bold;
        }

        /* Mobile responsiveness */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .content {
                padding: 20px 15px;
            }

            .header h1 {
                font-size: 20px;
            }

            .student-info .label {
                width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1><?= esc($school_name ?? 'School Name') ?></h1>
            <p>Report Card Notification</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Dear Parent/Guardian,
            </div>

            <div class="message-box">
                <p>
                    We are pleased to inform you that the <span class="highlight">report card</span> for
                    <span class="highlight"><?= esc($student_name ?? 'your student') ?></span> is now available.
                </p>
            </div>

            <!-- Student and Term Info -->
            <div class="student-info">
                <table>
                    <tr>
                        <td class="label">Student:</td>
                        <td><?= esc($student_name ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td class="label">Term:</td>
                        <td><?= esc($term ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td class="label">Academic Year:</td>
                        <td><?= esc($academic_year ?? 'N/A') ?></td>
                    </tr>
                    <?php if (!empty($issued_date)): ?>
                    <tr>
                        <td class="label">Issued Date:</td>
                        <td><?= esc($issued_date) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php if (!empty($has_pdf_attachment)): ?>
            <p style="margin: 20px 0;">
                Please find the report card attached to this email as a PDF document.
                You can also view it online using the button below.
            </p>
            <?php else: ?>
            <p style="margin: 20px 0;">
                You can view and download the report card by clicking the button below.
            </p>
            <?php endif; ?>

            <!-- CTA Button -->
            <?php if (!empty($portal_link)): ?>
            <div class="button-container">
                <a href="<?= esc($portal_link) ?>" class="cta-button">View Report Card</a>
            </div>
            <?php endif; ?>

            <div class="divider"></div>

            <p style="margin: 20px 0; font-size: 14px;">
                If you have any questions or concerns regarding this report card, please don't hesitate to contact your
                student's teacher or the school office.
            </p>

            <p style="margin: 20px 0; font-size: 14px; color: #666;">
                <strong>Contact Information:</strong><br>
                <?php if (!empty($school_phone)): ?>
                Phone: <?= esc($school_phone) ?><br>
                <?php endif; ?>
                <?php if (!empty($school_email)): ?>
                Email: <?= esc($school_email) ?><br>
                <?php endif; ?>
            </p>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Best regards,<br>
                <strong><?= esc($school_name ?? 'School Administration') ?></strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated message from <?= esc($school_name ?? 'your school') ?>.</p>
            <p>Please do not reply directly to this email.</p>
            <?php if (!empty($school_website)): ?>
            <p><a href="<?= esc($school_website) ?>"><?= esc($school_website) ?></a></p>
            <?php endif; ?>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                &copy; <?= date('Y') ?> <?= esc($school_name ?? 'School') ?>. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
