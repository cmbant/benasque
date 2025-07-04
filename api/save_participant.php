<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../database/Database.php';
require_once '../utils/ArxivAPI.php';

function resizeImage($sourcePath, $targetPath, $maxWidth = 300, $maxHeight = 300)
{
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }

    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $imageType = $imageInfo[2];

    // Create source image
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    // Handle EXIF orientation for JPEG images
    if ($imageType == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($sourcePath);
        if ($exif && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 2:
                    // Flip horizontally
                    imageflip($sourceImage, IMG_FLIP_HORIZONTAL);
                    break;
                case 3:
                    // Rotate 180 degrees
                    $sourceImage = imagerotate($sourceImage, 180, 0);
                    break;
                case 4:
                    // Flip vertically
                    imageflip($sourceImage, IMG_FLIP_VERTICAL);
                    break;
                case 5:
                    // Rotate 90 degrees counter-clockwise and flip horizontally
                    $sourceImage = imagerotate($sourceImage, -90, 0);
                    imageflip($sourceImage, IMG_FLIP_HORIZONTAL);
                    // Update dimensions after rotation
                    $temp = $sourceWidth;
                    $sourceWidth = $sourceHeight;
                    $sourceHeight = $temp;
                    break;
                case 6:
                    // Rotate 90 degrees clockwise
                    $sourceImage = imagerotate($sourceImage, -90, 0);
                    // Update dimensions after rotation
                    $temp = $sourceWidth;
                    $sourceWidth = $sourceHeight;
                    $sourceHeight = $temp;
                    break;
                case 7:
                    // Rotate 90 degrees clockwise and flip horizontally
                    $sourceImage = imagerotate($sourceImage, 90, 0);
                    imageflip($sourceImage, IMG_FLIP_HORIZONTAL);
                    // Update dimensions after rotation
                    $temp = $sourceWidth;
                    $sourceWidth = $sourceHeight;
                    $sourceHeight = $temp;
                    break;
                case 8:
                    // Rotate 90 degrees counter-clockwise
                    $sourceImage = imagerotate($sourceImage, 90, 0);
                    // Update dimensions after rotation
                    $temp = $sourceWidth;
                    $sourceWidth = $sourceHeight;
                    $sourceHeight = $temp;
                    break;
            }
        }
    }

    // Calculate new dimensions after orientation correction
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $newWidth = round($sourceWidth * $ratio);
    $newHeight = round($sourceHeight * $ratio);

    // Create target image
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and GIF
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize image
    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

    // Save resized image
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($targetImage, $targetPath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($targetImage, $targetPath);
            break;
        case IMAGETYPE_GIF:
            imagegif($targetImage, $targetPath);
            break;
    }

    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($targetImage);

    return true;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'email'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Sanitize and validate data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $emailPublic = isset($_POST['email_public']) && $_POST['email_public'] === '1' ? 1 : 0;
    $interests = trim($_POST['interests'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $arxivLinks = $_POST['arxiv_links'] ?? '[]';
    $isEdit = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';
    $originalEmail = $_POST['original_email'] ?? null;

    // Talk-related fields
    $talkFlash = isset($_POST['talk_flash']) && $_POST['talk_flash'] === '1' ? 1 : 0;
    $talkContributed = isset($_POST['talk_contributed']) && $_POST['talk_contributed'] === '1' ? 1 : 0;
    $talkTitle = trim($_POST['talk_title'] ?? '');
    $talkAbstract = trim($_POST['talk_abstract'] ?? '');

    if (!$email) {
        throw new Exception('Invalid email format');
    }

    // Validate arXiv links JSON
    $arxivArray = json_decode($arxivLinks, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid arXiv links format');
    }

    // Limit to 3 arXiv links
    if (count($arxivArray) > 3) {
        $arxivArray = array_slice($arxivArray, 0, 3);
    }

    // Process arXiv links to fetch titles
    if (!empty($arxivArray)) {
        error_log('Processing ' . count($arxivArray) . ' arXiv links for title fetching...');
        $processedLinks = ArxivAPI::processArxivLinks($arxivArray);
        $arxivLinks = json_encode($processedLinks);
        error_log('Processed arXiv links: ' . $arxivLinks);
    } else {
        $arxivLinks = json_encode([]);
    }

    $db = new Database();
    $photoPath = null;



    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        error_log('Processing photo upload...');
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['photo']['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }

        $fileName = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            // Try to resize image if GD functions are available
            if (function_exists('imagecreatefromjpeg')) {
                $resizedPath = $uploadDir . 'resized_' . $fileName;
                if (resizeImage($targetPath, $resizedPath)) {
                    unlink($targetPath); // Remove original
                    $photoPath = 'uploads/resized_' . $fileName;
                } else {
                    $photoPath = 'uploads/' . $fileName;
                }
            } else {
                // GD not available, use original image
                $photoPath = 'uploads/' . $fileName;
            }
        } else {
            throw new Exception('Failed to upload photo');
        }
    }

    $participantData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'email_public' => $emailPublic,
        'interests' => $interests,
        'description' => $description,
        'arxiv_links' => $arxivLinks,
        'photo_path' => $photoPath,
        'talk_flash' => $talkFlash,
        'talk_contributed' => $talkContributed,
        'talk_title' => $talkTitle,
        'talk_abstract' => $talkAbstract
    ];

    if ($isEdit && $originalEmail) {
        // Update existing participant
        $existing = $db->getParticipantByEmail($originalEmail);
        if (!$existing) {
            throw new Exception('Original participant not found');
        }

        // Keep existing photo if no new photo uploaded
        if (!$photoPath) {
            $participantData['photo_path'] = $existing['photo_path'];
        }

        $success = $db->updateParticipant($originalEmail, $participantData);
    } else {
        // Check if email already exists
        $existing = $db->getParticipantByEmail($email);
        if ($existing) {
            throw new Exception('A participant with this email already exists');
        }

        $success = $db->addParticipant($participantData);
    }

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => $isEdit ? 'Participant updated successfully' : 'Participant added successfully'
        ]);
    } else {
        throw new Exception('Failed to save participant data');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
