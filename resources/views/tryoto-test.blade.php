<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار API Tryoto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #555;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-info {
            background-color: #17a2b8;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .alert {
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid transparent;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 14px;
        }
        .result.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .result.info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 10px 0;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 اختبار API Tryoto</h1>
        
        <div class="test-section">
            <h3>1. فحص إمكانية الوصول للـ API</h3>
            <button class="btn" onclick="checkApiAccess()">فحص الوصول</button>
            <div class="loading" id="loading1">
                <div class="spinner"></div>
                <p>جاري الفحص...</p>
            </div>
            <div id="result1" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>2. اختبار الحصول على Access Token</h3>
            <button class="btn" onclick="testEndpoint('refreshToken')">اختبار refreshToken</button>
            <div class="loading" id="loading2">
                <div class="spinner"></div>
                <p>جاري الاختبار...</p>
            </div>
            <div id="result2" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>3. اختبار إنشاء طلب تجريبي</h3>
            <button class="btn btn-success" onclick="testFullApi()">اختبار كامل للـ API</button>
            <div class="loading" id="loading3">
                <div class="spinner"></div>
                <p>جاري إنشاء الطلب التجريبي...</p>
            </div>
            <div id="result3" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>4. اختبار الـ Endpoints المختلفة</h3>
            <button class="btn btn-info" onclick="testEndpoints()">اختبار جميع الـ Endpoints</button>
            <div class="loading" id="loading4">
                <div class="spinner"></div>
                <p>جاري اختبار الـ Endpoints...</p>
            </div>
            <div id="result4" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>5. فحص صلاحيات الحساب</h3>
            <button class="btn btn-warning" onclick="checkPermissions()">فحص الصلاحيات</button>
            <div class="loading" id="loading5">
                <div class="spinner"></div>
                <p>جاري فحص الصلاحيات...</p>
            </div>
            <div id="result5" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>6. اختبار إصدارات API</h3>
            <button class="btn btn-secondary" onclick="testVersions()">اختبار الإصدارات</button>
            <div class="loading" id="loading6">
                <div class="spinner"></div>
                <p>جاري اختبار الإصدارات...</p>
            </div>
            <div id="result6" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>7. تجربة بدائل إنشاء الطلب</h3>
            <button class="btn btn-danger" onclick="tryAlternatives()">تجربة البدائل</button>
            <div class="loading" id="loading7">
                <div class="spinner"></div>
                <p>جاري تجربة البدائل...</p>
            </div>
            <div id="result7" class="result" style="display: none;"></div>
        </div>

        <div class="test-section">
            <h3>8. إنشاء طلب تجريبي للوحة التحكم</h3>
            <button class="btn btn-primary" onclick="createDashboardTest()">إنشاء طلب تجريبي</button>
            <div class="loading" id="loading8">
                <div class="spinner"></div>
                <p>جاري إنشاء الطلب التجريبي...</p>
            </div>
            <div id="result8" class="result" style="display: none;"></div>
            <div class="alert alert-info" style="margin-top: 10px;">
                <strong>ملاحظة:</strong> هذا الطلب سيظهر في لوحة التحكم على 
                <a href="https://app.tryoto.com/" target="_blank">https://app.tryoto.com/</a>
            </div>
        </div>

        <div class="test-section">
            <h3>9. معلومات الاتصال</h3>
            <div class="result info">
                <strong>Base URL:</strong> https://api.tryoto.com/rest/v2/
                <br><strong>Endpoint:</strong> refreshToken
                <br><strong>Method:</strong> POST
                <br><strong>Headers:</strong> Content-Type: application/json
            </div>
        </div>
    </div>

    <script>
        function showLoading(id) {
            document.getElementById('loading' + id).style.display = 'block';
            document.getElementById('result' + id).style.display = 'none';
        }

        function hideLoading(id) {
            document.getElementById('loading' + id).style.display = 'none';
        }

        function showResult(id, data, isSuccess = true) {
            const resultDiv = document.getElementById('result' + id);
            resultDiv.style.display = 'block';
            resultDiv.className = 'result ' + (isSuccess ? 'success' : 'error');
            resultDiv.textContent = JSON.stringify(data, null, 2);
        }

        async function checkApiAccess() {
            showLoading(1);
            try {
                const response = await fetch('/tryoto-test/check-access');
                const data = await response.json();
                hideLoading(1);
                showResult(1, data, data.success);
            } catch (error) {
                hideLoading(1);
                showResult(1, { error: error.message }, false);
            }
        }

        async function testEndpoint(endpoint) {
            showLoading(2);
            try {
                const response = await fetch(`/tryoto-test/test-endpoint?endpoint=${endpoint}`);
                const data = await response.json();
                hideLoading(2);
                showResult(2, data, data.success);
            } catch (error) {
                hideLoading(2);
                showResult(2, { error: error.message }, false);
            }
        }

        async function testFullApi() {
            showLoading(3);
            try {
                const response = await fetch('/tryoto-test/test-api');
                const data = await response.json();
                hideLoading(3);
                showResult(3, data, data.success);
            } catch (error) {
                hideLoading(3);
                showResult(3, { error: error.message }, false);
            }
        }

        async function testEndpoints() {
            showLoading(4);
            try {
                const response = await fetch('/tryoto-test/test-endpoints');
                const data = await response.json();
                hideLoading(4);
                showResult(4, data, data.success);
            } catch (error) {
                hideLoading(4);
                showResult(4, { error: error.message }, false);
            }
        }

        async function checkPermissions() {
            showLoading(5);
            try {
                const response = await fetch('/tryoto-test/check-permissions');
                const data = await response.json();
                hideLoading(5);
                showResult(5, data, data.success);
            } catch (error) {
                hideLoading(5);
                showResult(5, { error: error.message }, false);
            }
        }

        async function testVersions() {
            showLoading(6);
            try {
                const response = await fetch('/tryoto-test/test-versions');
                const data = await response.json();
                hideLoading(6);
                showResult(6, data, data.success);
            } catch (error) {
                hideLoading(6);
                showResult(6, { error: error.message }, false);
            }
        }

        async function tryAlternatives() {
            showLoading(7);
            try {
                const response = await fetch('/tryoto-test/try-alternatives');
                const data = await response.json();
                hideLoading(7);
                showResult(7, data, data.success);
            } catch (error) {
                hideLoading(7);
                showResult(7, { error: error.message }, false);
            }
        }

        async function createDashboardTest() {
            showLoading(8);
            try {
                const response = await fetch('/tryoto-test/create-dashboard-test');
                const data = await response.json();
                hideLoading(8);
                showResult(8, data, data.success);
                
                // If successful, show dashboard link
                if (data.success) {
                    const resultDiv = document.getElementById('result8');
                    resultDiv.innerHTML += `
                        <div style="margin-top: 15px; padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;">
                            <strong>✅ تم إنشاء الطلب بنجاح!</strong><br>
                            <a href="https://app.tryoto.com/" target="_blank" style="color: #155724; text-decoration: underline;">
                                🔗 انقر هنا للذهاب إلى لوحة التحكم
                            </a>
                        </div>
                    `;
                }
            } catch (error) {
                hideLoading(8);
                showResult(8, { error: error.message }, false);
            }
        }
    </script>
</body>
</html> 