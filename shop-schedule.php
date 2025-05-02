<?php
require_once 'auth_check.php';
require_once 'db.php';  // Include database connection
require_once 'AppointmentService.php';  // Include AppointmentService

// Create AppointmentService instance
$appointmentService = new AppointmentService($pdo);

// Handle AJAX request for appointments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_appointments') {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $locationIds = isset($_POST['location_ids']) ? $_POST['location_ids'] : [];
    
    // If "all" is selected, get all location IDs
    if (in_array('all', $locationIds)) {
        $stmt = $pdo->query("SELECT id FROM locations ORDER BY id");
        $locationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    try {
        $appointments = $appointmentService->getAppointmentsForRange(
            $startDate,
            $endDate,
            ['location_id' => $locationIds]
        );
        
        echo json_encode([
            'success' => true,
            'data' => $appointments
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Ensure jQuery Loads First -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" data-cfasync="false"></script>

    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="author" content="Codescandy" />

    <!-- Favicon icon -->
    <link rel="shortcut icon" type="image/x-icon" href="dashui/assets/images/brand/logo/idnj_logo_small.png" />

    <!-- Color modes -->
    <script src="/dashui/assets/js/vendors/color-modes.js"></script>

    <!-- Libs CSS -->
    <link href="/dashui/assets/libs/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    <link href="/dashui/assets/libs/simplebar/dist/simplebar.min.css" rel="stylesheet" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="/dashui/assets/css/theme.min.css">

    <title>Shop Schedule | IDNJ</title>
    

</head>

<body>
<?php include 'navigation.php'; ?>

<div id="app-content">
    <div class="app-content-area ps-10">
        <div class="container-fluid">
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-12">
                        <!-- Page header -->
                        <div class="d-flex align-items-center mb-5">
                            <div>
                                <h3 class="mb-0 fw-bold">Shop Schedule</h3>
                                <p class="text-muted mb-0">Location: <?php echo htmlspecialchars($_SESSION['location_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Picker Card -->
                <div class="row">
                    <div class="col-12 ps-0 pe-0">
                        <div class="card">
                            <div class="card-body">
                                <form id="dateFilterForm">
                                    <div class="row justify-content-center">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="datePicker" class="form-label">Select Date or Date Range</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="datePicker" placeholder="Select date or date range">
                                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="locationSelect" class="form-label">Select Location</label>
                                                <select class="form-select" id="locationSelect" multiple>
                                                    <option value="all">All Shops</option>
                                                    <?php
                                                    // Fetch locations from database
                                                    $locations_query = "SELECT id, location_name FROM locations ORDER BY location_name";
                                                    $locations_stmt = $pdo->prepare($locations_query);
                                                    $locations_stmt->execute();
                                                    while ($location = $locations_stmt->fetch(PDO::FETCH_ASSOC)) {
                                                        echo '<option value="' . htmlspecialchars($location['id']) . '">' . 
                                                             htmlspecialchars($location['location_name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-primary w-100 mt-3" onclick="applyDateFilter()">
                                                <i class="bi bi-list me-2"></i>Submit
                                            </button>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-secondary w-100 mt-3" onclick="window.location.reload()">
                                                <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Flatpickr CSS -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <!-- Add Flatpickr JS -->
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                <!-- Add Select2 CSS -->
                <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
                <!-- Add Select2 JS -->
                <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

                <!-- Appointments Tables Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div id="appointmentsTables">
                            <!-- Tables will be dynamically added here -->
                        </div>
                    </div>
                </div>

                <script>
                // Define applyDateFilter function globally
                function applyDateFilter() {
                    const selectedDates = document.getElementById('datePicker').value;
                    const selectedLocations = $('#locationSelect').val() || [];
                    
                    if (!selectedDates) {
                        alert('Please select a date range');
                        return;
                    }

                    if (selectedLocations.length === 0) {
                        alert('Please select at least one location');
                        return;
                    }

                    // Split the date range into start and end dates
                    const [startDate, endDate] = selectedDates.split(' to ');
                    
                    // Format dates for API (MM/DD/YYYY to YYYY-MM-DD)
                    const formatDate = (dateStr) => {
                        const [month, day, year] = dateStr.split('/');
                        // Ensure month and day are two digits
                        const pad = (num) => num.toString().padStart(2, '0');
                        return `${year}-${pad(month)}-${pad(day)}`;
                    };

                    const formattedStartDate = formatDate(startDate);
                    const formattedEndDate = endDate ? formatDate(endDate) : formattedStartDate;

                    // Create the request data object
                    const requestData = {
                        action: 'get_appointments',
                        start_date: formattedStartDate,
                        end_date: formattedEndDate,
                        location_ids: selectedLocations
                    };

                    // Log the request data in a readable format
                    console.log('Sending to getAppointmentsForRange:', JSON.stringify(requestData, null, 2));

                    // Make AJAX call to PHP directly
                    $.ajax({
                        url: 'shop-schedule.php',
                        method: 'POST',
                        data: requestData,
                        success: function(response) {
                            console.log('Server Response:', response);
                            try {
                                // Parse the response if it's a string
                                const parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;
                                
                                if (parsedResponse.success) {
                                    // Clear input fields after successful response
                                    document.getElementById('datePicker').value = '';
                                    $('#locationSelect').val(null).trigger('change.select2');
                                    
                                    displayAppointments(parsedResponse.data);
                                } else {
                                    alert('Error fetching appointments: ' + parsedResponse.message);
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                alert('Error processing response');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            console.error('Response:', xhr.responseText);
                            alert('Error fetching appointments');
                        }
                    });
                }

                // Define displayAppointments function globally
                function displayAppointments(appointments) {
                    const container = document.getElementById('appointmentsTables');
                    container.innerHTML = ''; // Clear existing content

                    if (!appointments || appointments.length === 0) {
                        container.innerHTML = '<div class="alert alert-info">No appointments found for the selected date range.</div>';
                        return;
                    }

                    // Group appointments by location
                    const groupedAppointments = {};
                    appointments.forEach(appointment => {
                        const locationName = appointment.location_name || 'Unknown Location';
                        if (!groupedAppointments[locationName]) {
                            groupedAppointments[locationName] = [];
                        }
                        groupedAppointments[locationName].push(appointment);
                    });

                    // Create a card for each location
                    Object.entries(groupedAppointments).forEach(([locationName, locationAppointments]) => {
                        // Create card
                        const card = document.createElement('div');
                        card.className = 'card mb-3';

                        // Create card header
                        const cardHeader = document.createElement('div');
                        cardHeader.className = 'card-header bg-white';
                        cardHeader.setAttribute('role', 'button');
                        cardHeader.setAttribute('data-bs-toggle', 'collapse');
                        cardHeader.setAttribute('data-bs-target', `#location-${locationName.replace(/\s+/g, '-').toLowerCase()}`);
                        cardHeader.setAttribute('aria-expanded', 'false');
                        cardHeader.setAttribute('aria-controls', `location-${locationName.replace(/\s+/g, '-').toLowerCase()}`);

                        // Add header content
                        cardHeader.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">${locationName}</h5>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        `;
                        card.appendChild(cardHeader);

                        // Create collapse div
                        const collapseDiv = document.createElement('div');
                        collapseDiv.className = 'collapse';
                        collapseDiv.id = `location-${locationName.replace(/\s+/g, '-').toLowerCase()}`;

                        // Create card body
                        const cardBody = document.createElement('div');
                        cardBody.className = 'card-body';

                        // Create table
                        const table = document.createElement('table');
                        table.className = 'table table-hover';
                        
                        // Create table header
                        const thead = document.createElement('thead');
                        thead.innerHTML = `
                            <tr>
                                <th style="width: 15%">Date</th>
                                <th style="width: 15%">Time</th>
                                <th style="width: 15%">Client ID</th>
                                <th style="width: 30%">Name</th>
                                <th style="width: 25%">Appointment Type</th>
                            </tr>
                        `;
                        table.appendChild(thead);

                        // Create table body
                        const tbody = document.createElement('tbody');
                        locationAppointments.forEach(appointment => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td style="width: 15%">${appointment.date_formatted}</td>
                                <td style="width: 15%">${appointment.time_formatted}</td>
                                <td style="width: 15%">
                                    <a href="client_detail.php?id=${appointment.customer_id}" class="text-inherit">
                                        ${appointment.customer_id}
                                    </a>
                                </td>
                                <td style="width: 30%">
                                    <div class="lh-1">
                                        <h5 class="mb-0">
                                            <a href="client_detail.php?id=${appointment.customer_id}" class="text-inherit">
                                                ${appointment.customer_name}
                                            </a>
                                        </h5>
                                    </div>
                                </td>
                                <td style="width: 25%">${appointment.appointment_type}</td>
                            `;
                            tbody.appendChild(row);
                        });
                        table.appendChild(tbody);

                        // Add table to card body
                        cardBody.appendChild(table);
                        collapseDiv.appendChild(cardBody);
                        card.appendChild(collapseDiv);

                        // Add card to container
                        container.appendChild(card);
                    });
                }

                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Flatpickr
                    flatpickr("#datePicker", {
                        mode: "range",
                        dateFormat: "m/d/Y",
                        minDate: "today",
                        allowInput: true,
                        onChange: function(selectedDates, dateStr, instance) {
                            console.log('Selected dates:', selectedDates);
                        }
                    });

                    // Initialize Select2 for location dropdown
                    const $locationSelect = $('#locationSelect');
                    $locationSelect.select2({
                        theme: 'bootstrap-5',
                        placeholder: "Select locations",
                        allowClear: true,
                        closeOnSelect: false,
                        width: '100%',
                        templateSelection: function(data) {
                            return $('<span class="small">' + data.text + '</span>');
                        }
                    });

                    // Start with empty selection
                    $locationSelect.val(null).trigger('change.select2');

                    // Handle "All Shops" selection
                    $locationSelect.on('change.select2', function(e) {
                        const selectedValues = $(this).val() || [];
                        
                        // If "All Shops" is selected, deselect other options
                        if (selectedValues.includes('all')) {
                            if (selectedValues.length > 1) {
                                $(this).val('all').trigger('change.select2');
                            }
                        }
                    });
                });
                </script>

            </div>
        </div>
            </div>
        </div>

<?php include 'footer.php'; ?>