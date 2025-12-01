<?php
include 'includes/helpers.php';
session_start();
 $station_name = getStationName($_SESSION['station_id']); ?>
<?php 
$passenger_id = isset($_GET['passenger_id']) ? $_GET['passenger_id'] : '';
$station_id = isset($_GET['station_id']) ? $_GET['station_id'] : '';
$train_no = isset($_GET['train_no']) ? $_GET['train_no'] : '';
$coach_no = isset($_GET['coach_no']) ? $_GET['coach_no'] : '';
$grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$seat_no = isset($_GET['seat_no']) ? $_GET['seat_no'] : '';
$phone = isset($_GET['phone']) ? $_GET['phone'] : '';
$pnr_number = isset($_GET['pnr_number']) ? $_GET['pnr_number'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($name." - ".$passenger_id); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .employee-card {
            width: 700px;
            background: white;
            border: 2px solid #000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin:  auto;
        }

        .card-header {
            background: white;
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid #000;
        }

        .card-title {
            color: #000;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 20px;
        }

        .employee-info {
            display: flex;
            gap: 30px;
         
        }

        .employee-photo-container {
            width: 150px;
            height: 180px;
            border: 2px solid #000;
            overflow: hidden;
            flex-shrink: 0;
        }

        .employee-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .employee-basic-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #000;
            min-width: 120px;
        }

        .info-value {
            color: #000;
            font-weight: 500;
        }

        .greeting-text {
            font-size: 13px;
        
            color: #000;
            margin-bottom: 10px;
            text-align: justify;
        }

        .service-table {
            width: 100%;
            border-collapse: collapse;

        }

        .service-table th,
        .service-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
        }

        .service-table th {
            background-color: #f0f0f0;
            font-weight: 700;
            font-size: 12px;
        }

        .service-table td {
            font-size: 11px;
        }

        .rating-cell {
            width: 60px;
        }

        .checkmark {
            color: green;
            font-size: 18px;
            font-weight: bold;
        }

        .crossmark {
            color: red;
            font-size: 18px;
            font-weight: bold;
        }

        .card-footer {
            padding: 20px 30px;
            text-align: center;
            display: flex;
            gap: 20px;
            justify-content: center;
            border-top: 2px solid #000;
        }

        .btn {
            padding: 12px 30px;
            border: 2px solid #000;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #000;
        }

        .btn-print {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .btn-print:hover {
            background: #0056b3;
            border-color: #0056b3;
        }

        .btn-back:hover {
            background: #f0f0f0;
        }

        @media print {
            body {
                background: white;
            }
             @page {
      size: A4 ;
      margin: 0;
   }

            .card-footer,
            .no-print {
                display: none !important;
            }

            .employee-card {
                box-shadow: none;
                page-break-inside: avoid;
                margin: 0;
            }
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen p-4">





    <div class="employee-card">
        <!-- Card Header -->
        <div class="card-header">
            <div class="card-title">FEEDBACK FORM FOR ON BOARD HOUSEKEEPING SERVICES</div>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <div class="greeting-text">
                <strong>Dear Passenger, <?php echo $name; ?></strong><br><br>
                Our endeavor is to provide you the most hygienic On Board Housekeeping Services. Services during 5:00 to 22:00 hrs Feedback: Passengers are requested to give feedback regarding services provided by OBHS staff, in the format given below on your mobile. Based on your Feedback, payment to the contractor will be made. It will help us to serve you better. Kindly spare a few minutes and rate the areas as given in the table below:
            </div>
            <div class="employee-info">
                <div class="row" style=" margin-bottom:15px;">
                    <div class="info-col" style="flex:1; display:flex; align-items:center; gap:10px;">
                        <span class="info-label"><strong>Train:</strong></span>
                        <span class="info-value"><?php echo $train_no; ?></span>
                    </div>
                    <div class="info-col" style="flex:1; display:flex; align-items:center; gap:10px;">
                        <span class="info-label"><strong>Date:</strong></span>
                        <span class="info-value"><?php echo $date_from; ?></span>
                    </div>
                </div>

                <div class="row" style="">
                    <div class="info-col" style="flex:1; display:flex; align-items:center; gap:10px;">
                        <span class="info-label"><strong>Seat:</strong></span>
                        <span class="info-value"><?php echo $seat_no; ?></span>
                    </div>
                    <div class="info-col" style="flex:1; display:flex; align-items:center; gap:10px;">
                        <span class="info-label"><strong>PNR:</strong></span>
                        <span class="info-value"><?php echo $pnr_number; ?></span>
                    </div>
                </div>
            </div>

            <!-- Service Areas Table -->
            <table class="service-table">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 50%;"><?php echo $station_name ?>– Areas of Cleaning / Services</th>
                        <?php $marking_data = get_marking_data($_SESSION['station_id']);
                if ($marking_data) {
                    {
                        foreach ($marking_data as $data) {
                            echo '<th class="badge badge-' . strtolower(preg_replace('/\s+/', '', trim($data['category']))) . ' mr-2">' . htmlspecialchars($data['category']) . '</span>';
                        }
                    }
                }
                ?>
                        
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left;">Cleaning of toilets (including toilet floor, commode pan, wall panels, shelf, mirror, wash basin, disinfection, and provision of deodorant, etc.)</td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="checkmark">✔</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Cleaning of passenger compartment (including cleaning of passenger aisle, vestibule area, Doorway area and doorway wash basin, spraying of air freshener and cleaning of dust bin).</td>
                        <td class="rating-cell"><span class="checkmark">✔</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Collection of garbage from the coach compartments and clearance of dustbins.</td>
                        <td class="rating-cell"><span class="checkmark">✔</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Spraying of Mosquito/Cockroach/ Fly repellent and providing Glue Board whenever required or on demand by passengers.</td>
                        <td class="rating-cell"><span class="checkmark">✔</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Behavior / Response of janitors/supervisor (including hygiene & cleanliness of janitor/Supervisor)</td>
                        <td class="rating-cell"><span class="checkmark">✔</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                        <td class="rating-cell"><span class="crossmark">✖</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Card Footer -->
        <div class="card-footer">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Print
            </button>
            <!-- <button onclick="window.history.back()" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </button> -->
        </div>
    </div>

</body>

</html>
