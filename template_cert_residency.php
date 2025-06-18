<?php
// templates/template_cert_residency.php

// This is the template for the Certificate of Residency.
// It will be included by view_certificate.php and populated with data.

// Dummy data for testing the template directly
// In production, these variables will be populated from view_certificate.php
if (!isset($certificate_data)) {
    $certificate_data = [
        'resident_name' => 'JUAN DELA CRUZ',
        'resident_civil_status' => 'Single',
        'resident_address' => 'Purok Calamansi, Barangay Central Glad',
        'day' => date('jS'),
        'month' => date('F'),
        'year' => date('Y'),
        'punong_barangay' => 'HON. VERNON E. PAPELERA'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Residency</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Arial+Black&display=swap');

        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        .certificate-container {
            width: 8.5in;
            height: 11in;
            margin: auto;
            padding: 0.75in;
            box-sizing: border-box;
            position: relative;
            border: 2px solid black;
            display: flex;
            flex-direction: column;
            background-color: white;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            opacity: 0.1;
            width: 450px;
        }

        .certificate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            margin-bottom: 20px;
            z-index: 2;
        }

        .header-logo {
            width: 90px;
            height: 90px;
        }

        .header-text {
            flex-grow: 1;
        }
        
        .header-text p {
            margin: 0;
            line-height: 1.2;
            font-size: 11pt;
        }
        
        .header-text p strong {
            font-size: 12pt;
        }

        .office-title {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            margin-top: 10px;
            margin-bottom: 5px;
            z-index: 2;
        }

        .header-line {
            border: 0;
            border-top: 2px solid black;
            margin-bottom: 20px;
            z-index: 2;
        }

        .certificate-title {
            text-align: center;
            font-family: 'Arial Black', Gadget, sans-serif;
            font-size: 26pt;
            letter-spacing: 2px;
            margin-bottom: 30px;
            z-index: 2;
        }

        .certificate-body {
            text-align: justify;
            line-height: 2;
            font-size: 12pt;
            z-index: 2;
            flex-grow: 1;
        }

        .certificate-body .to-whom {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .certificate-body p {
            margin: 20px 0;
            text-indent: 40px;
        }

        .editable {
            padding: 0 5px;
            text-decoration: underline;
            font-weight: bold;
        }
        
        .certificate-footer {
            margin-top: auto; /* Pushes footer to the bottom */
            z-index: 2;
            display: flex;
            justify-content: flex-start; /* Align to the left instead of right */
            padding-left: 0.5in; /* Change from padding-right to padding-left */
            line-height: 1.5;
        }

        .signature-section {
            text-align: center;
            width: 250px; /* Fixed width for signature area */
            margin-top: 20px;
        }

        .certified-by {
            font-size: 12pt;
            margin-bottom: 40px; /* Space for actual signature */
            text-align: left;
        }

        .punong-barangay .name {
            font-weight: bold;
            font-size: 13pt;
            border-bottom: 1px solid black; /* Signature line */
            padding-bottom: 5px;
            margin-bottom: 5px;
            min-height: 25px;
        }

        .punong-barangay .title {
            font-size: 12pt;
        }

        @media print {
            body {
                background-color: #fff;
            }
            .certificate-container {
                border: none;
                margin: 0;
                width: 100%;
                height: auto;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
            /* Make contenteditable fields non-editable and remove focus outline for printing */
            .editable {
                border: none;
                outline: none;
                -webkit-user-modify: read-only;
                -moz-user-modify: read-only;
                user-modify: read-only;
            }
        }
    </style>
</head>
<body>

    <div class="certificate-container" id="printable-area">
        <!-- Watermark -->
        <img src="<?php echo html_escape($certificate_data['barangay_logo_path'] ?? 'images/logo2.jfif'); ?>" class="watermark" alt="Watermark">

        <!-- Header -->
        <div class="certificate-header">
            <img src="<?php echo html_escape($certificate_data['municipality_logo_path'] ?? 'images/logo.jfif'); ?>" class="header-logo" alt="Municipal Logo">
            <div class="header-text">
                <p>Republic of the Philippines</p>
                <p>REGION XII</p>
                <p>Province of Cotabato</p>
                <p>Municipality of Midsayap</p>
                <p><strong>Barangay Central Glad</strong></p>
            </div>
            <img src="<?php echo html_escape($certificate_data['barangay_logo_path'] ?? 'images/logo2.jfif'); ?>" class="header-logo" alt="Barangay Logo">
        </div>

        <div class="office-title">
            OFFICE OF THE BARANGAY CAPTAIN
        </div>
        <hr class="header-line">

        <!-- Title -->
        <div class="certificate-title">
            CERTIFICATE OF RESIDENCY
        </div>

        <!-- Body -->
        <div class="certificate-body">
            <div class="to-whom">
                TO WHOM IT MAY CONCERN:
            </div>

            <p>
                THIS IS TO CERTIFY that <span class="editable" contenteditable="true"><?php echo htmlspecialchars(strtoupper($certificate_data['resident_name'])); ?></span> of legal age,
                <span contenteditable="true"><?php echo htmlspecialchars(strtolower($certificate_data['resident_civil_status'])); ?></span>, Filipino citizen, is a bonafide
                resident of Brgy. Central Glad, Midsayap, Cotabato.
            </p>

            <p>
                This further certifies that based on the records of this office, he/she has been
                residing at <span contenteditable="true"><?php echo htmlspecialchars($certificate_data['resident_address']); ?></span>, Midsayap, Cotabato.
            </p>
            
            <p>
                This Certification is being issued upon his/her request of the above-named
                person for whatever legal purpose it may serve.
            </p>

            <p>
                Issued this <span class="editable" contenteditable="true"><?php echo htmlspecialchars($certificate_data['day']); ?></span> day of <span class="editable" contenteditable="true"><?php echo htmlspecialchars($certificate_data['month']); ?></span>, <span contenteditable="true"><?php echo htmlspecialchars($certificate_data['year']); ?></span>, at the office of the Barangay
                Captain, Barangay Central Glad, Midsayap, Cotabato.
            </p>
        </div>

        <!-- Footer -->
        <div class="certificate-footer">
            <div class="signature-section">
                <div class="certified-by">
                    Certified by:
                </div>
                <div class="punong-barangay">
                    <div class="name">
                        <?php echo htmlspecialchars($certificate_data['punong_barangay']); ?>
                    </div>
                    <div class="title">Punong Barangay</div>
                </div>
            </div>
        </div>

    </div>

</body>
</html> 