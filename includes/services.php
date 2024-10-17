<?php
include 'functions.php';

// 获取请求参数
$jsonData = file_get_contents("php://input");
// 解析JSON数据
$jsonObj = json_decode($jsonData);
// 现在可以使用$jsonObj访问传递的JSON数据中的属性或方法
// 获取token，通过token获取用户名
$token = $jsonObj->token;
if(empty($token)) {
  echo json_encode(array(
    'err' => 1,
    'msg' => 'Token is empty'
  ));
  return;
}
session_id($token);
// 强制禁止浏览器的隐式cookie中的sessionId
$_COOKIE = [ 'PHPSESSID' => '' ];
session_start([ // php7
    'cookie_lifetime' => 2000000000,
    'read_and_close'  => false,
]);
// 获取用户名
$userId = isset($_SESSION['uid']) && is_string($_SESSION['uid']) ? $_SESSION['uid'] : $_SESSION['username'];
if(!isset($userId)) {
  echo json_encode(array(
    'err' => 1,
    'msg' => 'User information not obtained'
  ));
  return;
}
// 获取要进行的操作
$action = $jsonObj->action;

if($action == "getConfig") {
  // 获取homes目录中管理应用的配置的目录
  $hmoesExtAppsFolder = getHomesAppsDir();
  if($hmoesExtAppsFolder == "") {
    // homes目录未开启，提醒用户开启homes目录
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Please enable the home directory in the User Account before use'
    ));
    return;
  }
  // 判断服务状态
  $enable = false;
  // 判断Gopeed服务是否已经安装
  if(checkServiceExist("gopeed")) {
    // Gopeed服务已经安装，判断是否运行
    $enable = checkServiceStatus("gopeed");
  }
  // 获取共享文件夹列表
  $shareFolders = getAllSharefolder();
  // 获取homes目录中外部应用的配置的目录，即默认的配置目录
  $homesExtConfigFolder = getDefaultConfigDir();
  // 读取配置文件中的配置
  $manageConfigFile = $hmoesExtAppsFolder.'/gopeed/config.json';
  if(file_exists($manageConfigFile)) {
    $jsonString = file_get_contents($manageConfigFile);
    // 如果想要以数组形式解码JSON，可以传递第二个参数为true
    $manageConfigData = json_decode($jsonString, true);
    $manageConfigData['enable'] = $enable;
    $manageConfigData['shareFolders'] = $shareFolders;
    $manageConfigData['homesExtConfigFolder'] = $homesExtConfigFolder;
    if(empty($manageConfigData['configDir'])) {
      $manageConfigData['configDir'] = $homesExtConfigFolder;
    }
    echo json_encode($manageConfigData);
  } else {
    echo json_encode(array(
      'enable' => $enable,
      'homesExtConfigFolder' => $homesExtConfigFolder,
      'shareFolders' => $shareFolders,
      'configDir' => $homesExtConfigFolder,
      'downloadDir' => '',
      'username' => '',
      'password' => '',
      'apiToken' => '',
      'port' => 9999
    ));
  }
} if($action == "manage") {
  // 保存配置并启动或者停止服务
  // 获取homes目录中管理应用的配置的目录
  $hmoesExtAppsFolder = getHomesAppsDir();
  if($hmoesExtAppsFolder == "") {
    // homes目录未开启，提醒用户开启homes目录
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Please enable the home directory in the User Account before use'
    ));
    return;
  }
  // 获取homes目录中外部应用的配置的目录，即默认的配置目录
  $homesExtConfigFolder = getDefaultConfigDir();
  // 是否启用gopeed服务
  $enable = false;
  if (property_exists($jsonObj, "enable")) {
    $enable = $jsonObj->enable;
  }
  // gopeed的配置文件目录
  if (property_exists($jsonObj, 'configDir')) {
    $configDir = $jsonObj->configDir;
    if($configDir == $homesExtConfigFolder) {
      // 如果配置目录为默认目录，则判断默认配置目录是否存在
      if (!is_dir($homesExtConfigFolder)) {
        // 默认配置目录不存在，创建默认配置目录
        exec("sudo mkdir -p $homesExtConfigFolder");
        // 此处不判断是否创建成功，交由后续判断统一处理
      }
    }
  } else {
    // 配置目录未设置
    echo json_encode(array(
      'err' => 2,
      'msg' => 'No configuration directory set'
    ));
    return;
  }

  // 检测配置目录是否存在
  if (is_dir($configDir)) {
    $gopeedConfigDir = $configDir."/gopeed";
    if (!is_dir($gopeedConfigDir)) {
      // 文件夹不存在，创建文件夹
      exec("sudo mkdir -p $gopeedConfigDir");
      // 此处不判断是否创建成功，交由后续判断统一处理
    }
    if (is_dir($gopeedConfigDir)) {
      // 设置www-data对gopeed配置文件目录访问权限
      exec("sudo setfacl -d -m u:www-data:rwx $gopeedConfigDir && sudo setfacl -m m:rwx $gopeedConfigDir && sudo setfacl -R -m u:www-data:rwx $gopeedConfigDir");
    } else {
      // gopeed配置目录创建失败
      echo json_encode(array(
        'err' => 2,
        'msg' => 'Failed to create Configuration directory'
      ));
      return;
    }
  } else {
    // 配置目录不存在
    echo json_encode(array(
      'err' => 2,
      'msg' => 'Configuration directory is not exist'
    ));
    return;
  }
  
  // gopeed的默认下载目录
  $downloadDir = "";
  if (property_exists($jsonObj, 'downloadDir')) {
    $downloadDir = $jsonObj->downloadDir;
  }
  if(empty($downloadDir)) {
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Please select default download directory'
    ));
    return;
  }

  // gopeed的用户名
  $username = "";
  if (property_exists($jsonObj, 'username')) {
    $username = $jsonObj->username;
  }
  if(empty($username)) {
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Please set username'
    ));
    return;
  }

  // gopeed的密码
  $password = "";
  if (property_exists($jsonObj, 'password')) {
    $password = $jsonObj->password;
  }
  if(empty($password)) {
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Please set password'
    ));
    return;
  }

  // gopeed的HTTP API 令牌
  $apiToken = "";
  if (property_exists($jsonObj, 'apiToken')) {
    $apiToken = $jsonObj->apiToken;
  }
  if(empty($apiToken)) {
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Please set ApiToken'
    ));
    return;
  }
  
  // gopeed的端口，默认9999
  $port = 9999;
  if (property_exists($jsonObj, 'port')) {
    $portData = $jsonObj->port;
    if(is_numeric($portData)) {
      $port = intval($portData);
    }
  }

  $manageConfigData = array(
    'configDir' => $configDir,
    'downloadDir' => $downloadDir,
    'username' => $username,
    'password' => $password,
    'apiToken' => $apiToken,
    'port' => $port
  );
  // 保存gopeed的管理配置
  $result = saveManageConfig($hmoesExtAppsFolder.'/gopeed', $manageConfigData);
  if($result == false) {
    error_log("Failed to save management configuration!");
    // 配置写入文件失败
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Failed to save configuration'
    ));
    return;
  }

  // 保存配置到gopeed配置文件
  $result = saveConfigToFile($gopeedConfigDir, "config.json", array(
    'port' => $port,
    'username' => $username,
    'password' => $password,
    'apiToken' => $apiToken,
    'storageDir' => $gopeedConfigDir,
    'downloadConfig' => array(
      "downloadDir" => $downloadDir."/download"
    )
  ));
  if($result == false) {
    error_log("Failed to save gopeed configuration!");
    // 配置写入文件失败
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Failed to save configuration'
    ));
    return;
  }

  // gopeed安装程序目录
  $sbinPath = "/unas/apps/gopeed/sbin";
  // gopeed的程序文件
  $appFile = $sbinPath."/gopeed";
  // 修改gopeed的权限和所有者
  exec("sudo chown www-data:www-data $appFile");
  exec("sudo chmod 755 $appFile");

  // 修改安装、卸载脚本的权限和所有者
  $installScript = $sbinPath."/install.sh";
  exec("sudo chown www-data:www-data $installScript");
  exec("sudo chmod 755 $installScript");

  $uninstallScript = $sbinPath."/uninstall.sh";
  exec("sudo chown www-data:www-data $uninstallScript");
  exec("sudo chmod 755 $uninstallScript");

  // 卸载gopeed的命令
  $uninstallServiceCommand = "sudo $uninstallScript $sbinPath";
  if($enable) {
    // gopeed的安装命令
    $installServiceCommand = "sudo $installScript $sbinPath $gopeedConfigDir";
    // error_log("安装命令为：".$installServiceCommand);

    // 判断Gopeed服务是否已经安装
    if(checkServiceExist("gopeed")) {
      // Gopeed服务已经安装，则执行卸载后再安装
      exec($uninstallServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
      exec($installServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
    } else {
      // Gopeed服务未安装，则执行安装
      exec($installServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
      // error_log("服务安装，结果为：".$result);
    }
  } else {
    // 判断Gopeed服务是否已经安装
    if(checkServiceExist("gopeed")) {
      // Gopeed服务已经安装，则执行卸载
      exec($uninstallServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
    }
  }
  echo json_encode(array(
    'err' => 0
  ));
} if($action == "checkport") {
  $port = $jsonObj->port;
  if(isset($port)) {
    if (is_numeric($port)) {
      if ($port >= 1 && $port <= 65535 ) {
        if (isPortOccupied($port)) {
          echo json_encode(array(
            'err' => 1,
            'msg' => 'Port has been used'
          ));
          return;
        }
        echo json_encode(array(
          'err' => 0
        ));
        return;
      }
    }
  }
  // 返回错误提示
  echo json_encode(array(
    'err' => 1,
    'msg' => 'Port should between 1 and 65535'
  ));
}
?>