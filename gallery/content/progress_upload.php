<?php
header('Content-Type: application/json');
session_start();
if ($_SERVER['REQUEST_METHOD']!=='POST'){ echo json_encode(['error'=>'只支持POST']); exit;}
$groupName = $_SESSION['group_name']??'未知用户';
$ip = $_SERVER['REMOTE_ADDR']; $timestamp = date('Y-m-d H:i:s');

$logFile = __DIR__.'/../logs/upload_log.json';
$imageFile = __DIR__.'/images.json';
$videoFile = __DIR__.'/videos.json';
$imageDir = __DIR__.'/../assets/image/';
$videoDir = __DIR__.'/../assets/video/';
!is_dir($imageDir)&&mkdir($imageDir,0755,true);
!is_dir($videoDir)&&mkdir($videoDir,0755,true);

function updateJson($file,$url){ $data = file_exists($file)?json_decode(file_get_contents($file),true):[]; $data[]=$url; file_put_contents($file,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));}
function logUpload($file,$data){ $logs=file_exists($file)?json_decode(file_get_contents($file),true):[]; $logs[]=$data; file_put_contents($file,json_encode($logs,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));}

// URL 上传
if(isset($_POST['urls'])){
  $urls=$_POST['urls'];
  $valid=$invalid=[];
  foreach($urls as $u){ $u=trim($u); if($u!==''){
    if(filter_var($u,FILTER_VALIDATE_URL)){ updateJson($imageFile,$u); logUpload($logFile,['type'=>'url','content'=>$u,'group_name'=>$groupName,'ip'=>$ip,'timestamp'=>$timestamp]); $valid[]=$u; }
    else $invalid[]=$u;
  }}
  if($valid) echo json_encode(['success'=>count($valid).'个链接上传成功'.($invalid?', '.count($invalid).'个无效':'')]); else echo json_encode(['error'=>'未提供有效URL']);
  exit;
}

// 图片上传
elseif(isset($_FILES['image'])){
  $files=$_FILES['image'];
  $uploaded=[]; $errors=[];
  $uploadTarget=$_POST['upload_target']??'local';
  for($i=0;$i<count($files['name']);$i++){
    $fileName=$files['name'][$i]; $tmpFile=$files['tmp_name'][$i];
    if($files['error'][$i]!==UPLOAD_ERR_OK){ $errors[]="$fileName: 上传错误"; continue;}
    if(!is_uploaded_file($tmpFile)){$errors[]="$fileName: 非法上传"; continue;}
    $ext=strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
    $allowed=['jpg','jpeg','png','gif','webp'];
    if(!in_array($ext,$allowed)){$errors[]="$fileName: 不支持的文件类型"; continue;}
    $filename='Image_'.time().'_'.uniqid().'.'.$ext;
    $target=$imageDir.$filename;
    if(move_uploaded_file($tmpFile,$target)){
      $url='/assets/image/'.$filename;
      updateJson($imageFile,$url);
      logUpload($logFile,['type'=>'file','content'=>$url,'group_name'=>$groupName,'ip'=>$ip,'timestamp'=>$timestamp]);
      $uploaded[]=$url;
    }else $errors[]="$fileName: 上传失败";
  }
  if($uploaded) echo json_encode(['success'=>count($uploaded).'个图片上传成功','urls'=>$uploaded,'errors'=>$errors]);
  else echo json_encode(['error'=>'图片上传失败','details'=>$errors]);
  exit;
}

// 视频上传
elseif(isset($_FILES['video'])){
  $files=$_FILES['video'];
  $uploaded=[]; $errors=[];
  for($i=0;$i<count($files['name']);$i++){
    $fileName=$files['name'][$i]; $tmpFile=$files['tmp_name'][$i];
    if($files['error'][$i]!==UPLOAD_ERR_OK){ $errors[]="$fileName: 上传错误"; continue;}
    if(!is_uploaded_file($tmpFile)){$errors[]="$fileName: 非法上传"; continue;}
    $ext=strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
    $allowed=['mp4','webm','ogg'];
    if(!in_array($ext,$allowed)){$errors[]="$fileName: 不支持的视频类型"; continue;}
    $filename='Video_'.time().'_'.uniqid().'.'.$ext;
    $target=$videoDir.$filename;
    if(move_uploaded_file($tmpFile,$target)){
      $url='/assets/video/'.$filename;
      updateJson($videoFile,$url);
      logUpload($logFile,['type'=>'video','content'=>$url,'group_name'=>$groupName,'ip'=>$ip,'timestamp'=>$timestamp]);
      $uploaded[]=$url;
    }else $errors[]="$fileName: 上传失败";
  }
  if($uploaded) echo json_encode(['success'=>count($uploaded).'个视频上传成功','urls'=>$uploaded,'errors'=>$errors]);
  else echo json_encode(['error'=>'视频上传失败','details'=>$errors]);
  exit;
}

else echo json_encode(['error'=>'未提供数据']);
?>