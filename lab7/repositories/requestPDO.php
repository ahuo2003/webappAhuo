<?php
function insertTutoringRequest($pdo, $studentId, $data) {
    $stmt=$pdo->prepare('INSERT INTO tutoring_requests(student_id,course,topic,preferred_date,preferred_time) VALUES(?,?,?,?,?)');
    $stmt->execute([$studentId,$data['course'],$data['topic'],$data['preferred_date'],$data['preferred_time']]);
    return (int)$pdo->lastInsertId();
}
function findRequest($pdo, $id, $ownerId = null) {
    $sql = 'SELECT r.*, COALESCE(u.full_name,s.student_name) AS student_name,
        COALESCE(u.email,s.student_email) AS student_email FROM tutoring_requests r
        JOIN students s ON r.student_id=s.student_id LEFT JOIN users u ON u.user_id=s.user_id WHERE r.request_id=?';
    $args = [$id];
    if ($ownerId !== null) {
        $sql .= ' AND s.user_id=?';
        $args[] = $ownerId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetch();
}
function listRequests($pdo, $course = '', $status = '', $ownerId = null) {
    $sql='SELECT r.*,COALESCE(u.full_name,s.student_name) AS student_name FROM tutoring_requests r JOIN students s ON r.student_id=s.student_id LEFT JOIN users u ON u.user_id=s.user_id WHERE 1=1';$args=[];
    if($course!==''){$sql.=' AND r.course = ?';$args[]=$course;}
    if($status!==''){$sql.=' AND r.status = ?';$args[]=$status;}
    if ($ownerId !== null) {
        $sql .= ' AND s.user_id=?';
        $args[] = $ownerId;
    }
    $sql.=' ORDER BY r.request_id DESC';$stmt=$pdo->prepare($sql);$stmt->execute($args);return $stmt->fetchAll();
}
function updateOpenRequest($pdo, $id, $data, $ownerId = null)
{
    $sql = "UPDATE tutoring_requests SET course=?,topic=?,preferred_date=?,preferred_time=? WHERE request_id=? AND status='Open'";
    $args = [$data['course'], $data['topic'], $data['preferred_date'], $data['preferred_time'], $id];
    return writeOpenRequest($pdo, $sql, $args, $ownerId);
}

function cancelOpenRequest($pdo, $id, $ownerId = null)
{
    $sql = "UPDATE tutoring_requests SET status='Cancelled' WHERE request_id=? AND status='Open'";
    return writeOpenRequest($pdo, $sql, [$id], $ownerId);
}

function writeOpenRequest($pdo, $sql, $args, $ownerId)
{
    if ($ownerId !== null) {
        $sql .= ' AND student_id IN (SELECT student_id FROM students WHERE user_id=?)';
        $args[] = $ownerId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    return $stmt->rowCount() === 1;
}
