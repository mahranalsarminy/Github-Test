<?php
/**
 * Watermark Class
 *
 * @package WallPix
 * @version 1.0.0
 */

class Watermark {
    private $db;
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    /**
     * Load watermark settings from database
     */
    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM watermark_settings WHERE id = 1");
            $stmt->execute();
            $this->settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$this->settings) {
                // Default settings if none exist
                $this->settings = [
                    'type' => 'text',
                    'text_content' => 'WallPix.Top',
                    'text_font' => 'Arial',
                    'text_size' => 24,
                    'text_color' => '#FFFFFF',
                    'text_opacity' => 0.7,
                    'image_path' => null,
                    'position' => 'bottom-right',
                    'padding' => 10,
                    'is_active' => 1,
                    'apply_to_new' => 1,
                    'apply_to_downloads' => 1
                ];
            }
        } catch (Exception $e) {
            error_log('Watermark settings load error: ' . $e->getMessage());
        }
    }
    
    /**
     * Apply watermark to an image
     * 
     * @param string $imagePath Source image path
     * @param string $outputPath Output image path (if null, overwrites source)
     * @return bool Success or failure
     */
    public function applyWatermark($imagePath, $outputPath = null) {
        // If watermark is disabled, return true but don't apply
        if (!$this->settings['is_active']) {
            return true;
        }
        
        // If output path is not specified, overwrite original
        if ($outputPath === null) {
            $outputPath = $imagePath;
        }
        
        try {
            // Get image type
            $imageInfo = getimagesize($imagePath);
            if ($imageInfo === false) {
                return false;
            }
            
            // Create image resource based on type
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $srcImage = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $srcImage = imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    $srcImage = imagecreatefromgif($imagePath);
                    break;
                default:
                    return false; // Unsupported image type
            }
            
            // Preserve transparency for PNG images
            if ($imageInfo[2] === IMAGETYPE_PNG) {
                imagealphablending($srcImage, true);
                imagesavealpha($srcImage, true);
            }
            
            // Apply text watermark
            if ($this->settings['type'] === 'text') {
                $result = $this->applyTextWatermark($srcImage, $imageInfo[0], $imageInfo[1]);
            } else {
                // Apply image watermark
                $result = $this->applyImageWatermark($srcImage, $imageInfo[0], $imageInfo[1]);
            }
            
            // Save the watermarked image
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($srcImage, $outputPath, 90); // 90% quality
                    break;
                case IMAGETYPE_PNG:
                    $result = imagepng($srcImage, $outputPath);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($srcImage, $outputPath);
                    break;
            }
            
            // Free memory
            imagedestroy($srcImage);
            
            return $result;
        } catch (Exception $e) {
            error_log('Watermark application error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Apply text watermark
     */
    private function applyTextWatermark($image, $width, $height) {
        $text = $this->settings['text_content'];
        $fontSize = $this->settings['text_size'];
        $padding = $this->settings['padding'];
        $position = $this->settings['position'];
        
        // Convert hex color to RGB
        $hexColor = str_replace('#', '', $this->settings['text_color']);
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        
        // Create text color with opacity
        $textColor = imagecolorallocatealpha($image, $r, $g, $b, (1 - $this->settings['text_opacity']) * 127);
        
        // Get text dimensions
        $fontPath = __DIR__ . '/../../assets/fonts/arial.ttf'; // Default font path
        $fontAngle = 0; // Horizontal text
        
        // Calculate text dimensions
        $textBox = imagettfbbox($fontSize, $fontAngle, $fontPath, $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        
        // Full-image watermark (tiled)
        if ($position === 'full') {
            // For full coverage, create a tiled pattern
            $angle = -30; // Angle for diagonal pattern
            $spacing = 100; // Space between text instances
            
            for ($x = -$width; $x < $width * 2; $x += $spacing) {
                for ($y = -$height; $y < $height * 2; $y += $spacing) {
                    imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $text);
                }
            }
            
            return true;
        }
        
        // Calculate position
        switch ($position) {
            case 'top-left':
                $x = $padding;
                $y = $textHeight + $padding;
                break;
                
            case 'top-right':
                $x = $width - $textWidth - $padding;
                $y = $textHeight + $padding;
                break;
                
            case 'bottom-left':
                $x = $padding;
                $y = $height - $padding;
                break;
                
            case 'bottom-right':
                $x = $width - $textWidth - $padding;
                $y = $height - $padding;
                break;
                
            case 'center':
                $x = ($width - $textWidth) / 2;
                $y = ($height + $textHeight) / 2;
                break;
                
            default:
                // Default to bottom right
                $x = $width - $textWidth - $padding;
                $y = $height - $padding;
        }
        
        // Add text to image
        return imagettftext($image, $fontSize, $fontAngle, $x, $y, $textColor, $fontPath, $text);
    }
    
    /**
     * Apply image watermark
     */
    private function applyImageWatermark($image, $width, $height) {
        $watermarkPath = $_SERVER['DOCUMENT_ROOT'] . $this->settings['image_path'];
        $padding = $this->settings['padding'];
        $position = $this->settings['position'];
        
        if (!file_exists($watermarkPath)) {
            return false;
        }
        
        // Create watermark resource based on file extension
        $watermarkExt = strtolower(pathinfo($watermarkPath, PATHINFO_EXTENSION));
        
        switch ($watermarkExt) {
            case 'jpg':
            case 'jpeg':
                $watermark = imagecreatefromjpeg($watermarkPath);
                break;
            case 'png':
                $watermark = imagecreatefrompng($watermarkPath);
                break;
            case 'gif':
                $watermark = imagecreatefromgif($watermarkPath);
                break;
            default:
                return false;
        }
        
        // Get watermark dimensions
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);
        
        // Full-image watermark (tiled)
        if ($position === 'full') {
            // For full coverage, tile the watermark with spacing
            $spacing = max($watermarkWidth, $watermarkHeight) * 2;
            
            for ($x = -$watermarkWidth; $x < $width + $watermarkWidth; $x += $spacing) {
                for ($y = -$watermarkHeight; $y < $height + $watermarkHeight; $y += $spacing) {
                    $this->copyWatermarkWithOpacity($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $this->settings['image_opacity']);
                }
            }
            
            imagedestroy($watermark);
            return true;
        }
        
        // Calculate position
        switch ($position) {
            case 'top-left':
                $x = $padding;
                $y = $padding;
                break;
                
            case 'top-right':
                $x = $width - $watermarkWidth - $padding;
                $y = $padding;
                break;
                
            case 'bottom-left':
                $x = $padding;
                $y = $height - $watermarkHeight - $padding;
                break;
                
            case 'bottom-right':
                $x = $width - $watermarkWidth - $padding;
                $y = $height - $watermarkHeight - $padding;
                break;
                
            case 'center':
                $x = ($width - $watermarkWidth) / 2;
                $y = ($height - $watermarkHeight) / 2;
                break;
                
            default:
                // Default to bottom right
                $x = $width - $watermarkWidth - $padding;
                $y = $height - $watermarkHeight - $padding;
        }
        
        // Copy watermark to image with opacity
        $this->copyWatermarkWithOpacity($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $this->settings['image_opacity']);
        
        // Free memory
        imagedestroy($watermark);
        
        return true;
    }
    
    /**
     * Copy watermark with opacity
     */
    private function copyWatermarkWithOpacity($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity) {
        // Creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);
        
        // Set transparency if PNG
        imagealphablending($cut, false);
        imagesavealpha($cut, true);
        
        // Copy relevant section from destination image to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        
        // Copy watermark to the cut resource with opacity
        imagecopymerge($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h, $opacity * 100);
        
        // Copy the cut resource back to the destination image
        imagecopy($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
        
        // Free memory
        imagedestroy($cut);
    }
    
    /**
     * Check if watermark should be applied to new uploads
     */
    public function shouldApplyToNewUploads() {
        return $this->settings['is_active'] && $this->settings['apply_to_new'];
    }
    
    /**
     * Check if watermark should be applied to downloads
     */
    public function shouldApplyToDownloads() {
        return $this->settings['is_active'] && $this->settings['apply_to_downloads'];
    }
}