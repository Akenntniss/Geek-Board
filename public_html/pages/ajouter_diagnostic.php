<?php
// Insertion de notification après création de diagnostic
if ($stmt->execute()) {
    // Ajouter la notification
    $message = "Nouveau diagnostic #".$pdo->lastInsertId();
    $pdo->query("INSERT INTO notifications (user_id, type, message) VALUES ('".$_SESSION['user_id']."', 'diagnostic', '".$message."')");
}
?>