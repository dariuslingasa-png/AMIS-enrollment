<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Student Enrollment Form - {{ $applicant->full_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-size: 11pt;
            line-height: 1.4;
        }
        .document-wrapper {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border-bottom: 2px solid #059669;
            padding-bottom: 10px;
        }
        .header-logo {
            width: 80px;
            text-align: center;
            vertical-align: middle;
        }
        .header-logo img {
            width: 75px;
            height: auto;
        }
        .header-text {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }
        .header-text h1 {
            margin: 0;
            font-size: 15pt;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text h2 {
            margin: 2px 0 0 0;
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
        }
        .header-text p {
            margin: 2px 0 0 0;
            font-size: 8.5pt;
            color: #475569;
        }
        .header-badge-box {
            width: 110px;
            text-align: right;
            vertical-align: middle;
        }
        .qr-code-img {
            width: 85px;
            height: 85px;
        }

        .doc-title-banner {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
            margin-bottom: 15px;
        }
        .doc-title-banner h3 {
            margin: 0;
            font-size: 12pt;
            font-weight: 800;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-title-banner span {
            font-size: 9pt;
            color: #047857;
            font-weight: 600;
        }

        .photo-info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .student-photo-td {
            width: 120px;
            vertical-align: top;
            padding-right: 15px;
        }
        .student-photo-box {
            width: 110px;
            height: 110px;
            border: 2px dashed #cbd5e1;
            border-radius: 6px;
            text-align: center;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .student-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .section-header {
            background: #059669;
            color: #ffffff;
            font-size: 9.5pt;
            font-weight: 800;
            text-transform: uppercase;
            padding: 5px 10px;
            margin-top: 12px;
            margin-bottom: 6px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9.5pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            text-align: left;
        }
        .data-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            width: 25%;
        }
        .data-table td {
            color: #0f172a;
        }
        .bg-light {
            background: #f8fafc;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 9pt;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
        }
        .sig-line {
            border-top: 1px solid #0f172a;
            margin-top: 40px;
            padding-top: 4px;
            font-weight: 700;
        }

        .footer-note {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="document-wrapper">
        <!-- SCHOOL HEADER -->
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{{ public_path('images/logo.png') }}" alt="AMIS Logo" onerror="this.style.display='none'">
                </td>
                <td class="header-text">
                    <h1>Al Munawwara Islamic School</h1>
                    <h2>OFFICIAL STUDENT ENROLLMENT FORM</h2>
                    <p>Don Julian Rodriguez Avenue, Ma-a, Davao City, Philippines • Tel: (082) 244-0570</p>
                    <p><strong>School ID:</strong> 466150 | <strong>ESC ID:</strong> 1104046</p>
                </td>
                <td class="header-badge-box">
                    @if(isset($qrCodeBase64))
                        <img src="{{ $qrCodeBase64 }}" class="qr-code-img" alt="QR Code">
                    @else
                        <div style="font-size: 8pt; color: #64748b; text-align: center; border: 1px solid #cbd5e1; padding: 10px; border-radius: 4px;">
                            <strong>REF NO:</strong><br>{{ $applicant->amis_student_id ?? 'APP-' . $applicant->id }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- TITLE BANNER -->
        <div class="doc-title-banner">
            <h3>Enrollment Registration • School Year {{ $applicant->school_year ?? '2026-2027' }}</h3>
            <span>Student Type: <strong>{{ strtoupper($applicant->student_type ?? 'NEW') }}</strong> | Mode: <strong>{{ strtoupper($applicant->learning_mode ?? 'FACE TO FACE') }}</strong></span>
        </div>

        <!-- 1. STUDENT PERSONAL INFORMATION -->
        <div class="section-header">1. Student Personal Information</div>
        <table class="data-table">
            <tr>
                <th>Full Name</th>
                <td colspan="3"><strong>{{ $applicant->full_name }}</strong></td>
            </tr>
            <tr>
                <th>LRN (Learner Ref. No.)</th>
                <td>{{ $applicant->lrn ?? 'N/A' }}</td>
                <th>AMIS Student ID</th>
                <td><strong>{{ $applicant->amis_student_id ?? 'PENDING' }}</strong></td>
            </tr>
            <tr>
                <th>Grade Level Applied</th>
                <td><strong>{{ $applicant->grade_level }}</strong></td>
                <th>Gender</th>
                <td>{{ ucfirst($applicant->gender ?? 'N/A') }}</td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td>{{ optional($applicant->date_of_birth)->format('F d, Y') ?? 'N/A' }}</td>
                <th>Place of Birth</th>
                <td>{{ $applicant->place_of_birth ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Religion</th>
                <td>{{ $applicant->religion ?? 'Islam' }}</td>
                <th>Ethnicity / Country</th>
                <td>{{ $applicant->ethnicity ?? 'Filipino' }} ({{ $applicant->country ?? 'Philippines' }})</td>
            </tr>
            <tr>
                <th>Contact Mobile</th>
                <td>{{ $applicant->mobile_number ? ($applicant->mobile_country_code . ' ' . $applicant->mobile_number) : 'N/A' }}</td>
                <th>Email Address</th>
                <td>{{ $applicant->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Complete Residence Address</th>
                <td colspan="3">{{ $applicant->address ?? ($applicant->street_address . ', ' . $applicant->city . ', ' . $applicant->state_province . ' ' . $applicant->postal_code) }}</td>
            </tr>
        </table>

        <!-- 2. PARENT & GUARDIAN DETAILS -->
        <div class="section-header">2. Parent & Guardian Information</div>
        <table class="data-table">
            <tr>
                <th>Father's Name</th>
                <td>{{ trim(($applicant->father_first_name ?? '') . ' ' . ($applicant->father_middle_name ?? '') . ' ' . ($applicant->father_last_name ?? '')) ?: 'N/A' }}</td>
                <th>Father's Occupation</th>
                <td>{{ $applicant->father_occupation ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Mother's Maiden Name</th>
                <td>{{ trim(($applicant->mother_first_name ?? '') . ' ' . ($applicant->mother_middle_name ?? '') . ' ' . ($applicant->mother_last_name ?? '')) ?: 'N/A' }}</td>
                <th>Mother's Occupation</th>
                <td>{{ $applicant->mother_occupation ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Parent Contact Mobile</th>
                <td>{{ $applicant->parent_mobile ? ($applicant->parent_country_code . ' ' . $applicant->parent_mobile) : 'N/A' }}</td>
                <th>Parent Email</th>
                <td>{{ $applicant->parent_email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Parent Residence Address</th>
                <td colspan="3">{{ $applicant->home_address ?? ($applicant->home_street_address . ', ' . $applicant->home_city . ', ' . $applicant->home_state_province) ?: 'Same as Student Address' }}</td>
            </tr>
        </table>

        <!-- 3. MEDICAL & EMERGENCY INFORMATION -->
        <div class="section-header">3. Medical & Emergency Information</div>
        <table class="data-table">
            <tr>
                <th>Emergency Contact Person</th>
                <td><strong>{{ $applicant->emergency_name ?? 'N/A' }}</strong></td>
                <th>Relationship</th>
                <td>{{ $applicant->emergency_relationship ?? 'Parent/Guardian' }}</td>
            </tr>
            <tr>
                <th>Emergency Mobile No.</th>
                <td>{{ $applicant->emergency_phone ?? 'N/A' }}</td>
                <th>Family Physician / Tel</th>
                <td>{{ $applicant->family_physician ?? 'N/A' }} ({{ $applicant->physician_phone ?? 'N/A' }})</td>
            </tr>
            <tr>
                <th>Known Medical Allergies</th>
                <td>{{ $applicant->allergies ?? 'None Reported' }}</td>
                <th>Health Conditions</th>
                <td>{{ $applicant->health_conditions ?? 'None Reported' }}</td>
            </tr>
        </table>

        <!-- SIGNATURES -->
        <table class="signature-table">
            <tr>
                <td>
                    <div class="sig-line">
                        Signature of Student / Applicant<br>
                        <span style="font-weight: 400; font-size: 8pt; color: #64748b;">Date: {{ date('F d, Y') }}</span>
                    </div>
                </td>
                <td>
                    <div class="sig-line">
                        Signature of Parent / Legal Guardian<br>
                        <span style="font-weight: 400; font-size: 8pt; color: #64748b;">Date: {{ date('F d, Y') }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- FOOTER -->
        <div class="footer-note">
            This is an official computer-generated document from Al Munawwara Islamic School (AMIS) Enrollment Management System.<br>
            Document Reference: <strong>{{ $applicant->amis_student_id ?? 'APP-' . $applicant->id }}</strong> | Generated on: {{ date('Y-m-d H:i:s') }}
        </div>
    </div>
</body>
</html>
