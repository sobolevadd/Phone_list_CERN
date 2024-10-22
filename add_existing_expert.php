<?php
include 'dbConnection.php';

if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expertId = $_POST['expert_id'];
    $phone = $_POST['phone'];
    $systemId = $_POST['system_id'];

    // Validate phone number
    if (empty($phone)) {
        die(json_encode([
            'status' => 'error',
            'message' => "Phone number cannot be empty."
        ]));
    }
    if (!preg_match('/^\+?\d*$/', $phone)) {
        die(json_encode([
            'status' => 'error',
            'message' => "Invalid phone number format. It should start with an optional + followed by digits."
        ]));
    }

    // Update the expert's phone number
    $updateExpertStmt = $conn->prepare("UPDATE Expert_person SET Private_phone = ? WHERE Id = ?");
    if (!$updateExpertStmt) {
        die(json_encode([
            'status' => 'error',
            'message' => "Failed to prepare statement: " . $conn->error
        ]));
    }

    $updateExpertStmt->bind_param("si", $phone, $expertId);

    if ($updateExpertStmt->execute()) {
        // Check if the systemId exists
        $checkSystemStmt = $conn->prepare("SELECT Id FROM Expert WHERE Id = ?");
        if (!$checkSystemStmt) {
            die(json_encode([
                'status' => 'error',
                'message' => "Failed to prepare statement: " . $conn->error
            ]));
        }

        $checkSystemStmt->bind_param("i", $systemId);
        $checkSystemStmt->execute();
        $checkSystemStmt->store_result();

        if ($checkSystemStmt->num_rows === 0) {
            die(json_encode([
                'status' => 'error',
                'message' => "Invalid system_id: No matching system found."
            ]));
        }

        // Associate the expert with the system
        $associateStmt = $conn->prepare("INSERT INTO Expert_system_person (system_id, person_id) VALUES (?, ?) 
                                        ON DUPLICATE KEY UPDATE person_id = VALUES(person_id)");
        if (!$associateStmt) {
            die(json_encode([
                'status' => 'error',
                'message' => "Failed to prepare statement: " . $conn->error
            ]));
        }

        $associateStmt->bind_param("ii", $systemId, $expertId);

        if ($associateStmt->execute()) {
            // Fetch the updated expert data
            $fetchExpertStmt = $conn->prepare("
                SELECT 
                    e.name AS expert_name, 
                    e.Private_phone AS phone,
                    e.Id AS expert_id
                FROM Expert_person e
                WHERE e.Id = ?
                LIMIT 1;
            ");
            if (!$fetchExpertStmt) {
                die(json_encode([
                    'status' => 'error',
                    'message' => "Failed to prepare statement: " . $conn->error
                ]));
            }

            $fetchExpertStmt->bind_param("i", $expertId);
            $fetchExpertStmt->execute();
            $result = $fetchExpertStmt->get_result();
            $expert = $result->fetch_assoc();

            $response = [
                'status' => 'success',
                'expert' => $expert,
                'message' => "Expert updated and assigned to the system successfully."
            ];

            echo json_encode($response);
        } else {
            die(json_encode([
                'status' => 'error',
                'message' => "Failed to associate the expert with the system: " . $associateStmt->error
            ]));
        }

        $associateStmt->close();
        $checkSystemStmt->close();
    } else {
        die(json_encode([
            'status' => 'error',
            'message' => "Failed to update expert phone number: " . $updateExpertStmt->error
        ]));
    }

    $updateExpertStmt->close();
    $conn->close();

} else {
    echo json_encode([
        'status' => 'error',
        'message' => "Invalid request."
    ]);
}
?>