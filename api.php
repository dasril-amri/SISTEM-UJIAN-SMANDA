<?php
// api.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/config/koneksi.php';

$action = $_GET['action'] ?? '';

// Format respon baku
function response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// -------------------------------------------------------------
// 1. AUTENTIKASI (LOGIN SUPERADMIN & GURU)
// -------------------------------------------------------------
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        response('error', 'Username dan password wajib diisi!');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verifikasi password (Mendukung password_hash atau plaintext lama)
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        unset($user['password']); // Hapus password dari objek respon
        response('success', 'Login berhasil!', $user);
    } else {
        response('error', 'Username atau password salah!');
    }
}

// -------------------------------------------------------------
// 2. MANAJEMEN PENGGUNA GURU (KHUSUS SUPER ADMIN)
// -------------------------------------------------------------
if ($action === 'get_teachers') {
    $stmt = $pdo->query("SELECT id, username, nama_lengkap, role, created_at FROM users WHERE role = 'guru' ORDER BY id DESC");
    response('success', 'Data guru berhasil dimuat', $stmt->fetchAll());
}

if ($action === 'save_teacher') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $username = trim($input['username'] ?? '');
    $nama = trim($input['nama_lengkap'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($nama)) {
        response('error', 'Username dan nama lengkap tidak boleh kosong!');
    }

    if ($id) {
        // Update data guru
        if (!empty($password)) {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, password = ? WHERE id = ? AND role = 'guru'");
            $stmt->execute([$username, $nama, $hashedPass, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ? WHERE id = ? AND role = 'guru'");
            $stmt->execute([$username, $nama, $id]);
        }
        response('success', 'Data guru berhasil diperbarui!');
    } else {
        // Tambah guru baru
        if (empty($password)) response('error', 'Password awal wajib diisi!');
        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'guru')");
            $stmt->execute([$username, $hashedPass, $nama]);
            response('success', 'Guru baru berhasil ditambahkan!');
        } catch (PDOException $e) {
            response('error', 'Username sudah digunakan!');
        }
    }
}

if ($action === 'delete_teacher') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'guru'");
        $stmt->execute([$id]);
        response('success', 'Akun guru berhasil dihapus');
    }
}

// -------------------------------------------------------------
// 3. MANAJEMEN PAKET UJIAN
// -------------------------------------------------------------
if ($action === 'get_exams') {
    $grade = $_GET['grade'] ?? 'all';
    
    if ($grade !== 'all') {
        $stmt = $pdo->prepare("SELECT * FROM exams WHERE grade_level = ? ORDER BY id DESC");
        $stmt->execute([$grade]);
    } else {
        $stmt = $pdo->query("SELECT * FROM exams ORDER BY id DESC");
    }
    
    response('success', 'Daftar ujian berhasil diambil', $stmt->fetchAll());
}

if ($action === 'save_exam') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = trim($input['title'] ?? '');
    $grade = $input['grade_level'] ?? 'X';
    $url = trim($input['url'] ?? '');
    $passcode = trim($input['passcode'] ?? '');
    $storage_key = trim($input['storage_key'] ?? '');
    $limit = intval($input['violation_limit'] ?? 2);
    $date = $input['exam_date'] ?? date('Y-m-d');
    $time = $input['exam_time'] ?? date('H:i');
    $block_ctx = $input['block_ctx'] ? 1 : 0;
    $block_dev = $input['block_dev'] ? 1 : 0;
    $force_fs = $input['force_fs'] ? 1 : 0;

    if (empty($title) || empty($url) || empty($storage_key)) {
        response('error', 'Judul, Link Form, dan Kunci Storage wajib diisi!');
    }

    // Cek apakah storage_key sudah ada
    $stmtCheck = $pdo->prepare("SELECT id FROM exams WHERE storage_key = ?");
    $stmtCheck->execute([$storage_key]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        // Update ujian
        $sql = "UPDATE exams SET title=?, grade_level=?, url=?, passcode=?, violation_limit=?, exam_date=?, exam_time=?, block_ctx=?, block_dev=?, force_fs=? WHERE storage_key=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $grade, $url, $passcode, $limit, $date, $time, $block_ctx, $block_dev, $force_fs, $storage_key]);
        response('success', 'Paket ujian berhasil diperbarui!');
    } else {
        // Tambah ujian baru
        $sql = "INSERT INTO exams (title, grade_level, url, passcode, storage_key, violation_limit, exam_date, exam_time, block_ctx, block_dev, force_fs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $grade, $url, $passcode, $storage_key, $limit, $date, $time, $block_ctx, $block_dev, $force_fs]);
        response('success', 'Paket ujian baru berhasil disimpan!');
    }
}

if ($action === 'delete_exam') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
        $stmt->execute([$id]);
        response('success', 'Paket ujian berhasil dihapus');
    }
}

if ($action === 'clear_all_exams') {
    $pdo->query("TRUNCATE TABLE exams");
    response('success', 'Seluruh daftar ujian telah dikosongkan!');
}

response('error', 'Aksi API tidak valid!');