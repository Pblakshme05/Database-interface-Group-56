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

# 🔥 LOGIN FUNCTION
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
    $sql = "INSERT INTO Assessor (name, email, password) VALUES (?, ?, ?)";
    return executePreparedStatement($sql, [$name, $email, $hashedPassword]);
}

function getAssessors() {
    $sql = "SELECT * FROM Assessor";
    return executePreparedStatement($sql, []);
}
?>

