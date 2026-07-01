<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Under Maintenance</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: #ffffff;
            max-width: 550px;
            width: 100%;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            text-align: center;
        }

        .icon-container {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }

        /* Maintenance Gear/Wrench SVG Icon */
        .icon {
            width: 80px;
            height: 80px;
            color: #3b82f6; /* Modern Blue */
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 16px;
            letter-spacing: -0.025em;
        }

        p {
            font-size: 1.05rem;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 24px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #eff6ff;
            color: #1d4ed8;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 32px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #3b82f6;
            border-radius: 50%;
            position: relative;
        }

        .pulse-dot::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-color: #3b82f6;
            border-radius: 50%;
            animation: pulse 1.5s infinite ease-in-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }

        hr {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .footer-text {
            font-size: 0.875rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="icon-container">
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>

        <h1>We’ll Be Right Back</h1>
        <p>We are currently performing scheduled system upgrades to improve your experience. The platform will be up and running shortly. Thank you for your patience!</p>
        
        <div class="status-badge">
            <span class="pulse-dot"></span>
            Live Upgrade in Progress
        </div>

        <hr>

        <p class="footer-text">Need urgent assistance? Please contact support.</p>
    </div>

</body>
</html>