<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your AMIS Enrollment Email</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Verify your email address to continue your AMIS online enrollment.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7f5;margin:0;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dce7df;box-shadow:0 18px 45px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="background:#065f46;padding:30px 24px;text-align:center;">
                            <div style="display:inline-block;width:96px;height:96px;margin:0 auto 14px;text-align:center;">
                                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" width="96" height="96" style="display:block;width:96px;height:96px;object-fit:contain;border:0;outline:none;text-decoration:none;filter:drop-shadow(0 12px 20px rgba(0,0,0,0.16));">
                            </div>
                            <div dir="rtl" style="font-size:18px;line-height:1.6;font-weight:700;color:#d1fae5;margin-bottom:4px;">
                                المدرسة المنورة الإسلامية
                            </div>
                            <div style="font-size:26px;line-height:1.25;font-weight:900;color:#ffffff;">
                                Al Munawwara Islamic School
                            </div>
                            <div style="font-size:13px;line-height:1.5;color:#a7f3d0;margin-top:6px;">
                                AMIS Online Enrollment Portal
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 32px 28px;">
                            <div style="display:inline-block;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:999px;color:#047857;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:7px 12px;margin-bottom:18px;">
                                Email Verification
                            </div>

                            <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;color:#111827;font-weight:800;">
                                Confirm your email address
                            </h1>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#4b5563;">
                                Assalamu alaikum. Please verify your email address so you can continue your online pre-enrollment application.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;margin:0 0 24px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">
                                            Account Email
                                        </div>
                                        <div style="font-size:15px;font-weight:700;color:#111827;word-break:break-word;">
                                            {{ $user->email }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align:center;margin:28px 0;">
                                <a href="{{ $verificationUrl }}" style="display:inline-block;background:#059669;color:#ffffff;text-decoration:none;border-radius:10px;padding:14px 24px;font-size:15px;font-weight:800;box-shadow:0 10px 20px rgba(5,150,105,0.22);">
                                    Verify Email Address
                                </a>
                            </div>

                            <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">
                                If you did not create an AMIS enrollment account, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 32px;background:#f8fafc;border-top:1px solid #e5e7eb;">
                            <p style="margin:0 0 10px;font-size:12px;line-height:1.6;color:#6b7280;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#047857;word-break:break-all;">
                                {{ $verificationUrl }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 24px;text-align:center;background:#ffffff;">
                            <div style="font-size:12px;color:#9ca3af;line-height:1.6;">
                                &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
