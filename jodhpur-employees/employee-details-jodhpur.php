<?php
session_start();
include '../includes/connection.php';

// Get employee ID from URL
$employee_id = $_GET['id'] ?? 0;

// Fetch employee data
$employee = null;
$query = "SELECT * FROM base_employees_jodhpur WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $employee = $row;
}
$stmt->close();

if (!$employee) {
    echo "Employee not found";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Biodata Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            width: 80%;
            margin: auto;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        h2, h4 {
            text-align: center;
            margin: 0;
        }
        .photo {
            width: 120px;
            height: 140px;
            border: 1px solid black;
            object-fit: cover;
        }
        td {
            padding: 6px;
            vertical-align: top;
        }
        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 80%;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .bold {
            font-weight: bold;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 30px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 999;
        }
        .print-btn:hover {
            background-color: #0056b3;
        }
        @media print {
            .print-btn {
                display: none;
            }
        }
        .sign-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .left-sign {
            text-align: left;
            font-weight: bold;
        }
        .right-sign {
            text-align: right;
            font-weight: bold;
        }
        
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                width: 100%;
                margin: 0;
                padding: 0;
                font-size: 12px;
                line-height: 1.4;
            }

            table, td {
                border: 1px solid #000;
                border-collapse: collapse;
            }

            .print-btn {
                display: none;
            }

            .photo {
                width: 100px;
                height: 120px;
            }

            h2, h4, p, td {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">Print PDF</button>

    <div class="header">
        <h2>RAKSHAK SECURITAS PVT. LTD.</h2>
        <h4>ADDRESS: RAILWAY STATION NWR JODHPUR (RAJ)</h4>
        <h4>BIODATA FORM</h4>
        <p><strong>APPLICATION – FORMAT FOR EHK / ACCA / OBHS STAFF</strong></p>
    </div>

    <table border="1">
        <tr>
            <td><strong>EMPLOYEE ID NO:</strong></td>
            <td><?php echo htmlspecialchars($employee['name'] ?? 'N/A'); ?></td>
            <td rowspan="5" style="text-align: center;">
                <img src="uploads/<?php echo htmlspecialchars($employee['photo'] ?? 'nophoto.png'); ?>" 
                     class="photo" alt="Photo">
            </td>
        </tr>
        <tr>
            <td><strong>NAME:</strong></td>
            <td><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>FATHER'S NAME:</strong></td>
            <td><?php echo htmlspecialchars($employee['FATHER_NAME'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>MOTHER'S NAME:</strong></td>
            <td><?php echo htmlspecialchars($employee['FORMULA_DOB'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>DATE OF BIRTH:</strong></td>
            <td><?php echo htmlspecialchars($employee['DOB'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>AADHAR NO:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['ADHAR_NO'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>MOBILE NO:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['MOBILE_NO'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>ADDRESS:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['ADDRESH'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>PAN NO:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['PAN_CARD'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>BANK NAME:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['AC_NAME'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>BANK ACCOUNT NO:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['AC_NO'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>IFSC CODE:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['IFSC_CODE'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>POLICE VERIFICATION CERTIFICATE:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['Police_ver'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>POLICE VERIFICATION CERTIFICATE DATE:</strong></td>
            <td colspan="2"><?php echo htmlspecialchars($employee['Police_ver_dt'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>ENCLOSED DOCUMENTS:</strong></td>
            <td colspan="2">Aadhar Card, PAN Card, Bank Pass Book, PVC, Medical, Photo</td>
        </tr>
    </table>

    <p><strong>DECLARATION:</strong><br>
        I <strong><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></strong> hereby declare that the information furnished above is true, complete, and correct to the best of my knowledge and belief. I understand that in the event of my information being found false or incorrect at any stage, my candidature/appointment shall be liable to cancellation/termination without any notice or compensation in lieu thereof.
    </p>

    <table style="width:100%; margin-top:20px;">
    <tr>
        <td style="text-align:left;">
            <strong>Place:</strong> Jodhpur
        </td>
        <td style="text-align:right;">
            <strong>Date:</strong> <?php echo $employee['created_at'] ? date('d/m/Y', strtotime($employee['created_at'])) : 'N/A'; ?>
        </td>
    </tr>
    </table>


    <div class="sign-section">
        <div class="left-sign">
            <p>Authorised Sign</p>
            <p>RAKSHAK SECURITAS PVT. LTD.</p>
        </div>
        <div class="right-sign">
            <p>Signature of Applicant</p>
        </div>
    </div>

</body>
</html>
