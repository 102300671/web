from flask import Flask, request, jsonify
from flask_cors import CORS
import importlib.util
import os
import mysql.connector

app = Flask(__name__)
CORS(app)

def get_db():
    return mysql.connector.connect(
        host="localhost",
        user="crypto",
        password="crypto",
        database="crypto_db"
    )

@app.route('/check_flag', methods=['POST'])
def check_flag():
    data = request.get_json()
    user_flag = data.get('flag', '').strip()
    problem = data.get('problem', '')
    username = data.get('username', '未知用户')  # 直接用前端传递的 username
    flag_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'code', problem, 'flag.py')
    real_ip = request.headers.get('X-Forwarded-For', request.remote_addr)

    if not os.path.exists(flag_path):
        app.logger.warning(f"[{real_ip}] 用户: {username}, Problem: {problem}, flag文件不存在")
        return jsonify({'success': False})

    try:
        spec = importlib.util.spec_from_file_location("flag", flag_path)
        flag_module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(flag_module)
        real_flag = flag_module.flag.decode() if isinstance(flag_module.flag, bytes) else str(flag_module.flag)
        result = user_flag == real_flag
    except Exception as e:
        app.logger.error(f"[{real_ip}] 用户: {username}, 加载flag失败: {e}")
        return jsonify({'success': False})

    app.logger.info(f"[{real_ip}] 用户: {username}, Problem: {problem}, Flag: {user_flag}, Result: {'正确' if result else '错误'}")
    if result:
        try:
            db = get_db()
            cursor = db.cursor()
            # 先查 user_id
            cursor.execute("SELECT id FROM users WHERE username=%s", (username,))
            row = cursor.fetchone()
            if not row:
                return jsonify({'success': False, 'msg': '用户不存在'})
            user_id = row[0]
            update_query = """
                INSERT INTO user_problem_status (user_id, problem, completed)
                VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE completed = TRUE
            """
            cursor.execute(update_query, (user_id, problem, True))
            db.commit()
            cursor.close()
            db.close()
            return jsonify({'success': True, 'msg': 'flag 正确'})
        except Exception as e:
            app.logger.error(f"数据库更新失败: {e}")
            return jsonify({'success': True, 'msg': 'flag 正确，但数据库写入失败'})
    else:
        return jsonify({'success': False, 'msg': 'flag错误，请重试。'})
