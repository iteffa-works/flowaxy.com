/**
 * JavaScript для редактора темы
 */

let codeEditor = null;
let originalContent = '';
let isModified = false;
let editorSettings = {
    enableSyntaxHighlighting: true,
    showEmptyFolders: false
};

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
    
    // Проверяем, включена ли подсветка синтаксиса
    const enableSyntaxHighlighting = textarea.getAttribute('data-syntax-highlighting') !== '0';
    // Обновляем глобальную настройку
    editorSettings.enableSyntaxHighlighting = enableSyntaxHighlighting;
    
    const extension = textarea.getAttribute('data-extension');
    const mode = enableSyntaxHighlighting ? getCodeMirrorMode(extension) : null;
    
    try {
        codeEditor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: mode,
            theme: enableSyntaxHighlighting ? 'monokai' : 'default',
            indentUnit: 4,
            indentWithTabs: false,
            lineWrapping: true,
            matchBrackets: enableSyntaxHighlighting,
            autoCloseBrackets: enableSyntaxHighlighting,
            foldGutter: enableSyntaxHighlighting,
            gutters: enableSyntaxHighlighting ? ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'] : ['CodeMirror-linenumbers']
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
    // Загружаем настройки редактора
    loadEditorSettings();
    
    // Инициализация CodeMirror
    initCodeMirror();
    
    // Инициализация древовидной структуры файлов
    initFileTree();
});

/**
 * Загрузка настроек редактора
 */
function loadEditorSettings() {
    const textarea = document.getElementById('theme-file-editor');
    if (textarea) {
        editorSettings.enableSyntaxHighlighting = textarea.getAttribute('data-syntax-highlighting') !== '0';
    }
    
    // Также загружаем из настроек при открытии модального окна
    // настройки будут обновлены в openEditorSettings()
}

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
        if (typeof showNotification !== 'undefined') {
            showNotification('Редактор не ініціалізовано', 'warning');
        }
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
    
    if (isModified) {
        showConfirmDialog(
            'Скасувати зміни',
            'Ви впевнені, що хочете скасувати всі зміни? Внесені зміни будуть втрачені.',
            function() {
                codeEditor.setValue(originalContent);
                isModified = false;
                updateEditorStatus();
            }
        );
        return;
    }
    
    codeEditor.setValue(originalContent);
    isModified = false;
    updateEditorStatus();
}

/**
 * Создание нового файла
 */
function createNewFile() {
    createNewFileInFolder(null, '');
}

/**
 * Создание нового файла в конкретной папке
 */
function createNewFileInFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Находим целевую папку в дереве
    let targetFolder = null;
    if (folderPath) {
        const folderPathParts = folderPath.split('/');
        let currentFolder = document.querySelector('.file-tree-root');
        
        for (const folderName of folderPathParts) {
            if (!currentFolder) break;
            const folders = currentFolder.querySelectorAll('.file-tree-folder');
            currentFolder = Array.from(folders).find(f => {
                const header = f.querySelector('.file-tree-folder-header');
                return header && header.textContent.trim() === folderName;
            });
        }
        
        if (currentFolder) {
            const content = currentFolder.querySelector('.file-tree-folder-content');
            if (content) {
                targetFolder = content;
                // Раскрываем папку
                const folderHeader = currentFolder.querySelector('.file-tree-folder-header');
                const folderContent = currentFolder.querySelector('.file-tree-folder-content');
                if (folderHeader && folderContent) {
                    folderHeader.classList.add('expanded');
                    folderContent.classList.add('expanded');
                }
            }
        }
    } else {
        // Если папка не указана, добавляем в корень
        const rootFolder = document.querySelector('.file-tree-root');
        if (rootFolder) {
            const rootContent = rootFolder.querySelector('.file-tree-folder-content');
            if (rootContent) {
                targetFolder = rootContent;
            }
        }
    }
    
    if (!targetFolder) {
        showNotification('Не вдалося знайти папку', 'danger');
        return;
    }
    
    // Удаляем существующие инлайн-формы
    document.querySelectorAll('.file-tree-inline-form').forEach(form => form.remove());
    
    // Создаем инлайн-форму
    const inlineForm = document.createElement('div');
    inlineForm.className = 'file-tree-inline-form';
    inlineForm.innerHTML = `
        <input type="text" 
               class="file-tree-inline-input" 
               placeholder="наприклад: header.php"
               autofocus>
        <button type="button" class="file-tree-inline-btn file-tree-inline-btn-success" onclick="submitInlineCreateFile(this, '${folderPath || ''}')">
            <i class="fas fa-check"></i>
        </button>
        <button type="button" class="file-tree-inline-btn file-tree-inline-btn-cancel" onclick="cancelInlineForm(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Добавляем обработчик Enter
    const input = inlineForm.querySelector('.file-tree-inline-input');
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitInlineCreateFile(this.nextElementSibling, folderPath || '');
        } else if (e.key === 'Escape') {
            cancelInlineForm(this.nextElementSibling.nextElementSibling);
        }
    });
    
    // Вставляем форму в начало папки
    targetFolder.insertBefore(inlineForm, targetFolder.firstChild);
    input.focus();
}

/**
 * Отправка инлайн-формы создания файла
 */
function submitInlineCreateFile(button, folderPath) {
    const form = button.closest('.file-tree-inline-form');
    const input = form.querySelector('.file-tree-inline-input');
    const fileName = input.value.trim();
    
    if (!fileName) {
        showNotification('Введіть назву файлу', 'warning');
        input.focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_file');
    formData.append('theme', new URLSearchParams(window.location.search).get('theme') || '');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    
    // Формируем полный путь к файлу
    let fullPath = fileName;
    if (folderPath) {
        fullPath = folderPath + '/' + fileName;
    }
    formData.append('file', fullPath);
    formData.append('content', '');
    
    // Блокируем кнопку
    button.disabled = true;
    input.disabled = true;
    
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
            form.remove();
            
            // Перезагружаем страницу для обновления дерева файлов
            const url = new URL(window.location.href);
            url.searchParams.set('file', data.path);
            window.location.href = url.toString();
        } else {
            showNotification(data.error || 'Помилка створення', 'danger');
            button.disabled = false;
            input.disabled = false;
            input.focus();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка створення файлу', 'danger');
        button.disabled = false;
        input.disabled = false;
        input.focus();
    });
}

/**
 * Создание новой папки
 */
function createNewDirectory() {
    createNewDirectoryInFolder(null, '');
}

/**
 * Создание новой папки в конкретной папке
 */
function createNewDirectoryInFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Находим целевую папку в дереве
    let targetFolder = null;
    if (folderPath) {
        const folderPathParts = folderPath.split('/');
        let currentFolder = document.querySelector('.file-tree-root');
        
        for (const folderName of folderPathParts) {
            if (!currentFolder) break;
            const folders = currentFolder.querySelectorAll('.file-tree-folder');
            currentFolder = Array.from(folders).find(f => {
                const header = f.querySelector('.file-tree-folder-header');
                return header && header.textContent.trim() === folderName;
            });
        }
        
        if (currentFolder) {
            const content = currentFolder.querySelector('.file-tree-folder-content');
            if (content) {
                targetFolder = content;
                // Раскрываем папку
                const folderHeader = currentFolder.querySelector('.file-tree-folder-header');
                const folderContent = currentFolder.querySelector('.file-tree-folder-content');
                if (folderHeader && folderContent) {
                    folderHeader.classList.add('expanded');
                    folderContent.classList.add('expanded');
                }
            }
        }
    } else {
        // Если папка не указана, добавляем в корень
        const rootFolder = document.querySelector('.file-tree-root');
        if (rootFolder) {
            const rootContent = rootFolder.querySelector('.file-tree-folder-content');
            if (rootContent) {
                targetFolder = rootContent;
            }
        }
    }
    
    if (!targetFolder) {
        showNotification('Не вдалося знайти папку', 'danger');
        return;
    }
    
    // Удаляем существующие инлайн-формы
    document.querySelectorAll('.file-tree-inline-form').forEach(form => form.remove());
    
    // Создаем инлайн-форму
    const inlineForm = document.createElement('div');
    inlineForm.className = 'file-tree-inline-form';
    inlineForm.innerHTML = `
        <input type="text" 
               class="file-tree-inline-input" 
               placeholder="наприклад: layouts"
               autofocus>
        <button type="button" class="file-tree-inline-btn file-tree-inline-btn-success" onclick="submitInlineCreateDirectory(this, '${folderPath || ''}')">
            <i class="fas fa-check"></i>
        </button>
        <button type="button" class="file-tree-inline-btn file-tree-inline-btn-cancel" onclick="cancelInlineForm(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Добавляем обработчик Enter
    const input = inlineForm.querySelector('.file-tree-inline-input');
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitInlineCreateDirectory(this.nextElementSibling, folderPath || '');
        } else if (e.key === 'Escape') {
            cancelInlineForm(this.nextElementSibling.nextElementSibling);
        }
    });
    
    // Вставляем форму в начало папки
    targetFolder.insertBefore(inlineForm, targetFolder.firstChild);
    input.focus();
}

/**
 * Отправка инлайн-формы создания папки
 */
function submitInlineCreateDirectory(button, folderPath) {
    const form = button.closest('.file-tree-inline-form');
    const input = form.querySelector('.file-tree-inline-input');
    const directoryName = input.value.trim();
    
    if (!directoryName) {
        showNotification('Введіть назву папки', 'warning');
        input.focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_directory');
    formData.append('theme', new URLSearchParams(window.location.search).get('theme') || '');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    
    // Формируем полный путь к папке
    let fullPath = directoryName;
    if (folderPath) {
        fullPath = folderPath + '/' + directoryName;
    }
    formData.append('directory', fullPath);
    
    // Блокируем кнопку
    button.disabled = true;
    input.disabled = true;
    
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
            form.remove();
            // Перезагружаем страницу для обновления дерева
            setTimeout(() => {
                window.location.reload();
            }, 200);
        } else {
            showNotification(data.error || 'Помилка створення', 'danger');
            button.disabled = false;
            input.disabled = false;
            input.focus();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка створення папки', 'danger');
        button.disabled = false;
        input.disabled = false;
        input.focus();
    });
}

/**
 * Отмена инлайн-формы
 */
function cancelInlineForm(button) {
    const form = button.closest('.file-tree-inline-form');
    if (form) {
        form.remove();
    }
}

/**
 * Удаление текущего файла
 */
function deleteCurrentFile(event, filePath) {
    event.preventDefault();
    event.stopPropagation();
    
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea.getAttribute('data-theme');
    
    showConfirmDialog(
        'Видалити файл',
        'Ви впевнені, що хочете видалити цей файл? Цю дію неможливо скасувати.',
        function() {
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
    );
}

/**
 * Показать уведомление
 */
// showNotification теперь глобальная функция из notifications.php

/**
 * Кастомное модальное окно подтверждения
 */
function showConfirmDialog(title, message, onConfirm, confirmText = 'Підтвердити', cancelText = 'Скасувати') {
    const modal = document.getElementById('confirmDialogModal');
    if (!modal) {
        // Если модальное окно не найдено, используем стандартный confirm
        if (confirm(message)) {
            onConfirm();
        }
        return;
    }
    
    const titleEl = document.getElementById('confirmDialogTitle');
    const messageEl = document.getElementById('confirmDialogMessage');
    const confirmBtn = document.getElementById('confirmDialogButton');
    
    if (titleEl) {
        titleEl.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>' + title;
    }
    if (messageEl) {
        messageEl.textContent = message;
    }
    if (confirmBtn) {
        confirmBtn.textContent = confirmText;
        
        // Удаляем старые обработчики
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Добавляем новый обработчик
        newConfirmBtn.addEventListener('click', function() {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }
    
    // Показываем модальное окно
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
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
    
    // Обработка кликов по файлам
    document.querySelectorAll('.file-tree-item').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const filePath = this.getAttribute('data-file');
            if (filePath) {
                // Получаем тему из URL или из data-атрибута
                let theme = new URLSearchParams(window.location.search).get('theme') || '';
                if (!theme) {
                    const textarea = document.getElementById('theme-file-editor');
                    if (textarea) {
                        theme = textarea.getAttribute('data-theme') || '';
                    }
                }
                
                loadFileInEditor(filePath);
                
                // Обновляем URL без перезагрузки
                const url = new URL(window.location.href);
                if (theme) {
                    url.searchParams.set('theme', theme);
                }
                url.searchParams.set('file', filePath);
                window.history.pushState({}, '', url);
            }
        });
    });
    
    // Обработка кликов по папкам
    document.querySelectorAll('.file-tree-folder-header').forEach(header => {
        header.addEventListener('click', function(e) {
            // Не раскрываем папку, если кликнули на кнопку контекстного меню
            if (e.target.closest('.file-tree-context-menu')) {
                return;
            }
            
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

/**
 * Добавление файла в дерево файлов
 */
function addFileToTree(filePath, parentFolder) {
    const fileName = filePath.split('/').pop();
    const extension = fileName.split('.').pop() || '';
    const fileIcon = getFileIcon(extension);
    
    // Находим родительскую папку в дереве
    let targetFolder = null;
    if (parentFolder) {
        const folderPath = parentFolder.split('/');
        let currentFolder = document.querySelector('.file-tree-root');
        
        for (const folderName of folderPath) {
            if (!currentFolder) break;
            const folders = currentFolder.querySelectorAll('.file-tree-folder');
            currentFolder = Array.from(folders).find(f => {
                const header = f.querySelector('.file-tree-folder-header');
                return header && header.textContent.trim() === folderName;
            });
        }
        
        if (currentFolder) {
            const content = currentFolder.querySelector('.file-tree-folder-content');
            if (content) {
                targetFolder = content;
            }
        }
    } else {
        // Если папка не указана, добавляем в корень
        const rootFolder = document.querySelector('.file-tree-root');
        if (rootFolder) {
            const rootContent = rootFolder.querySelector('.file-tree-folder-content');
            if (rootContent) {
                targetFolder = rootContent;
            }
        }
    }
    
    if (!targetFolder) {
        // Если не нашли папку, просто перезагружаем страницу
        setTimeout(() => window.location.reload(), 500);
        return;
    }
    
    // Создаем элемент файла
    const fileWrapper = document.createElement('div');
    fileWrapper.className = 'file-tree-item-wrapper';
    fileWrapper.setAttribute('data-file-path', filePath);
    
    const fileUrl = window.location.href.split('?')[0] + '?theme=' + 
                   encodeURIComponent(new URLSearchParams(window.location.search).get('theme') || '') + 
                   '&file=' + encodeURIComponent(filePath);
    
    fileWrapper.innerHTML = `
        <a href="${fileUrl}" 
           class="file-tree-item"
           data-file="${filePath}">
            <i class="fas ${fileIcon} file-icon"></i>
            <span class="file-name">${fileName}</span>
        </a>
        <div class="file-tree-context-menu">
            <button type="button" 
                    class="context-menu-btn" 
                    onclick="downloadFile(event, '${filePath}')"
                    title="Скачати файл">
                <i class="fas fa-download"></i>
            </button>
            <button type="button" 
                    class="context-menu-btn context-menu-btn-danger" 
                    onclick="deleteCurrentFile(event, '${filePath}')"
                    title="Видалити файл">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    // Добавляем обработчик клика
    const fileLink = fileWrapper.querySelector('.file-tree-item');
    fileLink.addEventListener('click', function(e) {
        e.preventDefault();
        loadFileInEditor(filePath);
    });
    
    // Вставляем файл в правильное место (сортируем)
    const existingFiles = Array.from(targetFolder.querySelectorAll('.file-tree-item-wrapper'));
    let inserted = false;
    
    for (const existingFile of existingFiles) {
        const existingFileName = existingFile.querySelector('.file-name')?.textContent || '';
        if (fileName.localeCompare(existingFileName) < 0) {
            targetFolder.insertBefore(fileWrapper, existingFile);
            inserted = true;
            break;
        }
    }
    
    if (!inserted) {
        targetFolder.appendChild(fileWrapper);
    }
    
    // Раскрываем родительскую папку
    if (parentFolder) {
        const folderElement = targetFolder.closest('.file-tree-folder');
        if (folderElement) {
            const folderHeader = folderElement.querySelector('.file-tree-folder-header');
            const folderContent = folderElement.querySelector('.file-tree-folder-content');
            if (folderHeader && folderContent) {
                folderHeader.classList.add('expanded');
                folderContent.classList.add('expanded');
            }
        }
    }
}

/**
 * Получение иконки для файла по расширению
 */
function getFileIcon(extension) {
    const icons = {
        'php': 'fa-file-code',
        'js': 'fa-file-code',
        'css': 'fa-file-code',
        'html': 'fa-file-code',
        'htm': 'fa-file-code',
        'json': 'fa-file-code',
        'xml': 'fa-file-code',
        'yaml': 'fa-file-code',
        'yml': 'fa-file-code',
        'txt': 'fa-file-alt',
        'md': 'fa-file-alt',
        'jpg': 'fa-file-image',
        'jpeg': 'fa-file-image',
        'png': 'fa-file-image',
        'gif': 'fa-file-image',
        'svg': 'fa-file-image',
        'pdf': 'fa-file-pdf',
        'zip': 'fa-file-archive',
        'rar': 'fa-file-archive'
    };
    return icons[extension.toLowerCase()] || 'fa-file';
}

/**
 * Загрузка файла в редактор
 */
function loadFileInEditor(filePath) {
    let theme = new URLSearchParams(window.location.search).get('theme') || '';
    
    // Если тема не указана в URL, пробуем получить из data-атрибута textarea
    if (!theme) {
        const textarea = document.getElementById('theme-file-editor');
        if (textarea) {
            theme = textarea.getAttribute('data-theme') || '';
        }
    }
    
    if (!theme) {
        showNotification('Тему не вказано', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'get_file');
    formData.append('theme', theme);
    formData.append('file', filePath);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.content !== undefined) {
            // Обновляем активный файл в дереве
            document.querySelectorAll('.file-tree-item-wrapper').forEach(item => {
                item.classList.remove('active');
            });
            
            let fileWrapper = document.querySelector(`[data-file-path="${filePath}"]`);
            if (fileWrapper) {
                fileWrapper.classList.add('active');
            }
            
            // Показываем редактор, скрываем placeholder
            const editorPlaceholder = document.querySelector('.editor-placeholder-wrapper');
            const editorHeader = document.getElementById('editor-header');
            const editorBody = document.getElementById('editor-body');
            const editorFooter = document.getElementById('editor-footer');
            
            if (editorPlaceholder) {
                editorPlaceholder.style.display = 'none';
            }
            if (editorHeader) {
                editorHeader.style.display = 'block';
            }
            if (editorBody) {
                editorBody.style.display = 'block';
            }
            if (editorFooter) {
                editorFooter.style.display = 'block';
            }
            
            // Убеждаемся, что textarea существует и обновлен
            let textareaEl = document.getElementById('theme-file-editor');
            if (!textareaEl) {
                // Создаем textarea если его нет
                if (editorBody) {
                    textareaEl = document.createElement('textarea');
                    textareaEl.id = 'theme-file-editor';
                    editorBody.appendChild(textareaEl);
                }
            }
            
            if (textareaEl) {
                // Обновляем атрибуты textarea
                const extension = filePath.split('.').pop() || '';
                textareaEl.setAttribute('data-theme', theme);
                textareaEl.setAttribute('data-file', filePath);
                textareaEl.setAttribute('data-extension', extension);
                textareaEl.setAttribute('data-syntax-highlighting', editorSettings.enableSyntaxHighlighting ? '1' : '0');
                textareaEl.value = data.content;
            }
            
            // Загружаем содержимое в редактор
            if (codeEditor) {
                // Если редактор уже инициализирован, обновляем его
                codeEditor.setValue(data.content);
                originalContent = data.content;
                isModified = false;
                updateEditorStatus();
                
                // Обновляем режим редактора
                const extension = filePath.split('.').pop() || '';
                if (editorSettings.enableSyntaxHighlighting && codeEditor) {
                    const mode = getCodeMirrorMode(extension);
                    codeEditor.setOption('mode', mode);
                } else if (codeEditor) {
                    codeEditor.setOption('mode', null);
                }
                if (codeEditor) {
                    codeEditor.refresh();
                }
            } else {
                // Если редактор еще не инициализирован, инициализируем его
                if (typeof CodeMirror !== 'undefined' && textareaEl) {
                    initCodeMirror();
                }
            }
            
            // Обновляем заголовок файла
            const fileTitle = document.querySelector('.editor-file-title');
            if (fileTitle) {
                const icon = fileTitle.querySelector('i');
                fileTitle.innerHTML = '';
                if (icon) {
                    fileTitle.appendChild(icon);
                } else {
                    const newIcon = document.createElement('i');
                    newIcon.className = 'fas fa-file-code me-2';
                    fileTitle.appendChild(newIcon);
                }
                fileTitle.appendChild(document.createTextNode(filePath));
            }
            
            // Прокручиваем к активному файлу
            if (fileWrapper) {
                fileWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            // Раскрываем путь к файлу
            expandPathToFile(filePath);
        } else {
            showNotification(data.error || 'Помилка завантаження файлу', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка завантаження файлу', 'danger');
    });
}

/**
 * Обновление дерева файлов
 */
function refreshFileTree() {
    window.location.reload();
}

/**
 * Открытие настроек редактора
 */
function openEditorSettings() {
    // Загружаем текущие настройки
    const formData = new FormData();
    formData.append('action', 'get_editor_settings');
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
        if (data.success && data.settings) {
            // Обновляем глобальные настройки
            editorSettings.showEmptyFolders = data.settings.show_empty_folders === '1';
            editorSettings.enableSyntaxHighlighting = data.settings.enable_syntax_highlighting === '1';
            
            // Устанавливаем значения чекбоксов
            document.getElementById('showEmptyFolders').checked = editorSettings.showEmptyFolders;
            document.getElementById('enableSyntaxHighlighting').checked = editorSettings.enableSyntaxHighlighting;
        }
        
        // Настраиваем автоматическое сохранение при изменении
        setupAutoSaveEditorSettings();
        
        // Показываем модальное окно
        const modal = new bootstrap.Modal(document.getElementById('editorSettingsModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error:', error);
        // Показываем модальное окно даже при ошибке
        const modal = new bootstrap.Modal(document.getElementById('editorSettingsModal'));
        modal.show();
        setupAutoSaveEditorSettings();
    });
}

/**
 * Настройка автоматического сохранения настроек редактора
 */
function setupAutoSaveEditorSettings() {
    const showEmptyFolders = document.getElementById('showEmptyFolders');
    const enableSyntaxHighlighting = document.getElementById('enableSyntaxHighlighting');
    
    if (!showEmptyFolders || !enableSyntaxHighlighting) {
        return;
    }
    
    // Удаляем старые обработчики, если они есть (удаляем атрибут data-listener)
    if (showEmptyFolders.dataset.listener) {
        showEmptyFolders.removeEventListener('change', autoSaveEditorSettings);
    }
    if (enableSyntaxHighlighting.dataset.listener) {
        enableSyntaxHighlighting.removeEventListener('change', autoSaveEditorSettings);
    }
    
    // Добавляем обработчики изменения
    showEmptyFolders.addEventListener('change', autoSaveEditorSettings);
    showEmptyFolders.dataset.listener = 'true';
    
    enableSyntaxHighlighting.addEventListener('change', autoSaveEditorSettings);
    enableSyntaxHighlighting.dataset.listener = 'true';
}

/**
 * Автоматическое сохранение настроек редактора
 */
function autoSaveEditorSettings() {
    const formData = new FormData();
    formData.append('action', 'save_editor_settings');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('show_empty_folders', document.getElementById('showEmptyFolders').checked ? '1' : '0');
    formData.append('enable_syntax_highlighting', document.getElementById('enableSyntaxHighlighting').checked ? '1' : '0');
    
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
            // Обновляем глобальные настройки
            editorSettings.showEmptyFolders = document.getElementById('showEmptyFolders').checked;
            editorSettings.enableSyntaxHighlighting = document.getElementById('enableSyntaxHighlighting').checked;
            
            // Если редактор уже инициализирован и настройка подсветки изменилась, перезагружаем страницу
            if (codeEditor) {
                const newHighlighting = editorSettings.enableSyntaxHighlighting;
                const currentMode = codeEditor.getOption('mode');
                if ((newHighlighting && !currentMode) || (!newHighlighting && currentMode)) {
                    // Настройка изменилась, нужно перезагрузить страницу для применения
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                    return;
                }
            }
            
            showNotification('Налаштування збережено', 'success');
        } else {
            showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка збереження налаштувань', 'danger');
    });
}

/**
 * Сохранение настроек редактора
 */
function saveEditorSettings() {
    const form = document.getElementById('editorSettingsForm');
    const formData = new FormData(form);
    formData.append('action', 'save_editor_settings');
    formData.append('show_empty_folders', document.getElementById('showEmptyFolders').checked ? '1' : '0');
    formData.append('enable_syntax_highlighting', document.getElementById('enableSyntaxHighlighting').checked ? '1' : '0');
    
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
            showNotification('Налаштування успішно збережено', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editorSettingsModal')).hide();
            // Перезагружаем страницу для применения настроек
            setTimeout(() => {
                window.location.reload();
            }, 300);
        } else {
            showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка збереження налаштувань', 'danger');
    });
}

