<?php

/**
 * 上传工具类
 */

namespace nice;

use nice\traits\InstanceTrait;

class Uploader
{
    use InstanceTrait;

    /**
     * 重组后的格式
     * 'inputName'=>[['name'=>'','type'=>'','tmp_name'=>'','error'=>'','size'=>'','is_uploaded'=>true|false,'extension'=>'jpg|png...']]
     */
    private $FILES = [];

    /**
     * 错误信息
     */
    private $message = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->rebuild();
    }

    /**
     * 重组 $_FILES
     * @return self
     */
    public function rebuild()
    {
        if ($_FILES) {
            foreach ($_FILES as $inputName => $files) {
                if (!isset($files['name']) || !isset($files['type']) || !isset($files['tmp_name']) || !isset($files['error']) || !isset($files['size'])) {
                    // 非正常上传文件
                    continue;
                }
                if (is_array($files['name'])) {
                    //多文件上传
                    foreach ($files as $fieldName => $fieldArr) {
                        foreach ($fieldArr as $fileIndex => $val) {
                            if ($fieldName == 'tmp_name') {
                                $this->FILES[$inputName][$fileIndex]['is_uploaded'] = is_uploaded_file($val);
                            }
                            if ($fieldName == 'name') {
                                $extension =  pathinfo($val, PATHINFO_EXTENSION);
                                $this->FILES[$inputName][$fileIndex]['extension'] = $extension;
                            }
                            $this->FILES[$inputName][$fileIndex][$fieldName] = $val;
                        }
                    }
                } else {
                    //单文件上传
                    $files['is_uploaded'] = is_uploaded_file($files['tmp_name']);
                    $extension = pathinfo($files['name'], PATHINFO_EXTENSION);
                    $files['extension'] = $extension;
                    $this->FILES[$inputName][] = $files;
                }
            }
        }
    }

    /**
     * 检查文件是否为合法上传
     * @param $tmp_name
     * @return bool
     */
    public function isUploaded($tmp_name = '')
    {
        return is_uploaded_file($tmp_name);
    }

    /**
     * 获取某个上传文件信息
     * @param $inputName
     * @param $index
     */
    public function file($inputName, $index = 0)
    {
        return $this->FILES[$inputName][$index] ?? null;
    }

    /**
     * 获取某个文件大小 - 默认第一个
     * @param $inputName
     * @param $index
     */
    public function size($inputName, $index = 0)
    {
        $file = $this->file($inputName,$index);
        return $file['size'] ?? 0;
    }

    /**
     * 获取md5值 - 默认第一个
     */
    public function md5($inputName, $index = 0)
    {
        $file = $this->file($inputName,$index);
        return $file ? md5_file($file['tmp_name']) : '';
    }

    /**
     * 获取sha1值 - 默认第一个
     */
    public function sha1($inputName, $index = 0)
    {
        $file = $this->file($inputName,$index);
        return $file ? sha1_file($file['tmp_name']) : '';
    }

    /**
     * 获取hash值 - 默认第一个
     */
    public function hash($algo, $inputName, $index = 0)
    {
        $file = $this->file($inputName,$index);
        return $file ? hash($algo, $file['tmp_name']) : '';
    }

    /**
     * 移动单个文件
     * @param $inputName 表单名
     * @param $index 指定索引，默认第一个
     * @param $targetDir 目标目录
     * @param $cover 是否覆盖
     * @param $rename 是否重命名, 0:采用原上传文件名 | 1:md5值 | 2:指定文件名
     * @param $fileName 指定文件名,不含扩展名
     * @return bool
     */
    public function move($inputName, $targetDir, $index = 0, $cover = true, $rename = 0, $fileName = null)
    {
        $file = $this->file($inputName,$index);
        if (!$file) {
            return $this->returnError('文件不存在');
        }
        if ($file['is_uploaded'] == false) {
            return $this->returnError('非法上传文件,无法移动');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->returnError($this->codeToMessage($file['error']));
        }
        if ($file['size'] <= 0) {
            return $this->returnError('无效文件，无法移动');
        }
        $targetDir = rtrim($targetDir, '/');
        if ((!file_exists($targetDir) || !is_dir($targetDir)) && (!mkdir($targetDir, 0775, true) || !chmod($targetDir, 0775))) {
            return $this->returnError('目标目录不存在或无权创建');
        }
        if ($rename === 1) {
            // 自动生成文件名
            $md5Name = $this->md5($inputName, $index) . '.' . $file['extension'];
            if (!$md5Name) {
                return $this->returnError('自动生成文件名失败');
            }
            $fileName = $md5Name;
        } elseif ($rename === 0) {
            $fileName = $file['name'];
        } elseif ($rename === 2) {
            if (!$fileName) {
                return $this->returnError('目标文件名不能为空');
            }
            $fileName .= '.' . $file['extension'];
        } else {
            return $this->returnError('rename 参数错误');
        }
        $destination = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        if (!$cover && file_exists($destination)) {
            // 不允许覆盖
            return $this->returnError('存在同名文件，无法移动');
        }
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return true;
        } else {
            return $this->returnError('操作失败');
        }
    }

    /**
     * 批量移动
     * @param $targetDir 目标目录
     * @param $cover 是否覆盖
     * @param $rename 是否重命名, 0:采用原上传文件名 | 1:md5值 | 2:指定文件名
     * @param $fileName 指定文件名时,自动拼接索引到文件名
     * @return array
     */
    public function moveAll($targetDir = '', $cover = true, $rename = 1, $fileName = null)
    {
        $return = [];
        foreach ($this->FILES as $inputName => $files) {
            foreach ($files as $index => $file) {
                $tmpName = $fileName;
                if ($rename == 2) {
                    $tmpName .= $index;
                }
                $return[] = $this->move($inputName, $targetDir, $index, $cover, $rename, $tmpName);
            }
        }
        return $return;
    }

    /**
     * 获取最近一次错误信息
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 返回错误信息
     */
    private function returnError($message = '')
    {
        $this->message = $message;
        return false;
    }

    /**
     * 映射上传错误代码
     * @param $code
     * @return string
     */
    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_OK:
                $message = 'success';
                break;
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
}
