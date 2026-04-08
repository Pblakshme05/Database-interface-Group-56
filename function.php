<?php
include 'configdb.php';

function executePreparedStatement($sql, $params) {
    global $conn;

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }

    if (stripos($sql, "SELECT") === 0) {
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $stmt->execute();
    }

    $stmt->close();
    return $result;
}

#  LOGIN FUNCTION
function adminLogin($email, $password) {
    $sql = "SELECT * FROM Admin WHERE email = ? AND password = ?";
    $params = [$email, $password];

    $result = executePreparedStatement($sql, $params);

    return $result;
}

# 🔥 STUDENT FUNCTIONS
function createStudent($name, $programme) {
    $sql = "INSERT INTO Student (student_name, programme) VALUES (?, ?)";
    return executePreparedStatement($sql, [$name, $programme]);
}

function getStudents() {
    $sql = "SELECT * FROM Student";
    return executePreparedStatement($sql, []);
}

function deleteStudent($id) {
    $sql = "DELETE FROM Student WHERE student_id = ?";
    return executePreparedStatement($sql, [$id]);
}

function updateStudent($id, $name, $programme) {
    $sql = "UPDATE Student SET student_name = ?, programme = ? WHERE student_id = ?";
    return executePreparedStatement($sql, [$name, $programme, $id]);
}
//---------------------------------------------------------------------------------------------------------------------
function createAssessor($name, $email, $hashedPassword) {
    $sql = "INSERT INTO Assessor (assessor_name, email, password) VALUES (?, ?, ?)";
    return executePreparedStatement($sql, [$name, $email, $hashedPassword]);
}

function getAssessors() {
    $sql = "SELECT * FROM Assessor";
    return executePreparedStatement($sql, []);
}

function updateAssessor($id, $name, $email) {
    $sql = "UPDATE Assessor SET assessor_name = ?, email = ? WHERE assessor_id = ?";
    return executePreparedStatement($sql, [$name, $email, $id], "ssi");
}

function deleteAssessor($id) {
    $sql = "DELETE FROM Assessor WHERE assessor_id = ?";
    return executePreparedStatement($sql, [$id], "i");
}

// Get current assessors for a student
function getStudentAssessors($student_name) {
    $sql = "SELECT assessor_name FROM student_assessors WHERE student_name = ?";
    return executePreparedStatement($sql, [$student_name]);
}

// Count current assessors for a student
function countStudentAssessors($student_name) {
    $sql = "SELECT COUNT(*) as total FROM student_assessors WHERE student_name = ?";
    $res = executePreparedStatement($sql, [$student_name]);
    $row = $res->fetch_assoc();
    return (int)$row['total'];
}

// Assign or update 2 assessors for a student
function assignAssessorsToStudent($student_name, $assessor_ids) {
    if (count($assessor_ids) != 2) {
        return false; // enforce exactly 2
    }

    // Delete existing assignments first
    $sql_delete = "DELETE FROM student_assessors WHERE student_name = ?";
    executePreparedStatement($sql_delete, [$student_name]);

    // Insert new 2 assessors
    $sql_insert = "INSERT INTO student_assessors (student_name, assessor_name) VALUES (?, ?)";
    foreach ($assessor_ids as $assessor_id) {
        $sql_assessor = "SELECT assessor_name FROM Assessor WHERE assessor_id = ?";
        $res = executePreparedStatement($sql_assessor, [$assessor_id]);
        $assessor_name = $res->fetch_assoc()['assessor_name'];

        executePreparedStatement($sql_insert, [$student_name, $assessor_name]);
    }

    return true;
}

// Remove all assessors for a student
function removeStudentAssessors($student_name) {
    $sql = "DELETE FROM student_assessors WHERE student_name = ?";
    return executePreparedStatement($sql, [$student_name]);

}

?>

