<?php $pageTitle = "Create Non AC Feedback"; ?>
<?php include 'header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <h2>Create Non AC Feedback</h2>
                <form id="nonAcFeedbackForm" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Train No:</label>
                            <select name="train_no" required>
                                <option value="">Select Train No</option>
                                <option value="12345">12345</option>
                                <option value="12346">12346</option>
                                <option value="12347">12347</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Coach No:</label>
                            <input type="text" name="coach_no" placeholder="Enter Coach No" required>
                        </div>
                        <div class="form-group">
                            <label>Passenger Name:</label>
                            <input type="text" name="passenger_name" placeholder="Enter Passenger Name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ticket Number:</label>
                            <input type="text" name="ticket_number" placeholder="Enter Ticket Number" required>
                        </div>
                        <div class="form-group">
                            <label>Date:</label>
                            <input type="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label>Rating:</label>
                            <select name="rating" required>
                                <option value="">Select Rating</option>
                                <option value="5">Excellent (5)</option>
                                <option value="4">Good (4)</option>
                                <option value="3">Average (3)</option>
                                <option value="2">Poor (2)</option>
                                <option value="1">Very Poor (1)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Feedback Comments:</label>
                            <textarea name="comments" placeholder="Enter feedback details..." required></textarea>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn btn-success">Submit Feedback</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('nonAcFeedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Non AC Feedback submitted successfully!');
            this.reset();
        });
    </script>

<?php include 'footer.php'; ?>
