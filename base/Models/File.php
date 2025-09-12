<?php

namespace Base\Models;
use Illuminate\Support\Facades\Storage;

class File
{
    protected $fileUri, $viewFileUrl, $uploadFileUrl;

    public function __construct($path = null) {
        $this->fileUri = $path;
        $this->viewFileUrl = env("ELITE_FILE_REPO_BASE_URL")."view/".$path;
        $this->uploadFileUrl = env("ELITE_FILE_REPO_BASE_URL")."file-upload";
    }

    public function fileRequest() : ? string {
        $url =  $this->fileUri;
        $url = unSignUrl($url);

        $ext = $this->getFileExtN($url);
        if($ext === 'pdf'){
            //FOR PDF
            file_put_contents(public_path() . "/tmp/_", Storage::disk("public")->get($url));
            return '<iframe width="100%" height="100%" src="' . env("ELITE_FILE_REPO_BASE_URL") .'/tmp/_"></iframe>';
        } else if(in_array($ext, imageFormat())) {
            //FOR IMAGE
            return Storage::disk('public')->get( $url);
        }
        return null;
    }

    public function crewDocFileRequest($fileName) : ? string {
        $url = unSignUrl($this->fileUri);
        $fileNameWithExt = $fileName . "." . $this->getFileExtN($url);
        if ($this->getFileExtN($url) === '') return '';
        file_put_contents(public_path() . "/tmp/" . $fileNameWithExt, Storage::disk("public")->get($url));
        return env("ELITE_FILE_REPO_BASE_URL") . "/tmp/" . $fileNameWithExt;
    }

    public function fileRequestOld() : ? string {
        file_put_contents(public_path()."/tmp/_", $this->requestFile());
        $ext = $this->getFileExt();
        if($ext === 'pdf'){
            //FOR PDF
            header("Content-type: application/pdf");
            return '<iframe width="100%" height="100%" src="/tmp/_"></iframe>';
        }
        else if(in_array($ext, imageFormat())) {
            //FOR IMAGE
            $imgFile = base64_encode(file_get_contents(public_path()."/tmp/_"));
            $base64Format = str_replace('data:image/'.$ext.';base64,','',$imgFile);
            $binaryFormat = base64_decode($base64Format);
            $image= imagecreatefromstring($binaryFormat);
            header('Content-Type: image/'.$ext);
            imageFormatting($image, $ext);
            imagedestroy($image);
            return null;
        }
        return null;
    }

    private function requestFile() : ? string {
        $headers = array(
            "Accept: application/json",
            "Accept-Encoding: gzip,deflate",
        );
        $curl = curl_init($this->viewFileUrl);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function uploadFile($location, $file, $fileName, $overwrite): ?string
    {
        ini_set('memory_limit', '1024M');
        $createdFilePath = Storage::disk('public')->putFileAs($location, $file, createFileName($fileName));
        var_dump($createdFilePath);
        return signUrl($createdFilePath);
    }

    /*public function uploadFileOld($location, $file, $fileName, $overwrite): ?string
    {
        $target_file = public_path()."/tmp/".$fileName;
        $target_file = str_replace(" ","",mb_strtolower($target_file));
        move_uploaded_file($file, $target_file);
        if(move_uploaded_file($file, $target_file)){
            if(uploadFileToRepo($target_file, $location)){
                $postFields = ["system" => env("SYSTEM_NAME"),"location" => $location,"file" => $fileName,"overwrite" => $overwrite];
                $headers = array("Accept: application/json");
                $url = 'http://192.168.2.49/elite_file_public/public/file-upload';
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
                $response = curl_exec($curl);
                curl_close($curl);
                unlink($target_file);
                return $response;
            }
        }
        return false;
    }*/

    public function getFileExt() : string {
        if (!$this->fileUri) return '';
        $path = explode('.',$this->fileUri);
        return str_rot13($path[1]);
    }

    private function getFileExtN($url) : string {
        $path = explode('.',$url);
        return $path[sizeof($path) - 1];
    }
}
