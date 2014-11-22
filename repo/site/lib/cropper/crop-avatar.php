<?php //-->

class CropAvatar {
    private $src;
    private $data;
    private $file;
    public  $dst;
    private $type;
    private $extension;
    private $srcDir = '';
    private $dstDir = '';
    private $msg;

    private $tmpImage = null;
    private $status   = true;
    private $error    = '';

    function __construct() {
        $this->srcDir = dirname(__FILE__).'/img/upload';
        $this->dstDir = dirname(__FILE__).'/img/cropped';
    }

    public function cropImage($src, $data, $file) {
        $this->setSrc($src);
        $this->setData($data);

        $response = array();

        if ($this->setFile($file)) {
            if ($this->crop($this->src, $this->dst, $this->data)) {
                $response = array(
                    'status' => true,
                    'msg'    => 'Successfully cropped image',
                    'result' => $this->getResult(),
                    'dst'    => $this->dst);

                return $response;
            }
        }

        $response = array(
            'status' => false,
            'msg'    => $this->error,
            'result' => $this->getResult(),
            'dst'    => $this->dst);

        return $response;
    }

    private function setSrc($src) {
        if (!empty($src)) {
            $type = exif_imagetype($src);

            if ($type) {
                $this->src = $src;
                $this->type = $type;
                $this->extension = image_type_to_extension($type);
                $this->setDst();
            }
        }
    }

    private function setData($data) {
        if (!empty($data)) {
            $this->data = json_decode(stripslashes($data));
        }
    }

    private function setFile($file) {
        // check if upload error
        $errorCode = $file['error'];
        if ($errorCode != UPLOAD_ERR_OK) {
            $this->error = $this->codeToMessage($errorCode);
            return false;
        }
        
        // check if uploaded file is of image type
        $type = exif_imagetype($file['tmp_name']);
        if (!$type) {
            $this->error = 'Please upload image file';
            return false;
        }

        // only allow jpeg or png file
        if ($type != IMAGETYPE_JPEG && $type != IMAGETYPE_PNG) {
            $this->error = 'Please upload image with the following types: JPG and PNG';
            return false;
        }

        $dir = $this->srcDir;
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }

        $extension = image_type_to_extension($type);
        $this->tmpImage = date('YmdHis') . $extension;
        $src = $dir . '/' . $this->tmpImage;

        if (file_exists($src)) {
            unlink($src);
        }

        $result = move_uploaded_file($file['tmp_name'], $src);

        if (!$result) {
            $this->_errors = 'Failed to save file';
            return false;
        }

        $this->src = $src;
        $this->type = $type;
        $this->extension = $extension;
        $this->setDst();

        return true;
    }

    private function setDst() {
        $dir = $this->dstDir;

        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }

        $this->dst = $dir . '/' . date('YmdHis') . $this->extension;
    }

    private function crop($src, $dst, $data) {
        if (!empty($src) && !empty($dst) && !empty($data)) {
            switch ($this->type) {
                case IMAGETYPE_GIF:
                    $src_img = imagecreatefromgif($src);
                    break;

                case IMAGETYPE_JPEG:
                    $src_img = imagecreatefromjpeg($src);
                    break;

                case IMAGETYPE_PNG:
                    $src_img = imagecreatefrompng($src);
                    break;
            }

            if (!$src_img) {
                $this->error = "Failed to read the image file";
                return false;
            }

            $dst_img = imagecreatetruecolor(320, 320);
            $result = imagecopyresampled($dst_img, $src_img, 0, 0, $data->x, $data->y, 320, 320, $data->width, $data->height);

            if ($result) {
                switch ($this->type) {
                    case IMAGETYPE_GIF:
                        $result = imagegif($dst_img, $dst, 99);
                        break;

                    case IMAGETYPE_JPEG:
                        $result = imagejpeg($dst_img, $dst, 99);
                        break;

                    case IMAGETYPE_PNG:
                        $result = imagepng($dst_img, $dst, 99);
                        break;
                }

                if (!$result) {
                    $this->error = "Failed to save the cropped image file";
                    return false;
                }
            } else {
                $this->error = "Failed to crop the image file";
                return false;
            }

            imagedestroy($src_img);
            imagedestroy($dst_img);
        }

        // remove original uploaded image
        unlink($this->srcDir.'/'.$this->tmpImage);
        
        return true;
    }

    private function codeToMessage($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;

            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded';
                break;

            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing a temporary folder';
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk';
                break;

            case UPLOAD_ERR_EXTENSION:
                $message = 'File upload stopped by extension';
                break;

            default:
                $message = 'Unknown upload error';
        }

        return $message;
    }

    public function getResult() {
        return !empty($this->data) ? $this->dst : $this->src;
    }

    public function getMsg() {
        return $this->msg;
    }
}
