<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$message = "";
$message_class = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['assignment_file'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $file = $_FILES['assignment_file'];
    
    $allowed_extensions = ['pdf', 'png', 'jpg', 'jpeg'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $message = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
        $message_class = "alert-danger";
    } elseif ($file['error'] !== 0) {
        $message = "An error occurred during file upload.";
        $message_class = "alert-danger";
    } else {
        $unique_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $target_directory = 'uploads/' . $unique_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_directory)) {
            $insert_query = "INSERT INTO submissions (assignment_id, student_id, file_path, status) VALUES ($assignment_id, $student_id, '$target_directory', 'submitted')";
            if (mysqli_query($conn, $insert_query)) {
                $message = "Success! Your assignment has been securely uploaded.";
                $message_class = "alert-success";
            } else {
                $message = "Database processing configuration error.";
                $message_class = "alert-danger";
            }
        } else {
            $message = "Failed to save the file to the uploads folder.";
            $message_class = "alert-danger";
        }
    }
}

$sql = "SELECT a.*, c.Course_Name FROM assignments a LEFT JOIN courses c ON a.course_id = c.Id OR a.course_id = c.id ORDER BY a.due_date ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments Panel | Forces Academy LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { min-width: 260px; max-width: 260px; background-color: #1e3a8a; min-height: 100vh; }
        .sidebar .nav-link { color: rgba(255,255,255,0.75); font-weight: 500; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: block; padding: 10px 15px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: rgba(255,255,255,0.15); }
        .main-content { width: 100%; overflow-y: auto; }
        .assignment-card { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s; }
        .assignment-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="d-flex text-start">
        <nav class="sidebar p-3 d-flex flex-column sticky-top">
            <div class="text-white text-center py-3 mb-4 border-bottom border-secondary">
                <h2 class="h5 fw-bold mb-0">Forces Academy</h2>
                <span class="small opacity-50">Student Workspace</span>
            </div>
            <ul class="nav nav-pills flex-column mb-auto">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="courses.php" class="nav-link">My Courses</a></li>
                <li><a href="assignments.php" class="nav-link active">Assignments</a></li>
                <li><a href="results.php" class="nav-link">My Results</a></li>
                <li><a href="notices.php" class="nav-link">Notices</a></li>
            </ul>
            <div class="pt-3 border-top border-secondary">
                <a href="logout.php" class="btn btn-outline-danger w-100 btn-sm rounded-2">Logout Portal</a>
            </div>
        </nav>

        <div class="main-content">
            <header class="navbar navbar-expand navbar-light bg-white shadow-sm p-3 mb-4">
                <div class="container-fluid"><span class="navbar-text fw-medium text-dark">Course Evaluation Framework</span></div>
            </header>

            <main class="container-fluid px-4">
                <div class="mb-4">
                    <h1 class="h3 fw-bold text-dark mb-1">Academic Assignments</h1>
                    <p class="text-muted small">Upload your files and keep track of pending tasks dynamically.</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $message_class; ?> alert-dismissible fade show rounded-3" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($assignment = mysqli_fetch_assoc($result)): ?>
                            <?php
                            $assign_id = $assignment['id'] ?? $assignment['Id'];
                            $course_name = $assignment['Course_Name'] ?? $assignment['course_name'] ?? 'General Course';
                            
                            $check_query = "SELECT status FROM submissions WHERE assignment_id = $assign_id AND student_id = $student_id LIMIT 1";
                            $check_result = mysqli_query($conn, $check_query);
                            $is_submitted = ($check_result && mysqli_num_rows($check_result) > 0);
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="card assignment-card p-4 bg-white h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-2 small fw-semibold"><?php echo htmlspecialchars($course_name); ?></span>
                                            <span class="small text-danger fw-semibold">Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></span>
                                        </div>
                                        <h2 class="h5 fw-bold text-dark mb-2"><?php echo htmlspecialchars($assignment['title']); ?></h2>
                                        <p class="text-secondary small mb-4"><?php echo htmlspecialchars($assignment['description']); ?></p>
                                    </div>
                                    <div class="pt-3 border-top border-light">
                                        <?php if ($is_submitted): ?>
                                            <div class="w-100 text-center py-2 bg-success-subtle text-success rounded-3 fw-bold small">✓ Assignment Successfully Submitted</div>
                                        <?php else: ?>
                                            <form action="assignments.php" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assign_id; ?>">
                                                <input type="file" name="assignment_file" class="form-control form-control-sm rounded-3" required>
                                                <button type="submit" class="btn btn-primary btn-sm px-4 rounded-3 fw-medium">Submit</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card text-center p-5 border-0 shadow-sm bg-white rounded-3">
                                <div class="fs-1 mb-2">📁</div>
                                <h3 class="h5 fw-bold text-dark">No Current Assignments Found</h3>
                                <p class="text-muted small mb-0">You are completely caught up with your classes.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>