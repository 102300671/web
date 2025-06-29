# /var/www 目录说明

## 项目概述
该目录用于存放Web应用程序及网站内容。

## 包含内容
- crypto/ 	密码学相关组件
- html/ 	静态网页文件
- library/ 	图书管理系统

## 环境要求
- Apache/Nginx Web服务器
- PHP 7.4+
- MySQL 5.7+

## 配置说明
1. 设置目录权限：
```bash
sudo chown -R www-data:www-data /var/www
sudo chmod -R 755 /var/www
```

2. 部署步骤：
```
cp -r ./build/* /var/www/html/
```

## 贡献指南
请遵循现有目录结构提交代码，所有PHP文件需符合PSR-4规范。