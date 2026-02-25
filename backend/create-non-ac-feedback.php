<?php
session_start();
include '../includes/connection.php';
include '../includes/helpers.php';
$station_id=$_SESSION['station_id'];
$stationName = getStationName($station_id); ?>
<?php include 'header.php';
// php debug code
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone to Asia/Kolkata (Indian Standard Time)
date_default_timezone_set('Asia/Kolkata');

// Ensure station id is available for initial page render
$station_id = $_SESSION['station_id'] ?? 0;

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $train_no = trim($_POST['train_no'] ?? '');
    $coach_no = trim($_POST['coach_no'] ?? '');
    $seat_no = intval($_POST['seat_no'] ?? 0);
    $passenger_name = trim($_POST['passenger_name'] ?? '');
    $pnr_number = trim($_POST['pnr_number'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $coach_type = trim($_POST['coach_type'] ?? 'NON-AC');
    $grade = trim($_POST['grade'] ?? '');
    $station_id = $_SESSION['station_id'];
    $date_input = trim($_POST['date'] ?? '');
    
    // Convert datetime-local format (YYYY-MM-DDTHH:MM) to database format (YYYY-MM-DD HH:MM:SS)
    if ($date_input === '') {
        $date = date('Y-m-d H:i:s');
    } else {
        // Replace 'T' with space to convert datetime-local format
        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $date_input);
        if ($dateTime) {
            $date = $dateTime->format('Y-m-d H:i:s');
        } else {
            // Fallback if format is already correct
            $date = str_replace('T', ' ', $date_input);
            // Only add seconds if not already present
            if (substr_count($date, ':') == 1) {
                $date .= ':00';
            }
        }
    }
    
    $question_ids = $_POST['question_id'] ?? [];
    // Ratings come keyed by question id: rating[<qid>] => value
    $ratings = $_POST['rating'] ?? [];
    $verified = 0; // default unverified
    
    // Validate inputs
    if (empty($train_no) || empty($coach_no) || empty($passenger_name) || empty($pnr_number) || empty($phone_number) || empty($grade)) {
        $error_message = 'Please fill all required fields.';
    } else {
        // Ensure each question has a rating
        foreach ($question_ids as $qid) {
            if (!isset($ratings[$qid]) || $ratings[$qid] === '') {
                $error_message = 'Please rate all questions.';
                break;
            }
        }
    }
      $created_by = 'BACKEND';
    if ($error_message === '') {
        // Generate unique 32-char ID for passenger
        $passenger_id = bin2hex(random_bytes(16)); // 32 character hex string
        
        // Insert into OBHS_passenger
        $sql_passenger = "INSERT INTO OBHS_passenger (id, name, ph_number, pnr_number, seat_no, coach_no, train_no, coach_type, station_id, grade, created, verified, created_by) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $mysqli->prepare($sql_passenger)) {
            // Types: id(s), name(s), ph(s), pnr(s), seat(i), coach(s), train(s), coach_type(s), station_id(s), grade(s), created(s), verified(i), created_by(s)
            $stmt->bind_param("ssssisssissis", $passenger_id, $passenger_name, $phone_number, $pnr_number, $seat_no, $coach_no, $train_no, $coach_type, $station_id, $grade, $date, $verified, $created_by);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Insert feedback for each question
                $sql_feedback = "INSERT INTO OBHS_feedback (feed_param, value, passenger_id, created) VALUES (?, ?, ?, ?)";
                $feedback_success = true;
                
                foreach ($question_ids as $qid) {
                    if ($stmt = $mysqli->prepare($sql_feedback)) {
                        $qid_int = (int)$qid;
                        $val = isset($ratings[$qid]) ? (float)$ratings[$qid] : 0.0;
                        $stmt->bind_param("idss", $qid_int, $val, $passenger_id, $date);
                        
                        if (!$stmt->execute()) {
                            $feedback_success = false;
                            $stmt->close();
                            break;
                        }
                        $stmt->close();
                    } else {
                        $feedback_success = false;
                        break;
                    }
                }
                
                if ($feedback_success) {
                    $success_message = 'NON-AC Feedback submitted successfully!';
                } else {
                    $error_message = 'Error saving feedback ratings. Please try again.';
                }
            } else {
                $error_message = 'Error saving passenger data. Please try again.';
                $stmt->close();
            }
        } else {
            $error_message = 'Database error: ' . $mysqli->error;
        }
    }
}
?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <h2>Create NON-AC Feedback</h2>
                <style>
                    .rating-group { display: flex; flex-wrap: wrap; gap: 8px; }
                    .rating-group input[type="radio"] { display: none; }
                    .rating-group label { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; background: #fff; color: #334155; font-size: 13px; }
                    .rating-group input[type="radio"]:checked + label { background: #2563eb; color: #fff; border-color: #2563eb; }
                    .question-text { font-weight: 600; font-size: 14px; color: #0f172a; }
                    .question-sub { color: #475569; font-size: 12px; margin-top: 2px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 10px; vertical-align: top; border-bottom: 1px solid #e2e8f0; }
                    thead th { background: #f8fafc; color: #334155; font-weight: 700; }
                </style>
                
                <?php if ($success_message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form id="nonAcFeedbackForm" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Train No:</label>
                            <select name="train_no" required>
                                <option value="">Select Train No</option>
                                <?php
                                // Fetch train numbers from the database
                                $sql = "SELECT train_no FROM base_fb_target WHERE station = ? ORDER BY train_no ASC";
                                $stmt = $mysqli->prepare($sql);
                                $stmt->bind_param("i", $station_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['train_no'] . '">' . $row['train_no'] . '</option>';
                                }
                                $stmt->close();
                                ?>
                                
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Coach No:</label>
                            <input type="text" name="coach_no" placeholder="Enter Coach No" required>
                        </div>
                        <div class="form-group">
                            <label>Seat No:</label>
                            <input type="text" name="seat_no" placeholder="Enter Seat No" required>
                        </div>
                         <div class="form-group">
                            <label>Paseenger Name:</label>
                            <input type="text" name="passenger_name" placeholder="Enter Paseenger Name" required>
                        </div>
                          
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>PNR Number:</label>
                            <input type="text" name="pnr_number" placeholder="Enter PNR Number" required>
                        </div>
                        
                      
                        <div class="form-group">
                            <label>Date & Time:</label>
                            <input type="datetime-local" name="date" required value="<?php echo date('Y-m-d\TH:i:s'); ?>" max="<?php echo date('Y-m-d\TH:i:s'); ?>" step="1">
                        </div>
                        <div class="form-group">
                            <label>Ph Number:</label>
                            <input type="text" name="phone_number" placeholder="Enter Phone Number" required>
                        </div>
                       
                            <input  type="hidden" name="coach_type" value="NON-AC" readonly>
                    
                         <div class="form-group">
                             <label>Grde</label>
                             <select name="grade" required>
                                 <option value="A">A (Monday)</option>
                                 <option value="B">B (Tuesday)</option>
                                 <option value="C">C (Wednesday)</option>
                                 <option value="D">D (Thursday)</option>
                                 <option value="E">E (Friday)</option>
                                    <option value="F">F (Saturday)</option>
                                    <option value="G">G (Sunday)</option>
                             </select>
                          </div>
                
                      
                      
                    </div>
                    <div class="form-row">
                        <!-- generate table where we show ac question from  OBHS_questions table    -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch NON-AC questions from the database
                                $station_id = $_SESSION['station_id'];
                                
                                // Get NON-AC questions
                                $sql_q = "SELECT id, eng_question, hin_question FROM OBHS_questions WHERE type = 'NON-AC' AND station_id = ? ORDER BY id ASC";
                                $nonac_questions = [];
                                
                                if ($stmt = $mysqli->prepare($sql_q)) {
                                    $stmt->bind_param("i", $station_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $nonac_questions = $result->fetch_all(MYSQLI_ASSOC);
                                    $stmt->close();
                                }
                                
                                // Get marking data (category and value)
                                $sql_m = "SELECT id, category, value FROM OBHS_marking WHERE station_id = ? ORDER BY value DESC";
                                $markings = [];
                                
                                if ($stmt = $mysqli->prepare($sql_m)) {
                                    $stmt->bind_param("i", $station_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $markings = $result->fetch_all(MYSQLI_ASSOC);
                                    $stmt->close();
                                }

                                if (count($nonac_questions) > 0) {
                                    foreach ($nonac_questions as $index => $question) {
                                        $eng_q = !empty($question['eng_question']) ? $question['eng_question'] : '';
                                        $hin_q = !empty($question['hin_question']) ? $question['hin_question'] : '';
                                        $question_text = $eng_q . (!empty($eng_q) && !empty($hin_q) ? ' / ' : '') . $hin_q;
                                        
                                        echo "<tr>";
                                        echo "<td>";
                                        echo "<input type='hidden' name='question_id[]' value='" . htmlspecialchars($question['id']) . "'>";
                                        echo "<div class='question-text'>" . htmlspecialchars($eng_q) . "</div>";
                                        if (!empty($hin_q)) {
                                            echo "<div class='question-sub'>" . htmlspecialchars($hin_q) . "</div>";
                                        }
                                        echo "</td>";
                                        echo "<td>";
                                        echo "<div class='rating-group'>";
                                        // Display marking options with category and value as radio buttons
                                        foreach ($markings as $idx => $marking) {
                                            $inputId = 'q' . $question['id'] . '_r' . $marking['value'];
                                            $display_text = $marking['category'] . ' - ' . $marking['value'];
                                            // Put required on the first radio of the group so the group is required
                                            $requiredAttr = ($idx === 0) ? 'required' : '';
                                            echo "<input type='radio' id='" . htmlspecialchars($inputId) . "' name='rating[" . htmlspecialchars($question['id']) . "]' value='" . htmlspecialchars($marking['value']) . "' " . $requiredAttr . ">";
                                            echo "<label for='" . htmlspecialchars($inputId) . "'>" . htmlspecialchars($display_text) . "</label>";
                                        }
                                        echo "</div>"; // rating-group
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='2' style='text-align:center; color: #999;'>No NON-AC questions available for this station</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        

                    </div>
            
                    <div class="button-group">
                        <button type="submit" class="btn btn-success">Submit Feedback</button>
                       
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
