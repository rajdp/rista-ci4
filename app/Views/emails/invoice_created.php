<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= esc($invoice['invoice_number'] ?? 'N/A') ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">Invoice #<?= esc($invoice['invoice_number'] ?? 'N/A') ?></h2>
        
        <p>Dear <?= esc($student['first_name'] ?? 'Student') ?>,</p>
        
        <p>Your invoice has been generated for the following:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Invoice Number:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= esc($invoice['invoice_number'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Due Date:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= esc($invoice['due_date'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Amount Due:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>$<?= number_format((float)($invoice['amount_due'] ?? 0), 2) ?></strong></td>
            </tr>
        </table>
        
        <?php if (!empty($lineItems)): ?>
        <h3 style="color: #2c3e50; margin-top: 30px;">Line Items:</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Description</th>
                    <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lineItems as $item): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= esc($item['description']) ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">$<?= number_format($item['total_cents'] / 100, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <p style="margin-top: 30px;">
            <a href="<?= esc($paymentLink ?? '#') ?>" style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                Pay Now
            </a>
        </p>
        
        <p style="margin-top: 30px; color: #666; font-size: 0.9em;">
            Thank you for learning with EdQuill!<br>
            If you have any questions, please contact us.
        </p>
    </div>
</body>
</html>


