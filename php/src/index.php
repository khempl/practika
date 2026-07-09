<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парсер платежных документов ЖКХ</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .main-card {
            border-radius: 1.25rem;
            border: 0;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .main-card .card-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            border: 0;
            padding: 1.25rem 2rem;
        }

        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #fcfcfd;
        }

        .drop-zone:hover {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }

        .drop-zone.dragover {
            border-color: #0d6efd;
            background-color: #e8f0fe;
            transform: scale(1.01);
        }

        .drop-zone i {
            font-size: 3.5rem;
            color: #94a3b8;
            margin-bottom: 0.75rem;
            display: block;
        }

        .drop-zone .file-name {
            font-weight: 600;
            color: #0f172a;
            background: #eef2ff;
            padding: 0.35rem 1.25rem;
            border-radius: 50px;
            display: inline-block;
            font-size: 0.9rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .stat-card .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            font-weight: 600;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .log-container {
            max-height: 380px;
            overflow-y: auto;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 0.8rem;
            line-height: 1.6;
        }

        .log-container::-webkit-scrollbar {
            width: 6px;
        }

        .log-container::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 4px;
        }

        .log-container::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .log-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        .log-line {
            padding: 0.2rem 0;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
            white-space: pre-wrap;
            word-break: break-all;
        }

        .log-line .log-time {
            color: #64748b;
            margin-right: 0.75rem;
            user-select: none;
        }

        .log-line.log-success {
            color: #34d399;
        }

        .log-line.log-error {
            color: #f87171;
        }

        .log-line.log-info {
            color: #93c5fd;
        }

        .log-empty {
            color: #64748b;
            text-align: center;
            padding: 2rem 0;
        }

        .progress-custom {
            height: 1.25rem;
            border-radius: 2rem;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-custom .progress-bar {
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: width 0.5s ease;
        }

        .error-group-badge {
            background: #fef2f2;
            color: #991b1b;
            border-radius: 50px;
            padding: 0.3rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .error-group-badge .badge-count {
            background: #dc2626;
            color: white;
            border-radius: 50px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
            line-height: 1;
            box-sizing: border-box;
            white-space: nowrap;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: 0;
            padding: 0.6rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .btn-primary-gradient:disabled {
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .status-badge.idle {
            background: #e2e8f0;
            color: #475569;
        }

        .status-badge.processing {
            background: #fef9c3;
            color: #854d0e;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .file-info {
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 0.75rem 1.25rem;
            display: none;
        }

        .file-info.active {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .file-info .file-details {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .file-info .file-size {
            color: #64748b;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <!-- Шапка -->
                <div class="text-center mb-4">
                    <h1 class="fw-bold" style="color: #0f172a;">
                        <i class="fas fa-file-import text-primary me-3"></i>Парсер ЖКХ
                    </h1>
                    <p class="text-muted">Загрузите файл с платежными документами. Система обработает 700 000+ строк.
                    </p>
                    <p class="small"><a href="list.php">Посмотреть записи в базе данных</a></p>
                    <button onclick="clearDatabase()"
                            style="background:#dc2626; color:#fff; border:none; margin: 10px; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:13px;">
                            Очистить базу данных
                    </button>
                </div>

                <!-- Основная карточка -->
                <div class="card main-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="fw-semibold"><i class="fas fa-upload me-2"></i>Загрузка и обработка</span>
                            <span id="statusBadge" class="status-badge idle">
                                <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Ожидание
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        <!-- 1. Зона загрузки -->
                        <div id="dropZone" class="drop-zone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mb-1 fw-semibold" id="dropText">Перетащите файл сюда или кликните для выбора</p>
                            <p class="text-muted small">Поддерживаются файлы формата .txt</p>
                            <input type="file" id="fileInput" accept=".txt" class="d-none">
                            <span class="file-name" id="fileNameDisplay" style="display:none;"></span>
                        </div>

                        <!-- Информация о файле -->
                        <div id="fileInfo" class="file-info mt-3">
                            <div class="file-details">
                                <i class="fas fa-file-alt text-primary"></i>
                                <span id="fileInfoName" class="fw-semibold">file.txt</span>
                                <span class="file-size" id="fileInfoSize">0 KB</span>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" id="removeFileBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Прогресс -->
                        <div id="progressArea" class="mt-4" style="display:none;">
                            <div class="d-flex justify-content-between small mb-1">
                                <span id="progressLabel"><i class="fas fa-spinner fa-spin me-2"
                                        id="progressIcon"></i><span id="progressText">Обработка...</span></span>
                                <span id="progressPercent" class="fw-bold">0%</span>
                            </div>
                            <div class="progress-custom">
                                <div id="progressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                    role="progressbar" style="width: 0%;">
                                    0%
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- 2. Статистика -->
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="stat-card text-center">
                                    <div class="stat-icon text-secondary"><i class="fas fa-list"></i></div>
                                    <div class="stat-label">Всего строк</div>
                                    <div class="stat-value" id="totalLines">0</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card text-center border-start border-success border-3">
                                    <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                                    <div class="stat-label">Успешно</div>
                                    <div class="stat-value text-success" id="successCount">0</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card text-center border-start border-danger border-3">
                                    <div class="stat-icon text-danger"><i class="fas fa-exclamation-circle"></i></div>
                                    <div class="stat-label">Ошибки</div>
                                    <div class="stat-value text-danger" id="errorCount">0</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-card text-center border-start border-info border-3">
                                    <div class="stat-icon text-info"><i class="fas fa-clock"></i></div>
                                    <div class="stat-label">Время</div>
                                    <div class="stat-value" id="elapsedTime" style="font-size: 1.1rem;">00:00</div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Детали ошибок -->
                        <div id="errorDetails" class="mt-3" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-bug text-danger me-2"></i>Детализация ошибок
                                </h6>
                                <span id="errorGroupsCount" class="badge bg-light text-dark">0 типов</span>
                            </div>
                            <div id="errorGroupsContainer" class="d-flex flex-wrap gap-2 mb-2"></div>
                            <button class="btn btn-outline-danger btn-sm" id="downloadErrorsBtn">
                                <i class="fas fa-download me-2"></i>Скачать файл с ошибками
                            </button>
                        </div>

                        <hr class="my-4">

                        <!-- 4. Лог-консоль -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-terminal me-2"></i>Лог обработки
                                    <span id="logCount" class="badge bg-secondary ms-2">0</span>
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary" id="clearLogsBtn">
                                    <i class="fas fa-eraser me-1"></i>Очистить лог
                                </button>
                            </div>
                            <div class="log-container" id="logContainer">
                                <div class="log-empty">
                                    <i class="fas fa-circle-notch fa-spin me-2"></i> Ожидание действий...
                                </div>
                            </div>
                        </div>

                        <!-- Кнопки управления -->
                        <div class="mt-4 d-flex gap-3 flex-wrap">
                            <button class="btn btn-primary-gradient px-5" id="startBtn">
                                <i class="fas fa-play me-2"></i>Запустить парсинг
                            </button>
                            <button class="btn btn-outline-secondary" id="resetBtn">
                                <i class="fas fa-undo me-2"></i>Сбросить парсинг
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Подвал -->
                <div class="text-center text-muted small mt-4">
                    <i class="fas fa-database me-1"></i> Данные сохраняются в MongoDB. Ошибки логируются отдельно.
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Полная логика -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ----- DOM элементы -----
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const dropText = document.getElementById('dropText');
            const fileInfo = document.getElementById('fileInfo');
            const fileInfoName = document.getElementById('fileInfoName');
            const fileInfoSize = document.getElementById('fileInfoSize');
            const removeFileBtn = document.getElementById('removeFileBtn');

            const startBtn = document.getElementById('startBtn');
            const resetBtn = document.getElementById('resetBtn');
            const clearLogsBtn = document.getElementById('clearLogsBtn');

            const progressArea = document.getElementById('progressArea');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');

            const totalLines = document.getElementById('totalLines');
            const successCount = document.getElementById('successCount');
            const errorCount = document.getElementById('errorCount');
            const elapsedTime = document.getElementById('elapsedTime');

            const logContainer = document.getElementById('logContainer');
            const logCount = document.getElementById('logCount');

            const statusBadge = document.getElementById('statusBadge');
            const errorDetails = document.getElementById('errorDetails');
            const errorGroupsContainer = document.getElementById('errorGroupsContainer');
            const errorGroupsCount = document.getElementById('errorGroupsCount');
            const downloadErrorsBtn = document.getElementById('downloadErrorsBtn');

            let selectedFile = null;
            let statusInterval = null;
            let timerInterval = null;
            let startTime = null;
            let isProcessing = false;
            let currentJobId = null;
            let logCursor = 0;

            // ----- Вспомогательные функции -----

            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1048576).toFixed(1) + ' MB';
            }

            function formatTime(seconds) {
                const m = String(Math.floor(seconds / 60)).padStart(2, '0');
                const s = String(Math.floor(seconds % 60)).padStart(2, '0');
                return m + ':' + s;
            }

            function setStatus(type, text) {
                statusBadge.className = 'status-badge ' + type;
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> ' + text;
            }

            function updateLogCount() {
                const items = logContainer.querySelectorAll('.log-line:not(.log-empty)');
                logCount.textContent = items.length;
            }

            function addLog(message, type = 'info') {
                // Удаляем пустое сообщение
                const empty = logContainer.querySelector('.log-empty');
                if (empty) empty.remove();

                const div = document.createElement('div');
                div.className = 'log-line log-' + type;
                const time = new Date().toLocaleTimeString();
                div.innerHTML = '<span class="log-time">[' + time + ']</span>' + message;
                logContainer.prepend(div);

                // Ограничиваем количество
                while (logContainer.children.length > 200) {
                    logContainer.removeChild(logContainer.lastChild);
                }

                updateLogCount();
            }

            function clearLogs() {
                logContainer.innerHTML = '<div class="log-empty"><i class="fas fa-circle-notch fa-spin me-2"></i> Ожидание действий...</div>';
                updateLogCount();
            }

            function updateStats(data) {
                totalLines.textContent = data.total || 0;
                successCount.textContent = data.success || 0;
                errorCount.textContent = data.errors || 0;

                // Обновляем прогресс
                const progress = data.progress || 0;
                progressBar.style.width = progress + '%';
                progressBar.textContent = progress + '%';
                progressPercent.textContent = progress + '%';

                // Обновляем статус
                if (data.status === 'completed') {
                    setStatus('completed', '✅ Завершено');
                    progressBar.classList.remove('progress-bar-animated');
                } else if (data.status === 'error') {
                    setStatus('error', '❌ Ошибка');
                    progressBar.classList.remove('progress-bar-animated');
                } else if (data.status === 'processing') {
                    setStatus('processing', '⏳ Обработка...');
                }

                // Обновляем ошибки
                if (data.error_groups && data.error_groups.length > 0) {
                    errorDetails.style.display = 'block';
                    errorGroupsContainer.innerHTML = '';
                    data.error_groups.forEach(group => {
                        const badge = document.createElement('span');
                        badge.className = 'error-group-badge';
                        badge.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>' +
                            group.field + ' <span class="badge-count">' + group.count + '</span>';
                        errorGroupsContainer.appendChild(badge);
                    });
                    errorGroupsCount.textContent = data.error_groups.length + ' типов';
                } else {
                    errorDetails.style.display = 'none';
                }

                // Добавляем новые логи (сервер отдаёт только те, что мы ещё не видели, см. logCursor)
                if (data.logs && data.logs.length) {
                    data.logs.forEach(log => {
                        addLog(log.message, log.type || 'info');
                    });
                }
                if (typeof data.logs_total === 'number') {
                    logCursor = data.logs_total;
                }
            }

            function resetAll() {
                if (statusInterval) clearInterval(statusInterval);
                if (timerInterval) clearInterval(timerInterval);
                isProcessing = false;
                logCursor = 0;

                totalLines.textContent = '0';
                successCount.textContent = '0';
                errorCount.textContent = '0';
                elapsedTime.textContent = '00:00';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressPercent.textContent = '0%';
                progressArea.style.display = 'none';
                errorDetails.style.display = 'none';
                setStatus('idle', 'Ожидание');

                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="fas fa-play me-2"></i>Запустить парсинг';
                startBtn.className = 'btn btn-primary-gradient px-5';
                startBtn.dataset.mode = 'start';

                resetDownloadButton();

                // Не очищаем логи и не удаляем файл
            }

            // ----- Обработка файла -----

            function handleFile(file) {
                if (!file) return;
                selectedFile = file;
                fileNameDisplay.textContent = ' ' + file.name;
                fileNameDisplay.style.display = 'inline-block';
                dropText.textContent = 'Файл готов к загрузке';

                fileInfoName.textContent = file.name;
                fileInfoSize.textContent = formatFileSize(file.size);
                fileInfo.classList.add('active');

                addLog('Выбран файл: ' + file.name + ' (' + formatFileSize(file.size) + ')', 'success');
            }

            function removeFile() {
                selectedFile = null;
                fileInput.value = '';
                fileNameDisplay.style.display = 'none';
                dropText.textContent = 'Перетащите файл сюда или кликните для выбора';
                fileInfo.classList.remove('active');
                addLog('Файл удалён', 'info');
            }

            // ----- Drag & Drop -----

            dropZone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', function (e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0 && files[0].type === 'text/plain') {
                    handleFile(files[0]);
                } else {
                    addLog('Пожалуйста, загрузите файл формата .txt', 'error');
                }
            });

            dropZone.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (this.files.length > 0) {
                    handleFile(this.files[0]);
                }
            });

            removeFileBtn.addEventListener('click', removeFile);

            // ----- Запуск парсинга -----

            startBtn.addEventListener('click', function () {
                if (!selectedFile) {
                    addLog('Сначала выберите файл!', 'error');
                    return;
                }

                if (startBtn.dataset.mode === 'done') {
                    resetAll();
                    removeFile();
                    clearLogs();
                    return;
                }

                if (isProcessing) return;

                // Отправляем файл на сервер
                const formData = new FormData();
                formData.append('file', selectedFile);

                addLog('Отправка файла на сервер...', 'info');
                startBtn.disabled = true;
                startBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Загрузка...';
                logCursor = 0;

                let uploadProgressLogEl = null;

                function updateUploadProgressLog(message) {
                    if (!uploadProgressLogEl) {
                        // создаём строку в логе один раз при первом вызове
                        uploadProgressLogEl = document.createElement('div');
                        logContainer.appendChild(uploadProgressLogEl);
                    }
                    uploadProgressLogEl.textContent = message;
                }

                const xhr = new XMLHttpRequest();
                let lastLoggedPercent = -1;

                // прогресс отправки файла на сервер - обновляем одну и ту же строку в логе,
                // а не плодим новую при каждом срабатывании события
                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const loadedMb = (event.loaded / 1024 / 1024).toFixed(1);
                        const totalMb = (event.total / 1024 / 1024).toFixed(1);
                        const percent = Math.round((event.loaded / event.total) * 100);

                        if (percent !== lastLoggedPercent) {
                            updateUploadProgressLog(`📤 Отправка файла: ${loadedMb} МБ из ${totalMb} МБ (${percent}%)`);
                            lastLoggedPercent = percent;
                        }
                    }
                });

                xhr.addEventListener('load', () => {
                    let data;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (e) {
                        addLog('Сервер вернул некорректный ответ', 'error');
                        startBtn.disabled = false;
                        startBtn.innerHTML = '<i class="fas fa-play me-2"></i>Запустить парсинг';
                        return;
                    }

                    if (data.success) {
                        addLog('Файл загружен, ID: ' + data.file_id, 'success');
                        startParsing(data.file_id);
                    } else {
                        addLog('Ошибка загрузки: ' + (data.message || 'неизвестная ошибка'), 'error');
                        startBtn.disabled = false;
                        startBtn.innerHTML = '<i class="fas fa-play me-2"></i>Запустить парсинг';
                    }
                });

                xhr.addEventListener('error', () => {
                    addLog('Ошибка соединения при загрузке файла', 'error');
                    startBtn.disabled = false;
                    startBtn.innerHTML = '<i class="fas fa-play me-2"></i>Запустить парсинг';
                });

                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            });

            function startParsing(fileId) {
                isProcessing = true;
                progressArea.style.display = 'block';
                setStatus('processing', '⏳ Обработка...');
                startTime = Date.now();

                const progressIcon = document.getElementById('progressIcon');
                const progressText = document.getElementById('progressText');
                const progressBar = document.getElementById('progressBar');
                const progressPercent = document.getElementById('progressPercent');

                progressIcon.className = 'fas fa-spinner fa-spin me-2';
                progressText.textContent = 'Обработка...';
                progressBar.classList.remove('bg-success', 'bg-danger');
                progressBar.classList.add('bg-primary', 'progress-bar-animated');
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressPercent.textContent = '0%';

                timerInterval = setInterval(function () {
                    const elapsed = Math.floor((Date.now() - startTime) / 1000);
                    elapsedTime.textContent = formatTime(elapsed);
                }, 1000);

                startBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Обработка...';
                startBtn.disabled = true;

                fetch('api/parse.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ file_id: fileId })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            currentJobId = data.job_id;
                            resetDownloadButton();
                            addLog('🚀 Парсинг запущен, Job ID: ' + data.job_id, 'success');
                            if (statusInterval) clearInterval(statusInterval);
                            statusInterval = setInterval(fetchStatus, 1500);
                        } else {
                            addLog('Ошибка запуска парсинга: ' + (data.message || 'неизвестная ошибка'), 'error');
                            isProcessing = false;
                            startBtn.disabled = false;
                            startBtn.innerHTML = '<i class="fas fa-play me-2"></i>Запустить парсинг';
                            if (timerInterval) clearInterval(timerInterval);
                            setStatus('error', 'Ошибка');
                        }
                    })
                    .catch(err => {
                        addLog('Ошибка соединения: ' + err.message, 'error');
                        isProcessing = false;
                        startBtn.disabled = false;
                        startBtn.innerHTML = '<i class="fas fa-play me-2"></i>Запустить парсинг';
                        if (timerInterval) clearInterval(timerInterval);
                    });
            }

            // ----- Получение статуса -----

            function fetchStatus() {
                if (!currentJobId) return;

                fetch('api/status.php?job_id=' + currentJobId + '&since=' + logCursor)
                    .then(res => res.json())
                    .then(data => {
                        updateStats(data);

                        if (data.status === 'completed' || data.status === 'error') {
                            clearInterval(statusInterval);
                            if (timerInterval) clearInterval(timerInterval);

                            isProcessing = false;
                            startBtn.disabled = false;
                            startBtn.innerHTML = '<i class="fas fa-check me-2"></i>Готово';
                            startBtn.className = 'btn btn-success px-5';
                            startBtn.dataset.mode = 'done';
                            const progressIcon = document.getElementById('progressIcon');
                            const progressText = document.getElementById('progressText');
                            const progressBar = document.getElementById('progressBar');

                            if (data.status === 'completed') {
                                progressIcon.className = 'fas fa-check-circle me-2 text-success';
                                progressText.textContent = 'Завершено';
                                progressBar.classList.remove('progress-bar-animated', 'bg-primary');
                                progressBar.classList.add('bg-success');
                                addLog('🏁 Обработка завершена успешно!', 'success');
                            } else {
                                progressIcon.className = 'fas fa-times-circle me-2 text-danger';
                                progressText.textContent = 'Завершено с ошибками';
                                progressBar.classList.remove('progress-bar-animated', 'bg-primary');
                                progressBar.classList.add('bg-danger');
                                addLog('Обработка завершена с ошибками', 'error');
                            }

                            // Останавливаем таймер
                            const elapsed = Math.floor((Date.now() - startTime) / 1000);
                            elapsedTime.textContent = formatTime(elapsed);
                        }
                    })
                    .catch(err => {
                        // Не показываем ошибку каждые 1.5 секунды, только если это не первая попытка
                        if (statusInterval) {
                            // Игнорируем, просто не обновляем
                        }
                    });
            }

            // ----- Сброс -----

            resetBtn.addEventListener('click', function () {
                if (isProcessing) {
                    if (!confirm('Обработка ещё идёт. Вы уверены, что хотите сбросить?')) return;
                }
                resetAll();
                addLog('Сброс выполнен', 'info');
            });

            // ----- Очистка логов -----

            clearLogsBtn.addEventListener('click', function () {
                clearLogs();
                addLog('Лог очищен', 'info');
            });

            // ----- Скачивание ошибок -----

            downloadErrorsBtn.addEventListener('click', function () {
                if (this.disabled) return;
                if (currentJobId) {
                    window.location.href = 'api/download-errors.php?job_id=' + currentJobId;
                } else {
                    addLog('Нет данных об ошибках для скачивания', 'error');
                }
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-check me-2"></i>Скачано';
            });

            // ----- Инициализация -----

            clearLogs();
            setStatus('idle', 'Ожидание');

            addLog('Готов к работе. Загрузите файл и нажмите "Запустить парсинг"', 'info');

        });

        function clearDatabase() {
            if (!confirm('Точно удалить ВСЕ записи из базы?')) {
                return;
            }

            fetch('api/clear.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Удалено записей: ' + data.deleted_count);
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                })
                .catch(err => alert('Ошибка соединения: ' + err.message));
        };

        function resetDownloadButton() {
            downloadErrorsBtn.disabled = false;
            downloadErrorsBtn.innerHTML = '<i class="fas fa-download me-2"></i>Скачать файл с ошибками';
            downloadErrorsBtn.className = 'btn btn-outline-danger btn-sm';
        };
    </script>

</body>

</html>
