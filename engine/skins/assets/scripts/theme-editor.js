/**
 * JavaScript для редактора темы
 */

let codeEditor = null;
let originalContent = '';
let isModified = false;

// Функция инициализации CodeMirror
function initCodeMirror() {
    // Проверяем наличие CodeMirror
    if (typeof CodeMirror === 'undefined') {
        console.error('CodeMirror не завантажено! Перевірте підключення скриптів.');
        // Повторная попытка через небольшую задержку
        setTimeout(initCodeMirror, 100);
        return;
    }
    
    // Инициализация CodeMirror
    const textarea = document.getElementById('theme-file-editor');
    if (!textarea) {
        return;
    }
    
    const extension = textarea.getAttribute('data-extension');
    const mode = getCodeMirrorMode(extension);
    
    try {
        codeEditor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: mode,
            theme: 'monokai',
            indentUnit: 4,
            indentWithTabs: false,
            lineWrapping: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            foldGutter: true,
            gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
        });
        
        // Скрываем textarea после успешной инициализации
        textarea.style.display = 'none';
        
        originalContent = codeEditor.getValue();
        
        codeEditor.on('change', function() {
            isModified = codeEditor.getValue() !== originalContent;
            updateEditorStatus();
        });
        
        // Обновляем размер редактора при изменении размера окна
        let resizeTimeout;
        window.addEventListener('resize', function() {
            if (codeEditor) {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    codeEditor.refresh();
                }, 100);
            }
        });
    } catch (error) {
        console.error('Помилка ініціалізації CodeMirror:', error);
        // Показываем textarea если CodeMirror не инициализировался
        textarea.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация CodeMirror
    initCodeMirror();
    
    // Инициализация древовидной структуры файлов
    initFileTree();
});

/**
 * Получение режима CodeMirror по расширению файла
 */
function getCodeMirrorMode(extension) {
    const modes = {
        'php': 'application/x-httpd-php',
        'js': 'javascript',
        'json': {name: 'javascript', json: true},
        'css': 'css',
        'html': 'htmlmixed',
        'htm': 'htmlmixed',
        'xml': 'xml',
        'yaml': 'yaml',
        'yml': 'yaml',
        'md': 'markdown'
    };
    
    return modes[extension.toLowerCase()] || 'text/plain';
}

/**
 * Обновление статуса редактора
 */
function updateEditorStatus() {
    const statusEl = document.getElementById('editor-status');
    if (statusEl) {
        if (isModified) {
            statusEl.textContent = 'Є незбережені зміни';
            statusEl.className = 'text-warning small';
        } else {
            statusEl.textContent = 'Готово до редагування';
            statusEl.className = 'text-muted small';
        }
    }
}

/**
 * Сохранение файла
 */
function saveFile() {
    if (!codeEditor) {
        alert('Редактор не ініціалізовано');
        return;
    }
    
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea.getAttribute('data-theme');
    const file = textarea.getAttribute('data-file');
    const content = codeEditor.getValue();
    
    const formData = new FormData();
    formData.append('action', 'save_file');
    formData.append('theme', theme);
    formData.append('file', file);
    formData.append('content', content);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            originalContent = content;
            isModified = false;
            updateEditorStatus();
            showNotification('Файл успішно збережено', 'success');
        } else {
            showNotification(data.error || 'Помилка збереження', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка збереження файлу', 'danger');
    });
}

/**
 * Сброс изменений в редакторе
 */
function resetEditor() {
    if (!codeEditor) return;
    
    if (isModified && confirm('Скасувати всі зміни?')) {
        codeEditor.setValue(originalContent);
        isModified = false;
        updateEditorStatus();
    }
}

/**
 * Создание нового файла
 */
function createNewFile() {
    const modal = new bootstrap.Modal(document.getElementById('createFileModal'));
    // Очищаем поле папки
    document.getElementById('createFileFolder').value = '';
    modal.show();
}

/**
 * Создание нового файла в конкретной папке
 */
function createNewFileInFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const modal = new bootstrap.Modal(document.getElementById('createFileModal'));
    // Устанавливаем путь к папке
    document.getElementById('createFileFolder').value = folderPath || '';
    modal.show();
}

/**
 * Отправка формы создания файла
 */
function submitCreateFile() {
    const form = document.getElementById('createFileForm');
    const formData = new FormData(form);
    formData.append('action', 'create_file');
    
    // Формируем полный путь к файлу
    const folder = formData.get('folder') || '';
    const fileName = formData.get('file') || '';
    if (folder && fileName) {
        formData.set('file', folder + '/' + fileName);
    } else if (fileName) {
        formData.set('file', fileName);
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Файл успішно створено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createFileModal')).hide();
            setTimeout(() => {
                window.location.href = window.location.href.split('&file=')[0] + '&file=' + encodeURIComponent(data.path);
            }, 500);
        } else {
            showNotification(data.error || 'Помилка створення', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка створення файлу', 'danger');
    });
}

/**
 * Создание новой папки
 */
function createNewDirectory() {
    const modal = new bootstrap.Modal(document.getElementById('createDirectoryModal'));
    // Очищаем поле папки
    document.getElementById('createDirectoryFolder').value = '';
    modal.show();
}

/**
 * Создание новой папки в конкретной папке
 */
function createNewDirectoryInFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const modal = new bootstrap.Modal(document.getElementById('createDirectoryModal'));
    // Устанавливаем путь к папке
    document.getElementById('createDirectoryFolder').value = folderPath || '';
    modal.show();
}

/**
 * Отправка формы создания папки
 */
function submitCreateDirectory() {
    const form = document.getElementById('createDirectoryForm');
    const formData = new FormData(form);
    formData.append('action', 'create_directory');
    
    // Формируем полный путь к папке
    const folder = formData.get('folder') || '';
    const directoryName = formData.get('directory') || '';
    if (folder && directoryName) {
        formData.set('directory', folder + '/' + directoryName);
    } else if (directoryName) {
        formData.set('directory', directoryName);
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Папку успішно створено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createDirectoryModal')).hide();
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showNotification(data.error || 'Помилка створення', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка створення папки', 'danger');
    });
}

/**
 * Удаление текущего файла
 */
function deleteCurrentFile(event, filePath) {
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('Ви впевнені, що хочете видалити цей файл? Цю дію неможливо скасувати.')) {
        return;
    }
    
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea.getAttribute('data-theme');
    
    const formData = new FormData();
    formData.append('action', 'delete_file');
    formData.append('theme', theme);
    formData.append('file', filePath);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Файл успішно видалено', 'success');
            setTimeout(() => {
                window.location.href = window.location.href.split('&file=')[0];
            }, 500);
        } else {
            showNotification(data.error || 'Помилка видалення', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка видалення файлу', 'danger');
    });
}

/**
 * Показать уведомление
 */
function showNotification(message, type) {
    if (typeof showAlert === 'function') {
        showAlert(message, type);
    } else {
        alert(message);
    }
}

/**
 * Автоматическое раскрытие папок до выбранного файла
 */
function expandPathToFile(filePath) {
    if (!filePath) return;
    
    // Получаем все папки
    const folders = document.querySelectorAll('.file-tree-folder');
    
    folders.forEach(folder => {
        const folderPath = folder.getAttribute('data-folder-path');
        if (!folderPath) return;
        
        // Нормализуем пути для сравнения
        const folderPathNormalized = folderPath.endsWith('/') ? folderPath : folderPath + '/';
        const filePathNormalized = filePath;
        
        // Проверяем, начинается ли путь к файлу с пути папки
        if (filePathNormalized.startsWith(folderPathNormalized)) {
            const header = folder.querySelector('.file-tree-folder-header');
            const content = folder.querySelector('.file-tree-folder-content');
            
            if (header && content) {
                header.classList.add('expanded');
                content.classList.add('expanded');
            }
        }
    });
}

/**
 * Инициализация древовидной структуры файлов
 */
function initFileTree() {
    // Автоматически раскрываем путь к выбранному файлу
    const activeFile = document.querySelector('.file-tree-item-wrapper.active');
    if (activeFile) {
        const filePath = activeFile.getAttribute('data-file-path') || 
                        activeFile.querySelector('.file-tree-item')?.getAttribute('data-file');
        if (filePath) {
            expandPathToFile(filePath);
        }
    }
    
    // Обработка кликов по папкам
    document.querySelectorAll('.file-tree-folder-header').forEach(header => {
        header.addEventListener('click', function() {
            const folder = this.closest('.file-tree-folder');
            const content = folder.querySelector('.file-tree-folder-content');
            const isExpanded = this.classList.contains('expanded');
            
            if (isExpanded) {
                this.classList.remove('expanded');
                content.classList.remove('expanded');
            } else {
                this.classList.add('expanded');
                content.classList.add('expanded');
            }
        });
    });
}

// Предупреждение при уходе со страницы с несохраненными изменениями
window.addEventListener('beforeunload', function(e) {
    if (isModified) {
        e.preventDefault();
        e.returnValue = 'У вас є незбережені зміни. Ви впевнені, що хочете покинути сторінку?';
    }
});

/**
 * Загрузка файла в папку
 */
function uploadFileToFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const modal = new bootstrap.Modal(document.getElementById('uploadFileModal'));
    document.getElementById('uploadFileFolder').value = folderPath || '';
    document.getElementById('uploadFileInput').value = '';
    document.getElementById('uploadFileProgress').classList.add('d-none');
    modal.show();
}

/**
 * Отправка формы загрузки файла
 */
function submitUploadFile() {
    const form = document.getElementById('uploadFileForm');
    const fileInput = document.getElementById('uploadFileInput');
    const progressBar = document.getElementById('uploadFileProgress');
    const progressBarInner = progressBar.querySelector('.progress-bar');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        showNotification('Виберіть файл для завантаження', 'warning');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'upload_file');
    
    progressBar.classList.remove('d-none');
    progressBarInner.style.width = '0%';
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBarInner.style.width = percentComplete + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    showNotification('Файл успішно завантажено', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('uploadFileModal')).hide();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    showNotification(data.error || 'Помилка завантаження', 'danger');
                }
            } catch (e) {
                showNotification('Помилка обробки відповіді', 'danger');
            }
        } else {
            showNotification('Помилка завантаження файлу', 'danger');
        }
        progressBar.classList.add('d-none');
    });
    
    xhr.addEventListener('error', function() {
        showNotification('Помилка завантаження файлу', 'danger');
        progressBar.classList.add('d-none');
    });
    
    xhr.open('POST', window.location.href);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(formData);
}

/**
 * Скачивание файла
 */
function downloadFile(event, filePath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const theme = new URLSearchParams(window.location.search).get('theme') || '';
    const url = window.location.href.split('?')[0] + '?action=download_file&theme=' + encodeURIComponent(theme) + '&file=' + encodeURIComponent(filePath);
    window.location.href = url;
}

/**
 * Скачивание папки (ZIP)
 */
function downloadFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const theme = new URLSearchParams(window.location.search).get('theme') || '';
    const url = window.location.href.split('?')[0] + '?action=download_folder&theme=' + encodeURIComponent(theme) + '&folder=' + encodeURIComponent(folderPath);
    window.location.href = url;
}

