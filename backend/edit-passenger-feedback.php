<?php $pageTitle = "Edit Passenger Feedback"; ?>
<?php include 'header.php'; ?>

    <!-- Main Container -->
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <!-- Content Area -->
        <div class="content">
            <div class="content-section">
                <h2>Edit Passenger Feedback</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Search by PNR/Ticket:</label>
                        <input type="text" placeholder="Enter PNR or Ticket Number" id="searchFeedback">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" onclick="searchFeedback()">Search</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Train No</th>
                            <th>Passenger Name</th>
                            <th>PNR/Ticket</th>
                            <th>Date</th>
                            <th>Rating</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                        <tr>
                            <td>001</td>
                            <td>12345</td>
                            <td>John Doe</td>
                            <td>1234567890</td>
                            <td>2025-12-10</td>
                            <td>5</td>
                            <td>AC</td>
                            <td>
                                <button class="action-btn edit-btn">Edit</button>
                                <button class="action-btn delete-btn">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>002</td>
                            <td>12346</td>
                            <td>Jane Smith</td>
                            <td>9876543210</td>
                            <td>2025-12-11</td>
                            <td>4</td>
                            <td>Non AC</td>
                            <td>
                                <button class="action-btn edit-btn">Edit</button>
                                <button class="action-btn delete-btn">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function searchFeedback() {
            const searchValue = document.getElementById('searchFeedback').value;
            if (searchValue) {
                alert('Searching for feedback: ' + searchValue);
            } else {
                alert('Please enter a PNR or Ticket Number');
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-btn')) {
                alert('Edit functionality - Open edit form for this record');
            }
            if (e.target.classList.contains('delete-btn')) {
                if (confirm('Are you sure you want to delete this record?')) {
                    e.target.closest('tr').remove();
                    alert('Record deleted successfully');
                }
            }
        });
    </script>

<?php include 'footer.php'; ?>
