import sys
import logging
import os

# 添加项目目录到路径
sys.path.insert(0, '/var/www/html/app')
# 导入 Flask 应用
from app import app as application

