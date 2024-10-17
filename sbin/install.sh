#!/bin/bash

if [[ $1 == */ ]]; then
  INSTALL_PATH=${1%?}
else
  INSTALL_PATH=$1
fi

# 配置目录
if [[ $2 == */ ]]; then
  CONFIG_PATH=${2%?}
else
  CONFIG_PATH=$2
fi

RED_COLOR='\e[1;31m'
GREEN_COLOR='\e[1;32m'
YELLOW_COLOR='\e[1;33m'
BLUE_COLOR='\e[1;34m'
PINK_COLOR='\e[1;35m'
SHAN='\e[1;33;5m'
RES='\e[0m'
clear

if [ "$(id -u)" != "0" ]; then
  echo -e "\r\n${RED_COLOR}出错了，请使用 root 权限重试！${RES}\r\n" 1>&2
  exit 1
fi

if [ ! -f "$INSTALL_PATH/gopeed" ]; then
  echo -e "\r\n${RED_COLOR}出错了${RES}，当前系统未安装 Gopeed\r\n"
  exit 1
fi

# 创建 systemd
cat >/etc/systemd/system/gopeed.service <<EOF
[Unit]
Description=UNAS Gopeed service
After=remote-fs.target network.target
AssertPathExists=$CONFIG_PATH

[Service]
Type=simple
WorkingDirectory=$CONFIG_PATH
ExecStart=$INSTALL_PATH/gopeed  -c "$CONFIG_PATH/config.json"
TimeoutStopSec=20
KillMode=process
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

# 添加开机启动
systemctl daemon-reload
systemctl enable gopeed >/dev/null 2>&1
# 启动服务
systemctl start gopeed >/dev/null 2>&1

