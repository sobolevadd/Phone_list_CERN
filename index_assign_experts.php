<?php
include 'dbConnection.php';

// Fetch all experts that are NOT associated with the given system
$expertsStmt = $conn->prepare("
    SELECT e.Id AS id, e.name AS name, e.Private_phone AS phone
    FROM Expert_person e
    ORDER BY name ASC
");
$expertsStmt->execute();
$expertsResult = $expertsStmt->get_result();

// Fetch systems associated with each expert
$systemsStmt = $conn->prepare("
    SELECT 
        e.Id AS expert_id,
        esp.System_id
    FROM Expert_person e
    LEFT JOIN Expert_system_person esp ON e.Id = esp.Person_id
");
$systemsStmt->execute();
$systemsResult = $systemsStmt->get_result();
$expertSystems = [];
while ($row = $systemsResult->fetch_assoc()) {
    $expert_id = $row['expert_id'];
    $system_id = $row['System_id'];
    if (!isset($expertSystems[$expert_id])) {
        $expertSystems[$expert_id] = [];
    }
    if ($system_id !== null) {
        $expertSystems[$expert_id][] = $system_id;
    }
}

// Combine expert data with system count
$expertsWithSystemCount = [];
while ($expert = $expertsResult->fetch_assoc()) {
    $id = $expert['id'];
    $expertsWithSystemCount[] = [
        'name' => $expert['name'],
        'phone' => $expert['phone'],
        'id' => $id,
        'system_count' => isset($expertSystems[$id]) ? count($expertSystems[$id]) : 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telephone List Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Telephone List Management</h1>
        <h2 class="text-center mb-4">Assign Systems to the Experts</h2>
        <div class="text-center mb-4 top-buttons">
            <button id="assign-choose-systems-button" class="btn btn-success">Choose Assignment of the Systems</button>
        </div>

        <div class="header-row">
            <div class="cell">Expert</div>
            <div class="cell">Phone</div>
            <div class="cell">Number of Systems</div>
            <div class="cell"></div>
        </div>

        <?php foreach ($expertsWithSystemCount as $expert): ?>
        <div class="data-row">
            <div class="cell cellExp"><?= htmlspecialchars($expert['name']) ?></div>
            <div class="cell cellExp"><?= htmlspecialchars($expert['phone'] ?? '') ?></div>
            <div class="cell cellExp"><?= htmlspecialchars($expert['system_count'] ?? '') ?></div>
            <div class="cell cellExp">
                <a href="#" class="btn btn-success assign_systems-button action-button"
                data-expert_id="<?= htmlspecialchars($expert['id']) ?>"
                data-expert_name="<?= htmlspecialchars($expert['name']) ?>"
                data-expert_phone="<?= htmlspecialchars($expert['phone'] ?? '') ?>">Assign</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Assign Systems to Expert Dialog -->
    <div id="assign_systems-dialog" title="Edit Expert Systems Assignment" style="display:none;">
        <form id="edit-form">
            <div class="form-group">
                <label for="Expert Name">Expert:</label>
                <span id="exp_name"></span>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" class="form-control" pattern="^\+?\d*$" placeholder="Enter phone number">
            </div>
            <div class="form-group">
                <label for="systems">Associated Systems: </label>
                <select id="systems" name="system_ids[]" multiple class="form-control">
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
            <input type="hidden" id="expert_id" name="expert_id">
            <div class="button-container">
                <button type="button" id="assign-all-btn" class="btn btn-primary">Assign All Systems</button>
                <button type="button" id="unassign-all-btn" class="btn btn-secondary">Unassign All Systems</button>
                <button type="button" id="save-only-btn" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $("#assign-choose-systems-button").click(function() {
                window.location.href = 'index.php'; 
            });
            // Initialize Select2
            $("#systems").select2({
                placeholder: "Select systems",
                allowClear: true
            });

            // Assign All Systems button functionality
            $("#assign-all-btn").click(function() {
                if (confirm("Are you sure you want to assign all systems?")) {
                    $("#systems").find('option').prop('selected', true);
                    $("#systems").trigger('change'); // Notify Select2 to update
                    $("#save-only-btn").click();
                    $("#assign_systems-dialog").dialog("close"); // Close the dialog after confirming
                }
            });

            // Unassign All Systems button functionality
            $("#unassign-all-btn").click(function() {
                if (confirm("Are you sure you want to unassign all systems?")) {
                    $("#systems").find('option').prop('selected', false);
                    $("#systems").trigger('change'); // Notify Select2 to update
                    $("#save-only-btn").click();
                    $("#assign_systems-dialog").dialog("close"); // Close the dialog after confirming
                }
            });

            // Open dialog and populate data
            $(".assign_systems-button").click(function(e) {
                e.preventDefault();
                var expertName = $(this).data("expert_name");
                var expertId = $(this).data("expert_id");
                var expertPhone = $(this).data("expert_phone");

                $("#exp_name").text(expertName);
                $("#expert_id").val(expertId);
                $("#phone").val(expertPhone);

                $.ajax({
                    url: 'fetch_expert_systems.php',
                    type: 'GET',
                    data: { expert_id: expertId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }

                        var systemsSelect = $("#systems");
                        systemsSelect.empty(); // Clear previous options

                        $.each(data.systems, function(index, system) {
                            var option = $('<option></option>')
                                .val(system.System_id)
                                .text(system.System_name);

                            if (data.associated_systems.includes(system.System_id)) {
                                option.prop('selected', true);
                            }

                            systemsSelect.append(option);
                        });

                        // Update Select2 to reflect the changes
                        systemsSelect.trigger('change');
                    },
                    error: function(xhr, status, error) {
                        alert("Error fetching systems: " + error);
                    }
                });

                $("#assign_systems-dialog").dialog("open");
            });

            // Save button functionality
            $("#save-only-btn").click(function() {
                $.ajax({
                    url: 'update_expert_phone_system.php',
                    type: 'POST',
                    data: $("#edit-form").serialize(),
                    success: function(response) {
                        if (response === "Expert assignment and phone number updated successfully.") {
                            alert(response);
                            $("#assign_systems-dialog").dialog("close");
                            location.reload();
                        } else {
                            alert("Error: " + response);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("An unexpected error occurred: " + error);
                    }
                });
            });

            // Dialog initialization
            $("#assign_systems-dialog").dialog({
                width: 320,
                autoOpen: false,
                modal: true,
                buttons: { 
                    "Save and Exit": { 
                        text: "Save and Exit",
                        class: "save-exit-button",
                        click: function() {
                            $.ajax({
                                url: 'update_expert_phone_system.php',
                                type: 'POST',
                                data: $("#edit-form").serialize(),
                                success: function(response) {
                                    if (response.trim() === "Expert updated and assigned successfully.") {
                                        alert(response);
                                        $("#assign_systems-dialog").dialog("close");
                                        location.reload();
                                    } else {
                                        alert("Error: " + response);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    alert("Error fetching systems: " + error);
                                }
                            });
                        }
                    },
                    "Cancel": {
                        text: "Cancel",
                        class: "cancel-button", 
                        click: function() {
                            $(this).dialog("close");
                            location.reload();
                        }
                    }
                },
                close: function() {
                    location.reload(); // Reload the page when dialog close button (X) is clicked
                },
                classes: {
                    "ui-dialog": "my-dialog",
                    "ui-dialog-titlebar": "my-dialog-titlebar"
                }
            });
        });
    </script>

</body>
</html>
