<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: auto; padding: 20px; }
        .header { background-color: #4A90D9; padding: 20px; color: white; text-align: center; }
        .body { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; font-size: 12px; color: #aaa; padding: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Jem8 Trading Co</h2>
        </div>
        <div class="body">
            <p>Hi <strong>{{ $customerName }}</strong>,</p>
            <p>{{ $replyMessage }}</p>
            <p>Thank you for reaching out to us!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Jem8 Trading Co. All rights reserved.</p>
        </div>
    </div>
</body>
</html>