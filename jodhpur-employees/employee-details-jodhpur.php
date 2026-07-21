<?php
session_start();
include '../includes/connection.php';

$employee_id = $_GET['id'] ?? 0;

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

function e($value) {
    return htmlspecialchars($value ?? 'N/A');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee ID Card</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f2f4f8;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 30px;
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            z-index: 999;
        }

        .card-wrapper {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .id-card {
            width: 330px;
            height: 520px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid #0b3d91;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            position: relative;
        }

        .card-header {
            background: linear-gradient(135deg, #0b3d91, #1c75bc);
            color: #fff;
            text-align: center;
            padding: 16px 10px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            line-height: 1.2;
        }

        .card-header p {
            margin: 5px 0 0;
            font-size: 11px;
        }

        .photo-box {
            text-align: center;
            margin-top: 18px;
        }

        .photo {
            width: 125px;
            height: 145px;
            border: 3px solid #0b3d91;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
        }

        .emp-name {
            text-align: center;
            margin: 12px 10px 4px;
            font-size: 20px;
            font-weight: bold;
            color: #0b3d91;
            text-transform: uppercase;
        }

        .designation {
            text-align: center;
            font-size: 13px;
            color: #555;
            margin-bottom: 12px;
        }

        .details {
            padding: 0 22px;
            font-size: 13px;
        }

        .row {
            display: flex;
            border-bottom: 1px dashed #ccc;
            padding: 7px 0;
        }

        .label {
            width: 42%;
            font-weight: bold;
            color: #333;
        }

        .value {
            width: 58%;
            color: #111;
        }

        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: #0b3d91;
            color: #fff;
            text-align: center;
            font-size: 11px;
            padding: 8px;
        }

        .back-content {
            padding: 20px;
            font-size: 13px;
        }

        .back-content h3 {
            text-align: center;
            color: #0b3d91;
            margin-top: 0;
            border-bottom: 2px solid #0b3d91;
            padding-bottom: 8px;
        }

        .address-box {
            min-height: 80px;
            border: 1px solid #ccc;
            padding: 8px;
            margin-top: 6px;
            font-size: 12px;
        }

        .instruction {
            margin-top: 15px;
            font-size: 12px;
            line-height: 1.5;
        }

        .sign-area {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
            font-size: 11px;
            font-weight: bold;
        }

        .sign-box {
            text-align: center;
            width: 45%;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .print-btn {
                display: none;
            }

            .card-wrapper {
                gap: 20px;
                align-items: flex-start;
                justify-content: center;
            }

            .id-card {
                box-shadow: none;
                page-break-inside: avoid;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Print ID Card</button>

<div class="card-wrapper">

    <!-- FRONT SIDE -->
    <div class="id-card">
        <div class="card-header">
            <h2>RAKSHAK SECURITAS PVT. LTD.</h2>
            <p>RAILWAY STATION NWR JODHPUR (RAJ)</p>
        </div>

        <div class="photo-box">
            <img src="/uploads/employee/<?php echo e($employee['photo'] ?? 'nophoto.png'); ?>" class="photo" alt="Employee Photo">
        </div>

        <div class="emp-name"><?php echo e($employee['name']); ?></div>
        <div class="designation">EHK / ACCA / OBHS STAFF</div>

        <div class="details">
            <div class="row">
                <div class="label">Emp ID</div>
                <div class="value"><?php echo e($employee['employee_id']); ?></div>
            </div>

            <div class="row">
                <div class="label">Father</div>
                <div class="value"><?php echo e($employee['FATHER_NAME']); ?></div>
            </div>

            <div class="row">
                <div class="label">DOB</div>
                <div class="value"><?php echo e($employee['DOB']); ?></div>
            </div>

            <div class="row">
                <div class="label">Mobile</div>
                <div class="value"><?php echo e($employee['MOBILE_NO']); ?></div>
            </div>

            <div class="row">
                <div class="label">Aadhar</div>
                <div class="value"><?php echo e($employee['ADHAR_NO']); ?></div>
            </div>
        </div>

        <div class="footer">
            Employee Identity Card
        </div>
    </div>

    <!-- BACK SIDE -->
    <div class="id-card">
        <div class="card-header">
            <h2>IDENTITY CARD</h2>
            <p>Back Side Details</p>
        </div>

        <div class="back-content">
            <h3>Employee Details</h3>

            <div class="row">
                <div class="label">PAN No</div>
                <div class="value"><?php echo e($employee['PAN_CARD']); ?></div>
            </div>

            <div class="row">
                <div class="label">Bank</div>
                <div class="value"><?php echo e($employee['AC_NAME']); ?></div>
            </div>

            <div class="row">
                <div class="label">A/C No</div>
                <div class="value"><?php echo e($employee['AC_NO']); ?></div>
            </div>

            <div class="row">
                <div class="label">IFSC</div>
                <div class="value"><?php echo e($employee['IFSC_CODE']); ?></div>
            </div>

            <div class="row">
                <div class="label">PVC</div>
                <div class="value"><?php echo e($employee['Police_ver']); ?></div>
            </div>

            <p><strong>Address:</strong></p>
            <div class="address-box">
                <?php echo e($employee['ADDRESH']); ?>
            </div>

            <div class="instruction">
                <strong>Instructions:</strong><br>
                This card is property of RAKSHAK SECURITAS PVT. LTD.
                If found, please return to the company office.
            </div>

            <p style="margin-top:15px;">
                <strong>Issue Date:</strong>
                <?php echo !empty($employee['created_at']) ? date('d/m/Y', strtotime($employee['created_at'])) : 'N/A'; ?>
            </p>

            <div class="sign-area">
                <div class="sign-box">
                    Authorised Sign
                </div>
                <div class="sign-box">
                    Employee Sign
                </div>
            </div>
        </div>

        <div class="footer">
            RAKSHAK SECURITAS PVT. LTD.
        </div>
    </div>

</div>

</body>
</html>