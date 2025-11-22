/**
 * JavaScript для редактора темы
 */

let codeEditor = null;
let originalContent = '';
let isModified = false;
let editorSettings = {
    enableSyntaxHighlighting: true,
    showEmptyFolders: true
};
let settingsSetupDone = false; // Флаг, что обработчики настроек уже установлены

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
        isModified = false;
        
        // Инициализируем статус при загрузке
        updateEditorStatus();
        
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
        
        // Применяем настройки после инициализации
        setTimeout(function() {
            applyEditorSettingsToCodeMirror();
        }, 100);
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
    
    // Проверяем, есть ли открытый файл при загрузке страницы
    const urlParams = new URLSearchParams(window.location.search);
    const file = urlParams.get('file');
    const folder = urlParams.get('folder');
    const mode = urlParams.get('mode');
    
    // Если файл открыт, показываем футер
    if (file && !folder && !mode) {
        const editorFooter = document.getElementById('editor-footer');
        const editorHeader = document.getElementById('editor-header');
        const editorBody = document.getElementById('editor-body');
        
        if (editorFooter) {
            editorFooter.style.setProperty('display', 'flex', 'important');
        }
        if (editorHeader) {
            editorHeader.style.display = 'block';
        }
        if (editorBody) {
            editorBody.style.display = 'block';
        }
    }
    
    // Проверяем наличие параметров в URL для автоматического показа режимов
    if (folder) {
        // Небольшая задержка чтобы дерево файлов успело инициализироваться
        setTimeout(function() {
            showUploadFiles(folder);
        }, 300);
    } else if (mode === 'settings') {
        // Небольшая задержка чтобы дерево файлов успело инициализироваться
        setTimeout(function() {
            showEditorSettings();
        }, 300);
    }
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
    const cancelBtn = document.getElementById('cancel-btn');
    const statusIcon = document.getElementById('editor-status-icon');
    
    if (!statusEl) {
        return;
    }
    
        if (isModified) {
            statusEl.textContent = 'Є незбережені зміни';
            statusEl.className = 'text-warning small';
        // Меняем точку на предупреждение (желтая)
        if (statusIcon) {
            statusIcon.className = 'editor-status-dot text-warning me-2';
        }
        // Показываем кнопку "Скасувати"
        if (cancelBtn) {
            cancelBtn.style.display = '';
        }
        } else {
            statusEl.textContent = 'Готово до редагування';
            statusEl.className = 'text-muted small';
        // Меняем точку на успех (зеленая)
        if (statusIcon) {
            statusIcon.className = 'editor-status-dot text-success me-2';
        }
        // Скрываем кнопку "Скасувати"
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
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
    
    // Используем AjaxHelper если доступен
    const url = window.location.href.split('?')[0];
    const requestFn = typeof AjaxHelper !== 'undefined' 
        ? AjaxHelper.post(url, { action: 'save_file', theme: theme, file: file, content: content })
        : fetch(url, {
        method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ 
                action: 'save_file', 
                theme: theme, 
                file: file, 
                content: content,
                csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
            })
        }).then(r => r.json());
    
    requestFn
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
    if (!codeEditor) {
        return;
    }
    
    // Если изменений нет, ничего не делаем
    if (!isModified) {
        return;
    }
    
    // Если есть изменения, спрашиваем подтверждение
    showConfirmDialog(
        'Скасувати зміни',
        'Ви впевнені, що хочете скасувати всі зміни? Внесені зміни будуть втрачені.',
        function() {
            // Восстанавливаем оригинальное содержимое
        codeEditor.setValue(originalContent);
            // Обновляем флаг изменений
        isModified = false;
            // Обновляем статус редактора (скроет кнопку "Скасувати" и изменит иконку)
        updateEditorStatus();
            // Показываем уведомление
            showNotification('Зміни скасовано', 'info');
    }
    );
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
    
    // Используем AjaxHelper если доступен
    const url = window.location.href.split('?')[0];
    const requestFn = typeof AjaxHelper !== 'undefined' 
        ? AjaxHelper.post(url, {
            action: 'create_file',
            theme: new URLSearchParams(window.location.search).get('theme') || '',
            file: fullPath,
            content: ''
        })
        : fetch(url, {
        method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
        }).then(r => r.json());
    
    requestFn
    .then(data => {
        if (data.success) {
            showNotification('Файл успішно створено', 'success');
            form.remove();
            
                // Обновляем дерево файлов через AJAX
                refreshFileTree();
                
                // Открываем созданный файл в редакторе
                if (data.path) {
                    setTimeout(() => {
                        loadFileInEditor(data.path);
                    }, 300);
                }
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
    
    // Используем AjaxHelper если доступен
    const url = window.location.href.split('?')[0];
    const requestFn = typeof AjaxHelper !== 'undefined' 
        ? AjaxHelper.post(url, {
            action: 'create_directory',
            theme: new URLSearchParams(window.location.search).get('theme') || '',
            directory: fullPath
        })
        : fetch(url, {
        method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
        }).then(r => r.json());
    
    requestFn
    .then(data => {
        if (data.success) {
            showNotification('Папку успішно створено', 'success');
            form.remove();
                // Обновляем дерево файлов через AJAX
                refreshFileTree();
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
            // Используем AjaxHelper если доступен
            const url = window.location.href.split('?')[0];
            const requestFn = typeof AjaxHelper !== 'undefined' 
                ? AjaxHelper.post(url, {
                    action: 'delete_file',
                    theme: theme,
                    file: filePath
                })
                : fetch(url, {
        method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({
                        action: 'delete_file',
                        theme: theme,
                        file: filePath,
                        csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
                    })
                }).then(r => r.json());
            
            requestFn
    .then(data => {
        if (data.success) {
            showNotification('Файл успішно видалено', 'success');
                    
                    // Закрываем редактор, если удален открытый файл
                    const textarea = document.getElementById('theme-file-editor');
                    const currentFile = textarea ? textarea.getAttribute('data-file') : '';
                    if (currentFile === filePath) {
                        // Скрываем редактор, показываем placeholder
                        const editorPlaceholder = document.querySelector('.editor-placeholder-wrapper');
                        const editorHeader = document.getElementById('editor-header');
                        const editorBody = document.getElementById('editor-body');
                        const editorFooter = document.getElementById('editor-footer');
                        
                        if (editorPlaceholder) {
                            editorPlaceholder.style.display = 'flex';
                        }
                        if (editorHeader) {
                            editorHeader.style.display = 'none';
                        }
                        if (editorBody) {
                            editorBody.style.display = 'none';
                        }
                        if (editorFooter) {
                            editorFooter.style.display = 'none';
                        }
                        
                        // Обновляем URL без перезагрузки
                        const url = new URL(window.location.href);
                        url.searchParams.delete('file');
                        window.history.pushState({ path: url.href }, '', url.href);
                    }
                    
                    // Обновляем дерево файлов через AJAX
                    refreshFileTree();
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
    
    // Обработка кликов по файлам - используем делегирование событий вместо добавления обработчиков к каждому элементу
    // Удаляем старые обработчики, если они есть
    const fileTree = document.querySelector('.file-tree');
    if (fileTree) {
        // Используем делегирование событий - один обработчик на все дерево
        if (!fileTree.dataset.delegateHandler) {
            fileTree.addEventListener('click', function(e) {
                // Проверяем, что клик по файлу
                const fileLink = e.target.closest('.file-tree-item');
                if (fileLink) {
            e.preventDefault();
                    e.stopPropagation();
                    const filePath = fileLink.getAttribute('data-file');
            if (filePath) {
                        loadFile(e, filePath);
                    }
                }
            });
            fileTree.dataset.delegateHandler = 'true';
        }
    }
    
    // Обработка кликов по папкам
    document.querySelectorAll('.file-tree-folder-header').forEach(header => {
        header.addEventListener('click', function(e) {
            // Не раскрываем папку, если кликнули на кнопку контекстного меню
            if (e.target.closest('.file-tree-context-menu')) {
                return;
            }
            
            const folder = this.closest('.file-tree-folder');
            if (!folder) return;
            
            const content = folder.querySelector('.file-tree-folder-content');
            if (!content) return; // Если папка не имеет содержимого, ничего не делаем
            
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
 * Загрузка файла в папку (открывает встроенный режим загрузки)
 */
function uploadFileToFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Открываем встроенный режим загрузки вместо модального окна
    showUploadFiles(folderPath || '');
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
        // Если не нашли папку, обновляем дерево через AJAX
        refreshFileTree();
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
 * Загрузка файла (обработчик клика по файлу)
 */
let isLoadingFile = false;
function loadFile(event, filePath) {
    if (!filePath) {
        return;
    }
    
    // Защита от множественных вызовов
    if (isLoadingFile) {
        return;
    }
    
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Устанавливаем флаг загрузки
    isLoadingFile = true;
    
    // Обновляем активный файл в дереве
    document.querySelectorAll('.file-tree-item-wrapper').forEach(wrapper => {
        wrapper.classList.remove('active');
    });
    
    const clickedWrapper = event?.target.closest('.file-tree-item-wrapper');
    if (clickedWrapper) {
        clickedWrapper.classList.add('active');
    }
    
    // Загружаем файл в редактор
    loadFileInEditor(filePath).finally(() => {
        // Снимаем флаг загрузки после завершения
        isLoadingFile = false;
    });
}

/**
 * Загрузка файла в редактор через AJAX
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
        return Promise.reject(new Error('Тему не вказано'));
    }
    
    // Используем AjaxHelper для загрузки файла
    const url = window.location.href.split('?')[0];
    const requestFn = typeof AjaxHelper !== 'undefined' 
        ? AjaxHelper.post(url, { action: 'get_file', theme: theme, file: filePath })
        : fetch(url, {
        method: 'POST',
        headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({ 
                action: 'get_file', 
                theme: theme, 
                file: filePath,
                csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
            })
        }).then(response => {
            // Проверяем, что ответ является JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                });
            }
            return response.json();
        });
    
    return requestFn
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
            
            // Скрываем встроенные режимы (загрузка файлов, настройки)
            hideEmbeddedModes();
            
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
                editorFooter.style.setProperty('display', 'flex', 'important');
                // Восстанавливаем кнопки в футере
                const footerButtons = editorFooter.querySelector('.d-flex.gap-2');
                if (footerButtons) footerButtons.style.display = 'flex';
                const statusText = editorFooter.querySelector('#editor-status');
                if (statusText) statusText.style.display = 'block';
                const statusIcon = editorFooter.querySelector('#editor-status-icon');
                if (statusIcon) statusIcon.style.display = 'inline-block';
                // Восстанавливаем заголовок файла в хедере
                const fileTitle = editorHeader?.querySelector('.editor-file-title');
                if (fileTitle && filePath) {
                    const extension = filePath.split('.').pop() || '';
                    fileTitle.innerHTML = '<i class="fas fa-edit me-2"></i>' + escapeHtml(filePath);
                }
                // Восстанавливаем информацию о файле в хедере
                const fileInfo = editorHeader?.querySelector('.d-flex.justify-content-between > div:last-child');
                if (fileInfo) fileInfo.style.display = 'block';
                // Восстанавливаем оригинальную кнопку "Зберегти"
                const saveBtn = editorFooter.querySelector('button.btn-primary.btn-sm');
                if (saveBtn) {
                    // Восстанавливаем оригинальные значения если были изменены
                    if (saveBtn.getAttribute('data-original-onclick')) {
                        saveBtn.setAttribute('onclick', saveBtn.getAttribute('data-original-onclick'));
                        saveBtn.innerHTML = saveBtn.getAttribute('data-original-html');
                        saveBtn.removeAttribute('data-original-onclick');
                        saveBtn.removeAttribute('data-original-html');
                    } else if (saveBtn.getAttribute('onclick') === 'startFilesUpload()') {
                        // Если нет сохраненных значений, восстанавливаем вручную
                        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Зберегти';
                        saveBtn.setAttribute('onclick', 'saveFile()');
                    }
                    if (saveBtn.style.display === 'none') {
                        saveBtn.style.display = '';
                    }
                }
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
                
                // Убеждаемся, что статус и кнопки обновлены
                updateEditorStatus();
                
                // Применяем все настройки к редактору
                applyEditorSettingsToCodeMirror();
            } else {
                // Если редактор еще не инициализирован, инициализируем его
                if (typeof CodeMirror !== 'undefined' && textareaEl) {
                    initCodeMirror();
                }
            }
            
            // Обновляем заголовок файла
            const fileTitle = document.querySelector('.editor-file-title');
            if (fileTitle) {
                fileTitle.innerHTML = '';
                    const newIcon = document.createElement('i');
                newIcon.className = 'fas fa-edit me-2';
                    fileTitle.appendChild(newIcon);
                fileTitle.appendChild(document.createTextNode(filePath));
            }
            
            // Обновляем расширение и размер
            const extensionEl = document.getElementById('editor-extension');
            if (extensionEl) {
                extensionEl.textContent = filePath.split('.').pop()?.toUpperCase() || '';
            }
            
            // Обновляем размер файла если доступен
            if (data.size !== undefined) {
                const sizeEl = document.getElementById('editor-size');
                if (sizeEl) {
                    sizeEl.textContent = formatBytes(data.size);
                }
            }
            
            // Прокручиваем к активному файлу
            if (fileWrapper) {
                fileWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            // Раскрываем путь к файлу
            expandPathToFile(filePath);
            
            // Обновляем URL без перезагрузки
            const url = new URL(window.location.href);
            url.searchParams.set('file', filePath);
            // Удаляем параметры folder и mode, так как мы открываем файл
            url.searchParams.delete('folder');
            url.searchParams.delete('mode');
            window.history.pushState({ path: url.href }, '', url.href);
        } else {
            showNotification(data.error || 'Помилка завантаження файлу', 'danger');
            throw new Error(data.error || 'Помилка завантаження файлу');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка завантаження файлу', 'danger');
        throw error;
    });
}

/**
 * Обновление дерева файлов через AJAX
 */
function refreshFileTree() {
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea ? textarea.getAttribute('data-theme') : new URLSearchParams(window.location.search).get('theme') || '';
    
    if (!theme) {
        showNotification('Тему не вказано', 'warning');
        return;
    }
    
    // Получаем текущее значение настройки показа пустых папок из чекбокса
    const showEmptyCheckbox = document.getElementById('showEmptyFoldersInline') || document.getElementById('showEmptyFolders');
    const showEmptyFolders = showEmptyCheckbox ? (showEmptyCheckbox.checked ? '1' : '0') : (editorSettings.showEmptyFolders ? '1' : '0');
    
    // Используем AjaxHelper если доступен
    const url = window.location.href.split('?')[0];
    const requestFn = typeof AjaxHelper !== 'undefined' 
        ? AjaxHelper.post(url, { action: 'get_file_tree', theme: theme, show_empty_folders: showEmptyFolders })
        : fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ action: 'get_file_tree', theme: theme, show_empty_folders: showEmptyFolders, csrf_token: document.querySelector('input[name="csrf_token"]')?.value || '' })
        }).then(r => r.json());
    
    requestFn
        .then(data => {
            if (data.success && data.tree) {
                // Обновляем дерево файлов (передаем объект темы)
                updateFileTree(data.tree, data.theme || theme);
            } else {
                showNotification(data.error || 'Помилка оновлення дерева', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Помилка оновлення дерева файлів', 'danger');
        });
}

/**
 * Обновление дерева файлов в DOM
 */
function updateFileTree(treeArray, theme) {
    const treeContainer = document.querySelector('.file-tree');
    if (!treeContainer) {
        return;
    }
    
    // Рендерим новое дерево
    const treeHtml = renderFileTreeFromArray(treeArray, theme);
    
    // Сохраняем состояние раскрытия папок
    const expandedFolders = [];
    document.querySelectorAll('.file-tree-folder-header.expanded').forEach(header => {
        const path = header.getAttribute('data-folder-path');
        if (path) {
            expandedFolders.push(path);
        }
    });
    
    // Заменяем содержимое
    const rootFolder = treeContainer.querySelector('.file-tree-root');
    if (rootFolder) {
        const rootContent = rootFolder.querySelector('.file-tree-folder-content');
        if (rootContent) {
            rootContent.innerHTML = treeHtml;
            
            // Восстанавливаем раскрытие папок
            expandedFolders.forEach(path => {
                const folder = document.querySelector(`[data-folder-path="${path}"]`);
                if (folder) {
                    const header = folder.querySelector('.file-tree-folder-header');
                    const content = folder.querySelector('.file-tree-folder-content');
                    if (header && content) {
                        header.classList.add('expanded');
                        content.classList.add('expanded');
                        const icon = header.querySelector('.folder-icon');
                        if (icon) {
                            icon.className = 'fas fa-chevron-down folder-icon';
                        }
                    }
                }
            });
            
            // Обработчики уже установлены через делегирование событий, не нужно вызывать initFileTree() снова
        }
    }
}

/**
 * Рендеринг дерева файлов из массива
 */
function renderFileTreeFromArray(treeArray, theme, level = 1) {
    let html = '';
    
    // Получаем slug темы
    const themeSlug = theme && typeof theme === 'object' ? theme.slug : theme || '';
    
    treeArray.forEach(item => {
        if (item.type === 'folder') {
            html += `<div class="file-tree-folder" data-folder-path="${escapeHtml(item.path)}">
                <div class="file-tree-folder-header" data-folder-path="${escapeHtml(item.path)}">
                    <i class="fas fa-chevron-right folder-icon"></i>
                    <i class="fas fa-folder file-icon"></i>
                    <span class="file-name">${escapeHtml(item.name)}</span>
                    <div class="file-tree-context-menu">
                        <button type="button" class="context-menu-btn" onclick="createNewFileInFolder(event, '${escapeHtml(item.path)}')" title="Створити файл">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="context-menu-btn" onclick="createNewDirectoryInFolder(event, '${escapeHtml(item.path)}')" title="Створити папку">
                            <i class="fas fa-folder"></i>
                        </button>
                        <button type="button" class="context-menu-btn" onclick="uploadFileToFolder(event, '${escapeHtml(item.path)}')" title="Завантажити файл">
                            <i class="fas fa-upload"></i>
                        </button>
                        <button type="button" class="context-menu-btn" onclick="downloadFolder(event, '${escapeHtml(item.path)}')" title="Скачати папку">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>`;
            
            if (item.children && item.children.length > 0) {
                html += `<div class="file-tree-folder-content">
                    ${renderFileTreeFromArray(item.children, theme, level + 1)}
                </div>`;
            }
            
            html += '</div>';
        } else {
            html += `<div class="file-tree-item-wrapper" data-file-path="${escapeHtml(item.path)}">
                <a href="#" 
                   class="file-tree-item"
                   data-file="${escapeHtml(item.path)}">
                    <i class="fas fa-file-code file-icon"></i>
                    <span class="file-name">${escapeHtml(item.name)}</span>
                </a>
                <div class="file-tree-context-menu">
                    <button type="button" class="context-menu-btn" onclick="downloadFile(event, '${escapeHtml(item.path)}')" title="Скачати файл">
                        <i class="fas fa-download"></i>
                    </button>
                    <button type="button" class="context-menu-btn context-menu-btn-danger" onclick="deleteCurrentFile(event, '${escapeHtml(item.path)}')" title="Видалити файл">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`;
        }
    });
    
    return html;
}

/**
 * Экранирование HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Форматирование размера в байтах
 */
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Открытие настроек редактора (переключение на встроенный режим настроек)
 */
function openEditorSettings() {
    showEditorSettings();
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
    
    // Проверяем, не настроены ли уже обработчики
    if (showEmptyFolders.dataset.listener === 'true' && enableSyntaxHighlighting.dataset.listener === 'true') {
        return; // Обработчики уже установлены
    }
    
    // Удаляем старые обработчики, если они есть
    showEmptyFolders.removeEventListener('change', autoSaveEditorSettings);
    enableSyntaxHighlighting.removeEventListener('change', autoSaveEditorSettings);
    
    // Добавляем обработчики изменения
    showEmptyFolders.addEventListener('change', autoSaveEditorSettings);
    showEmptyFolders.dataset.listener = 'true';
    
    enableSyntaxHighlighting.addEventListener('change', autoSaveEditorSettings);
    enableSyntaxHighlighting.dataset.listener = 'true';
}

/**
 * Автоматическое сохранение настроек редактора
 */
let autoSaveTimeout = null;
let isSaving = false;

function autoSaveEditorSettings() {
    // Отменяем предыдущий запрос, если он еще не выполнен (debounce)
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    
    // Если уже выполняется сохранение, не запускаем новый запрос
    if (isSaving) {
        return;
    }
    
    // Запускаем сохранение с задержкой (debounce 300ms)
    autoSaveTimeout = setTimeout(() => {
        // Проверяем inline версию настроек, если есть, иначе модальную
        const highlightingEl = document.getElementById('enableSyntaxHighlightingInline') || document.getElementById('enableSyntaxHighlighting');
        const showEmptyEl = document.getElementById('showEmptyFoldersInline') || document.getElementById('showEmptyFolders');
        
        if (!highlightingEl || !showEmptyEl) {
            return;
        }
        
        const newHighlighting = highlightingEl.checked;
        const newShowEmptyFolders = showEmptyEl.checked;
        
        // Сохраняем старые значения для проверки изменений
        const oldHighlighting = editorSettings.enableSyntaxHighlighting;
        const oldShowEmptyFolders = editorSettings.showEmptyFolders;
        
        // Получаем URL без параметров
        const url = window.location.href.split('?')[0];
        
        // Устанавливаем флаг, что идет сохранение
        isSaving = true;
        
        // Если используется AjaxHelper, используем его
        if (typeof AjaxHelper !== 'undefined') {
            // Собираем все настройки
            const formData = {
                action: 'save_editor_settings',
                show_empty_folders: newShowEmptyFolders ? '1' : '0',
                enable_syntax_highlighting: newHighlighting ? '1' : '0',
                show_line_numbers: (document.getElementById('showLineNumbersInline') || document.getElementById('showLineNumbers'))?.checked ? '1' : '0',
                font_family: (document.getElementById('editorFontFamilyInline') || document.getElementById('editorFontFamily'))?.value || "'Consolas', monospace",
                font_size: (document.getElementById('editorFontSizeInline') || document.getElementById('editorFontSize'))?.value || '14',
                editor_theme: (document.getElementById('editorThemeInline') || document.getElementById('editorTheme'))?.value || 'monokai',
                indent_size: (document.getElementById('editorIndentSizeInline') || document.getElementById('editorIndentSize'))?.value || '4',
                word_wrap: (document.getElementById('wordWrapInline') || document.getElementById('wordWrap'))?.checked ? '1' : '0',
                auto_save: (document.getElementById('autoSaveInline') || document.getElementById('autoSave'))?.checked ? '1' : '0',
                auto_save_interval: (document.getElementById('autoSaveIntervalInline') || document.getElementById('autoSaveInterval'))?.value || '60'
            };
            
            AjaxHelper.post(url, formData)
            .then(data => {
                isSaving = false;
                
                if (data.success) {
                    // Обновляем глобальные настройки
                    editorSettings.showEmptyFolders = newShowEmptyFolders;
                    editorSettings.enableSyntaxHighlighting = newHighlighting;
                    
                    // Применяем все настройки к редактору
                    applyEditorSettingsToCodeMirror();
                    
                    // Обновляем дерево файлов, если изменилась настройка показа пустых папок
                    if (oldShowEmptyFolders !== newShowEmptyFolders) {
                        refreshFileTree();
                    }
                    
                    showNotification('Налаштування збережено', 'success');
                } else {
                    // Восстанавливаем значения чекбоксов при ошибке
                    document.getElementById('enableSyntaxHighlighting').checked = oldHighlighting;
                    document.getElementById('showEmptyFolders').checked = oldShowEmptyFolders;
                    showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
                }
            })
            .catch(error => {
                isSaving = false;
                console.error('Error:', error);
                // Восстанавливаем значения чекбоксов при ошибке
                document.getElementById('enableSyntaxHighlighting').checked = oldHighlighting;
                document.getElementById('showEmptyFolders').checked = oldShowEmptyFolders;
                showNotification('Помилка збереження налаштувань', 'danger');
            });
    } else {
        // Fallback на старый способ
    const formData = new FormData();
        formData.append('action', 'save_editor_settings');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
        formData.append('show_empty_folders', newShowEmptyFolders ? '1' : '0');
        formData.append('enable_syntax_highlighting', newHighlighting ? '1' : '0');
        formData.append('show_line_numbers', (document.getElementById('showLineNumbersInline') || document.getElementById('showLineNumbers'))?.checked ? '1' : '0');
        formData.append('font_family', (document.getElementById('editorFontFamilyInline') || document.getElementById('editorFontFamily'))?.value || "'Consolas', monospace");
        formData.append('font_size', (document.getElementById('editorFontSizeInline') || document.getElementById('editorFontSize'))?.value || '14');
        formData.append('editor_theme', (document.getElementById('editorThemeInline') || document.getElementById('editorTheme'))?.value || 'monokai');
        formData.append('indent_size', (document.getElementById('editorIndentSizeInline') || document.getElementById('editorIndentSize'))?.value || '4');
        formData.append('word_wrap', (document.getElementById('wordWrapInline') || document.getElementById('wordWrap'))?.checked ? '1' : '0');
        formData.append('auto_save', (document.getElementById('autoSaveInline') || document.getElementById('autoSave'))?.checked ? '1' : '0');
        formData.append('auto_save_interval', (document.getElementById('autoSaveIntervalInline') || document.getElementById('autoSaveInterval'))?.value || '60');
        
        fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
        .then(response => {
            // Проверяем, что ответ является JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                });
            }
            return response.json();
        })
    .then(data => {
                isSaving = false;
                
                if (data.success) {
                    editorSettings.showEmptyFolders = newShowEmptyFolders;
                    editorSettings.enableSyntaxHighlighting = newHighlighting;
                    
                    // Применяем все настройки к редактору
                    applyEditorSettingsToCodeMirror();
                    
                    if (oldShowEmptyFolders !== newShowEmptyFolders) {
                        refreshFileTree();
                    }
                    
                    showNotification('Налаштування збережено', 'success');
                } else {
                    document.getElementById('enableSyntaxHighlighting').checked = oldHighlighting;
                    document.getElementById('showEmptyFolders').checked = oldShowEmptyFolders;
                    showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
                }
    })
    .catch(error => {
                isSaving = false;
        console.error('Error:', error);
                document.getElementById('enableSyntaxHighlighting').checked = oldHighlighting;
                document.getElementById('showEmptyFolders').checked = oldShowEmptyFolders;
                showNotification('Помилка збереження налаштувань', 'danger');
            });
        }
    }, 300); // debounce 300ms
}

/**
 * Динамическое обновление подсветки синтаксиса в CodeMirror
 */
function updateCodeMirrorHighlighting(enable) {
    if (!codeEditor) {
        return;
    }
    
    const textarea = document.getElementById('theme-file-editor');
    if (!textarea) {
        return;
    }
    
    const extension = textarea.getAttribute('data-extension');
    
    if (enable) {
        // Включаем подсветку
        const mode = getCodeMirrorMode(extension);
        codeEditor.setOption('mode', mode);
        codeEditor.setOption('theme', 'monokai');
        codeEditor.setOption('matchBrackets', true);
        codeEditor.setOption('autoCloseBrackets', true);
        codeEditor.setOption('foldGutter', true);
        codeEditor.setOption('gutters', ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']);
    } else {
        // Выключаем подсветку
        codeEditor.setOption('mode', null);
        codeEditor.setOption('theme', 'default');
        codeEditor.setOption('matchBrackets', false);
        codeEditor.setOption('autoCloseBrackets', false);
        codeEditor.setOption('foldGutter', false);
        codeEditor.setOption('gutters', ['CodeMirror-linenumbers']);
    }
    
    codeEditor.refresh();
}

/**
 * Применение всех настроек редактора к CodeMirror
 */
function applyEditorSettingsToCodeMirror() {
    if (!codeEditor) {
        return;
    }
    
    // Получаем текущие настройки из формы
    const showLineNumbers = document.getElementById('showLineNumbersInline') || document.getElementById('showLineNumbers');
    const fontFamily = document.getElementById('editorFontFamilyInline') || document.getElementById('editorFontFamily');
    const fontSize = document.getElementById('editorFontSizeInline') || document.getElementById('editorFontSize');
    const editorTheme = document.getElementById('editorThemeInline') || document.getElementById('editorTheme');
    const indentSize = document.getElementById('editorIndentSizeInline') || document.getElementById('editorIndentSize');
    const wordWrap = document.getElementById('wordWrapInline') || document.getElementById('wordWrap');
    const syntaxHighlighting = document.getElementById('enableSyntaxHighlightingInline') || document.getElementById('enableSyntaxHighlighting');
    
    // Применяем настройки
    if (showLineNumbers) {
        codeEditor.setOption('lineNumbers', showLineNumbers.checked);
    }
    
    if (fontFamily) {
        const fontValue = fontFamily.value || "'Consolas', monospace";
        codeEditor.getWrapperElement().style.fontFamily = fontValue;
    }
    
    if (fontSize) {
        const sizeValue = fontSize.value || '14';
        codeEditor.getWrapperElement().style.fontSize = sizeValue + 'px';
    }
    
    if (editorTheme) {
        const themeValue = editorTheme.value || 'monokai';
        codeEditor.setOption('theme', themeValue);
    }
    
    if (indentSize) {
        const indentValue = parseInt(indentSize.value || '4', 10);
        codeEditor.setOption('indentUnit', indentValue);
        codeEditor.setOption('tabSize', indentValue);
    }
    
    if (wordWrap) {
        codeEditor.setOption('lineWrapping', wordWrap.checked);
    }
    
    // Обновляем подсветку синтаксиса
    if (syntaxHighlighting) {
        const textarea = document.getElementById('theme-file-editor');
        if (textarea) {
            const extension = textarea.getAttribute('data-extension');
            if (syntaxHighlighting.checked) {
                const mode = getCodeMirrorMode(extension);
                codeEditor.setOption('mode', mode);
            } else {
                codeEditor.setOption('mode', null);
            }
        }
    }
    
    codeEditor.refresh();
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
            // Настройки уже применены через autoSaveEditorSettings, ничего не делаем
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
 * Сохранение настроек редактора из футера (для режима настроек)
 */
function saveEditorSettingsFromFooter() {
    // Получаем все элементы настроек
    const showEmptyCheckbox = document.getElementById('showEmptyFoldersInline');
    const syntaxCheckbox = document.getElementById('enableSyntaxHighlightingInline');
    const showLineNumbers = document.getElementById('showLineNumbersInline');
    const fontFamily = document.getElementById('editorFontFamilyInline');
    const fontSize = document.getElementById('editorFontSizeInline');
    const editorTheme = document.getElementById('editorThemeInline');
    const indentSize = document.getElementById('editorIndentSizeInline');
    const wordWrap = document.getElementById('wordWrapInline');
    const autoSave = document.getElementById('autoSaveInline');
    const autoSaveInterval = document.getElementById('autoSaveIntervalInline');
    
    // Сохраняем старое значение для проверки изменений
    const oldShowEmptyFolders = editorSettings.showEmptyFolders;
    
    // Формируем данные для отправки
    const url = window.location.href.split('?')[0];
    const formData = {
        action: 'save_editor_settings',
        show_empty_folders: showEmptyCheckbox?.checked ? '1' : '0',
        enable_syntax_highlighting: syntaxCheckbox?.checked ? '1' : '0',
        show_line_numbers: showLineNumbers?.checked ? '1' : '0',
        font_family: fontFamily?.value || "'Consolas', monospace",
        font_size: fontSize?.value || '14',
        editor_theme: editorTheme?.value || 'monokai',
        indent_size: indentSize?.value || '4',
        word_wrap: wordWrap?.checked ? '1' : '0',
        auto_save: autoSave?.checked ? '1' : '0',
        auto_save_interval: autoSaveInterval?.value || '60'
    };
    
    if (typeof AjaxHelper !== 'undefined') {
        isSaving = true;
        AjaxHelper.post(url, formData)
            .then(data => {
                isSaving = false;
                if (data.success) {
                    // Обновляем глобальные настройки
                    if (showEmptyCheckbox) editorSettings.showEmptyFolders = showEmptyCheckbox.checked;
                    if (syntaxCheckbox) editorSettings.enableSyntaxHighlighting = syntaxCheckbox.checked;
                    
                    // Применяем настройки к редактору
                    applyEditorSettingsToCodeMirror();
                    
                    // Обновляем дерево файлов, если изменилась настройка показа пустых папок
                    if (showEmptyCheckbox && oldShowEmptyFolders !== showEmptyCheckbox.checked) {
                        refreshFileTree();
                    }
                    
                    // Обновляем статус в футере
                    const statusText = document.getElementById('editor-status');
                    if (statusText) {
                        statusText.textContent = 'Налаштування збережено';
                        statusText.className = 'text-success small';
                        // Через 2 секунды возвращаем обычный статус
                        setTimeout(function() {
                            statusText.textContent = 'Налаштування готові';
                            statusText.className = 'text-muted small';
                        }, 2000);
                    }
                    
                    showNotification('Налаштування збережено', 'success');
                } else {
                    showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
                }
            })
            .catch(error => {
                isSaving = false;
                console.error('Error:', error);
                showNotification('Помилка збереження налаштувань', 'danger');
            });
    } else {
        showNotification('Помилка: AjaxHelper не доступний', 'danger');
    }
}

/**
 * Скрыть встроенные режимы (загрузка файлов, настройки) и показать редактор
 */
function hideEmbeddedModes() {
    const uploadContent = document.getElementById('upload-mode-content');
    const settingsContent = document.getElementById('settings-mode-content');
    
    if (uploadContent) {
        uploadContent.style.display = 'none';
    }
    if (settingsContent) {
        settingsContent.style.display = 'none';
    }
}

/**
 * Показать режим загрузки файлов (встраивается вместо редактора)
 * Вызывается из контекстного меню папок
 */
function showUploadFiles(targetFolder = '') {
    // Заменяем ТОЛЬКО тело редактора (черное окно с кодом) на режим загрузки
    // Хедер и футер остаются как в редакторе, без изменений
    
    const editorBody = document.getElementById('editor-body');
    const editorPlaceholder = document.querySelector('.editor-placeholder-wrapper');
    const uploadContent = document.getElementById('upload-mode-content');
    const settingsContent = document.getElementById('settings-mode-content');
    
    // Скрываем тело редактора и placeholder
    if (editorBody) editorBody.style.display = 'none';
    if (editorPlaceholder) editorPlaceholder.style.display = 'none';
    if (settingsContent) settingsContent.style.display = 'none';
    
    // Показываем хедер и футер (если они были скрыты), но НЕ изменяем их содержимое
    const editorHeader = document.getElementById('editor-header');
    const editorFooter = document.getElementById('editor-footer');
    
    // Показываем хедер, но НЕ изменяем его содержимое - оставляем как для редактирования файла
    if (editorHeader) {
        editorHeader.style.display = 'block';
        // НЕ изменяем заголовок - оставляем пустым или с текущим файлом
        // Информация о файле остается как есть
    }
    
    // Показываем футер и обновляем для режима загрузки
    if (editorFooter) {
        // Явно показываем футер, переопределяя inline стиль display: none
        editorFooter.style.setProperty('display', 'block', 'important');
        editorFooter.style.setProperty('visibility', 'visible', 'important');
        // Показываем все элементы футера
        const footerButtons = editorFooter.querySelector('.d-flex.gap-2');
        if (footerButtons) {
            footerButtons.style.display = 'flex';
        }
        // Обновляем статус на "FTP сервер: подключен"
        const statusText = editorFooter.querySelector('#editor-status');
        if (statusText) {
            statusText.style.display = 'block';
            statusText.style.visibility = 'visible';
            statusText.textContent = 'FTP сервер: подключен';
        }
        const statusIcon = editorFooter.querySelector('#editor-status-icon');
        if (statusIcon) {
            statusIcon.style.display = 'inline-block';
            statusIcon.style.visibility = 'visible';
            statusIcon.className = 'editor-status-dot text-success me-2';
        }
        // Скрываем кнопку "Скасувати"
        const cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        // Обновляем кнопку "Зберегти" на "Завантажити"
        const saveBtn = editorFooter.querySelector('button.btn-primary.btn-sm');
        if (saveBtn) {
            // Сохраняем оригинальную кнопку если еще не сохранена
            if (!saveBtn.getAttribute('data-original-onclick')) {
                saveBtn.setAttribute('data-original-onclick', saveBtn.getAttribute('onclick') || 'saveFile()');
                saveBtn.setAttribute('data-original-html', saveBtn.innerHTML);
            }
            // Изменяем на "Завантажити"
            saveBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Завантажити';
            saveBtn.setAttribute('onclick', 'startFilesUpload()');
            saveBtn.style.display = '';
        }
        // Скрываем прогресс бар в футере (пока что)
        const footerProgressBar = editorFooter.querySelector('#footer-upload-progress');
        if (footerProgressBar) {
            footerProgressBar.style.display = 'none';
        }
    }
    
    // Показываем режим загрузки (заменяет тело редактора - черное окно с кодом)
    if (uploadContent) {
        uploadContent.style.display = 'block';
        // Инициализируем dropzone при первом показе
        if (!uploadContent.dataset.initialized) {
            initUploadDropzone();
            loadFoldersForUpload();
            uploadContent.dataset.initialized = 'true';
        }
        // Устанавливаем целевую папку если указана
        if (targetFolder) {
            const select = document.getElementById('upload-target-folder');
            if (select) {
                select.value = targetFolder;
                // Обновляем заголовок с путем
                const fileTitle = editorHeader?.querySelector('.editor-file-title');
                if (fileTitle) {
                    const uploadPath = targetFolder || 'Коренева папка теми';
                    fileTitle.innerHTML = '<i class="fas fa-upload me-2"></i>Завантажити файли <span class="text-muted mx-2">|</span> <span class="text-muted">' + uploadPath + '</span>';
                }
            }
        }
        
        // Обновляем URL с параметром folder
        const theme = new URLSearchParams(window.location.search).get('theme') || '';
        const url = new URL(window.location.href);
        url.searchParams.set('theme', theme);
        if (targetFolder) {
            url.searchParams.set('folder', targetFolder);
        } else {
            url.searchParams.delete('folder');
        }
        // Удаляем параметры file и mode, так как мы в режиме загрузки
        url.searchParams.delete('file');
        url.searchParams.delete('mode');
        window.history.pushState({ path: url.href }, '', url.href);
        
        // Обновляем заголовок при изменении целевой папки
        const select = document.getElementById('upload-target-folder');
        if (select) {
            // Убираем старые обработчики чтобы избежать дублирования
            const newSelect = select.cloneNode(true);
            select.parentNode.replaceChild(newSelect, select);
            
            newSelect.addEventListener('change', function() {
                const fileTitle = editorHeader?.querySelector('.editor-file-title');
                if (fileTitle) {
                    const uploadPath = this.value || 'Коренева папка теми';
                    fileTitle.innerHTML = '<i class="fas fa-upload me-2"></i>Завантажити файли <span class="text-muted mx-2">|</span> <span class="text-muted">' + uploadPath + '</span>';
                }
                
                // Обновляем URL с выбранной папкой
                const theme = new URLSearchParams(window.location.search).get('theme') || '';
                const url = new URL(window.location.href);
                url.searchParams.set('theme', theme);
                if (this.value) {
                    url.searchParams.set('folder', this.value);
                } else {
                    url.searchParams.delete('folder');
                }
                url.searchParams.delete('file');
                url.searchParams.delete('mode');
                window.history.pushState({ path: url.href }, '', url.href);
            });
        }
    }
}

/**
 * Показать режим настроек (встраивается вместо редактора)
 */
function showEditorSettings() {
    // Скрываем тело редактора и показываем режим настроек
    const editorBody = document.getElementById('editor-body');
    const editorPlaceholder = document.querySelector('.editor-placeholder-wrapper');
    const uploadContent = document.getElementById('upload-mode-content');
    const settingsContent = document.getElementById('settings-mode-content');
    
    // Скрываем тело редактора и placeholder
    if (editorBody) editorBody.style.display = 'none';
    if (editorPlaceholder) editorPlaceholder.style.display = 'none';
    if (uploadContent) uploadContent.style.display = 'none';
    
    // Показываем хедер и футер (они должны быть видимы, как в редакторе)
    const editorHeader = document.getElementById('editor-header');
    const editorFooter = document.getElementById('editor-footer');
    
    // Обновляем URL - добавляем параметр mode=settings
    const theme = new URLSearchParams(window.location.search).get('theme') || '';
    const url = new URL(window.location.href);
    url.searchParams.set('theme', theme);
    url.searchParams.set('mode', 'settings');
    url.searchParams.delete('file');
    url.searchParams.delete('folder');
    window.history.pushState({ path: url.href }, '', url.href);
    
    // Показываем хедер и футер (они остаются как в редакторе, без изменений)
    // Хедер и футер остаются видимыми с их оригинальным содержимым (текущий файл, статус, кнопки)
    
    // Показываем хедер и изменяем заголовок на "Налаштування редактора"
    if (editorHeader) {
        editorHeader.style.display = 'block';
        // Изменяем заголовок на "Налаштування редактора"
        const fileTitle = editorHeader.querySelector('.editor-file-title');
        if (fileTitle) {
            fileTitle.innerHTML = '<i class="fas fa-cog me-2"></i>Налаштування редактора';
        }
        // Скрываем информацию о файле (PHP 481 B)
        const fileInfo = editorHeader.querySelector('.d-flex.justify-content-between > div:last-child');
        if (fileInfo) {
            fileInfo.style.display = 'none';
        }
    }
    
    // Показываем футер - он должен быть всегда виден
    if (editorFooter) {
        // Явно показываем футер, переопределяя inline стиль display: none
        editorFooter.style.setProperty('display', 'block', 'important');
        editorFooter.style.setProperty('visibility', 'visible', 'important');
        // Показываем все элементы футера, НЕ изменяем их содержимое
        const footerButtons = editorFooter.querySelector('.d-flex.gap-2');
        if (footerButtons) {
            footerButtons.style.display = 'flex';
        }
        // Обновляем статус для режима настроек
        const statusText = editorFooter.querySelector('#editor-status');
        if (statusText) {
            statusText.style.display = 'block';
            statusText.style.visibility = 'visible';
            statusText.textContent = 'Налаштування готові';
        }
        const statusIcon = editorFooter.querySelector('#editor-status-icon');
        if (statusIcon) {
            statusIcon.style.display = 'inline-block';
            statusIcon.style.visibility = 'visible';
        }
        // Скрываем кнопку "Скасувати"
        const cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        // Изменяем кнопку "Зберегти" для сохранения настроек
        const saveBtn = editorFooter.querySelector('button.btn-primary.btn-sm');
        if (saveBtn) {
            // Сохраняем оригинальные значения если они еще не сохранены
            if (!saveBtn.getAttribute('data-original-onclick')) {
                saveBtn.setAttribute('data-original-onclick', saveBtn.getAttribute('onclick') || 'saveFile()');
                saveBtn.setAttribute('data-original-html', saveBtn.innerHTML);
            }
            // Устанавливаем обработчик для сохранения настроек
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Зберегти';
            saveBtn.setAttribute('onclick', 'saveEditorSettingsFromFooter()');
            // Оставляем кнопку видимой
            saveBtn.style.display = '';
        }
    }
    
    // Показываем режим настроек (заменяет тело редактора)
    if (settingsContent) {
        settingsContent.style.display = 'block';
        // Загружаем настройки
        loadEditorSettingsInline();
    }
    
    // Убеждаемся, что футер виден после небольшой задержки
    setTimeout(function() {
        const footer = document.getElementById('editor-footer');
        if (footer) {
            footer.style.setProperty('display', 'block', 'important');
            footer.style.setProperty('visibility', 'visible', 'important');
        }
    }, 50);
}

/**
 * Инициализация drag & drop для загрузки файлов
 */
function initUploadDropzone() {
    const dropzone = document.getElementById('upload-dropzone');
    if (!dropzone) return;
    
    // Убираем старые обработчики
    const newDropzone = dropzone.cloneNode(true);
    dropzone.parentNode.replaceChild(newDropzone, dropzone);
    
    newDropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });
    
    newDropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });
    
    newDropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        if (files.length > 0) {
            handleFileSelection(files);
        }
    });
}

/**
 * Обработка выбранных файлов
 */
let selectedFiles = [];

function handleFileSelection(files) {
    const filesArray = Array.from(files);
    
    // Добавляем файлы в список (избегаем дубликатов)
    filesArray.forEach(file => {
        const existingIndex = selectedFiles.findIndex(f => f.name === file.name && f.size === file.size);
        if (existingIndex === -1) {
            selectedFiles.push(file);
        }
    });
    
    updateUploadFilesList();
}

/**
 * Обновление списка файлов для загрузки
 */
function updateUploadFilesList() {
    const filesList = document.getElementById('upload-files-list');
    const filesItems = document.getElementById('upload-files-items');
    const dropzone = document.getElementById('upload-dropzone');
    
    if (!filesList || !filesItems) return;
    
    if (selectedFiles.length === 0) {
        filesList.style.display = 'none';
        // Показываем dropzone когда нет файлов
        if (dropzone) {
            dropzone.style.display = 'flex';
        }
        return;
    }
    
    // Скрываем dropzone и показываем список файлов
    if (dropzone) {
        dropzone.style.display = 'none';
    }
    filesList.style.display = 'flex';
    filesList.style.flexDirection = 'column';
    filesList.style.height = '100%';
    
    filesItems.innerHTML = selectedFiles.map((file, index) => {
        const fileIcon = file.type.startsWith('image/') ? 'fa-image' : 
                        file.type.startsWith('text/') ? 'fa-file-code' :
                        file.type.includes('pdf') ? 'fa-file-pdf' :
                        'fa-file';
        return `
            <div class="upload-file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                <div class="d-flex align-items-center">
                    <i class="fas ${fileIcon} text-muted me-2"></i>
                    <div>
                        <div class="small fw-semibold">${escapeHtml(file.name)}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">${formatBytes(file.size)}</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFileFromUploadList(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }).join('');
}

/**
 * Удаление файла из списка загрузки
 */
function removeFileFromUploadList(index) {
    selectedFiles.splice(index, 1);
    updateUploadFilesList();
}

/**
 * Очистка списка загрузки
 */
function clearUploadList() {
    selectedFiles = [];
    updateUploadFilesList();
    const fileInput = document.getElementById('uploadFilesInput');
    if (fileInput) {
        fileInput.value = '';
    }
}

/**
 * Загрузка списка папок для выбора цели загрузки
 */
function loadFoldersForUpload() {
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea ? textarea.getAttribute('data-theme') : '';
    if (!theme) return;
    
    const select = document.getElementById('upload-target-folder');
    if (!select) return;
    
    // Очищаем существующие опции (кроме первой)
    while (select.children.length > 1) {
        select.removeChild(select.lastChild);
    }
    
    // Собираем все папки из дерева файлов
    const folders = document.querySelectorAll('.file-tree-folder[data-folder-path]');
    folders.forEach(folder => {
        const path = folder.getAttribute('data-folder-path');
        if (path) {
            const header = folder.querySelector('.file-tree-folder-header');
            const name = header ? header.textContent.trim().replace(/^.*?\s/, '') : path;
            const option = document.createElement('option');
            option.value = path;
            option.textContent = name || 'Корень';
            select.appendChild(option);
        }
    });
}

/**
 * Начало загрузки файлов
 */
function startFilesUpload() {
    if (selectedFiles.length === 0) {
        showNotification('Виберіть файли для завантаження', 'warning');
        return;
    }
    
    const textarea = document.getElementById('theme-file-editor');
    const theme = textarea ? textarea.getAttribute('data-theme') : '';
    if (!theme) {
        showNotification('Тему не вказано', 'danger');
        return;
    }
    
    const targetFolder = document.getElementById('upload-target-folder').value || '';
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');
    
    // Элементы футера для прогресса
    const editorFooter = document.getElementById('editor-footer');
    const footerProgressBar = document.getElementById('footer-upload-progress');
    const footerProgressBarInner = document.getElementById('footer-upload-progress-bar');
    const statusText = document.getElementById('editor-status');
    const statusIcon = document.getElementById('editor-status-icon');
    
    // Показываем прогресс в футере
    if (footerProgressBar && footerProgressBarInner && statusText) {
        footerProgressBar.style.display = 'block';
        footerProgressBarInner.style.width = '0%';
        statusText.textContent = 'Завантаження файлів...';
        if (statusIcon) {
            statusIcon.className = 'editor-status-dot text-warning me-2';
        }
    }
    
    if (progressContainer && progressBar && progressText) {
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.textContent = 'Готується до завантаження...';
    }
    
    let uploaded = 0;
    let errors = 0;
    
    // Загружаем файлы по одному
    selectedFiles.forEach((file, index) => {
        const formData = new FormData();
        formData.append('action', 'upload_file');
        formData.append('theme', theme);
        formData.append('folder', targetFolder);
        formData.append('file', file);
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
            uploaded++;
            const progress = (uploaded / selectedFiles.length) * 100;
            
            // Обновляем прогресс бар в футере
            if (footerProgressBarInner) {
                footerProgressBarInner.style.width = progress + '%';
            }
            if (statusText) {
                statusText.textContent = `Завантаження: ${uploaded} з ${selectedFiles.length} файлів`;
            }
            
            // Обновляем прогресс в основном контейнере
            if (progressBar) {
                progressBar.style.width = progress + '%';
            }
            if (progressText) {
                progressText.textContent = `Завантажено ${uploaded} з ${selectedFiles.length} файлів`;
            }
            
            if (!data.success) {
                errors++;
            }
            
            // Когда все файлы загружены
            if (uploaded === selectedFiles.length) {
                if (errors === 0) {
                    // Обновляем статус на "Успешно все файли загружены"
                    if (statusText) {
                        statusText.textContent = 'Успешно все файли загружены';
                    }
                    if (statusIcon) {
                        statusIcon.className = 'editor-status-dot text-success me-2';
                    }
                    // Скрываем прогресс бар через 3 секунды и возвращаем статус FTP
            setTimeout(() => {
                        if (footerProgressBar) {
                            footerProgressBar.style.display = 'none';
                        }
                        if (statusText) {
                            statusText.textContent = 'FTP сервер: подключен';
                        }
                    }, 3000);
                    
                    showNotification(`Успішно завантажено ${uploaded} файлів`, 'success');
                    clearUploadList();
                    refreshFileTree();
                    if (progressContainer) {
                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                        }, 2000);
                    }
        } else {
                    if (statusText) {
                        statusText.textContent = `Завантажено ${uploaded - errors} з ${selectedFiles.length} файлів. Помилок: ${errors}`;
                    }
                    if (statusIcon) {
                        statusIcon.className = 'editor-status-dot text-warning me-2';
                    }
                    showNotification(`Завантажено ${uploaded - errors} з ${selectedFiles.length} файлів. Помилок: ${errors}`, 'warning');
                }
            }
        })
        .catch(error => {
            uploaded++;
            errors++;
            const progress = (uploaded / selectedFiles.length) * 100;
            
            if (footerProgressBarInner) {
                footerProgressBarInner.style.width = progress + '%';
            }
            if (statusText) {
                statusText.textContent = `Помилка завантаження: ${uploaded - errors} з ${selectedFiles.length}`;
            }
            if (statusIcon) {
                statusIcon.className = 'editor-status-dot text-danger me-2';
            }
            
            if (progressBar) {
                progressBar.style.width = progress + '%';
            }
            
            if (uploaded === selectedFiles.length) {
                showNotification(`Помилка завантаження. Завантажено ${uploaded - errors} з ${selectedFiles.length}`, 'danger');
            }
        });
    });
}

/**
 * Загрузка настроек для inline режима
 */
function loadEditorSettingsInline() {
    // Если обработчики уже установлены, настройки уже загружены - не загружаем повторно
    if (settingsSetupDone) {
        // Просто применяем текущие значения к редактору
        if (codeEditor) {
            applyEditorSettingsToCodeMirror();
        }
        return;
    }
    
    // Настройки уже установлены в HTML при рендеринге страницы из PHP
    // Не нужно их перезагружать с сервера - это может перезаписать пользовательские изменения
    // Просто синхронизируем глобальные настройки с HTML и настраиваем обработчики
    
    const showEmptyCheckbox = document.getElementById('showEmptyFoldersInline');
    const syntaxCheckbox = document.getElementById('enableSyntaxHighlightingInline');
    
    // Синхронизируем глобальные настройки с HTML значениями
    if (showEmptyCheckbox) {
        editorSettings.showEmptyFolders = showEmptyCheckbox.checked;
    }
    if (syntaxCheckbox) {
        editorSettings.enableSyntaxHighlighting = syntaxCheckbox.checked;
    }
    
    // Применяем настройки к редактору, если он уже инициализирован
    if (codeEditor) {
        applyEditorSettingsToCodeMirror();
    }
    
    // Настраиваем автоматическое сохранение при изменении
    setupAutoSaveEditorSettingsInline();
}

/**
 * Автоматическое сохранение настроек в inline режиме
 */
function setupAutoSaveEditorSettingsInline() {
    // Если обработчики уже установлены, не добавляем их снова
    if (settingsSetupDone) {
        return;
    }
    
    // Получаем все элементы настроек
    const showEmptyCheckbox = document.getElementById('showEmptyFoldersInline');
    const syntaxCheckbox = document.getElementById('enableSyntaxHighlightingInline');
    const showLineNumbers = document.getElementById('showLineNumbersInline');
    const fontFamily = document.getElementById('editorFontFamilyInline');
    const fontSize = document.getElementById('editorFontSizeInline');
    const editorTheme = document.getElementById('editorThemeInline');
    const indentSize = document.getElementById('editorIndentSizeInline');
    const wordWrap = document.getElementById('wordWrapInline');
    const autoSave = document.getElementById('autoSaveInline');
    const autoSaveInterval = document.getElementById('autoSaveIntervalInline');
    
    // Debounce для сохранения настроек
    let saveSettingsTimeout = null;
    
    // Функция для применения настроек к редактору (без сохранения)
    const applySettingsToEditor = function() {
        if (codeEditor) {
            applyEditorSettingsToCodeMirror();
        }
    };
    
    // Функция для сохранения всех настроек
    const saveAllSettings = function() {
        // Сохраняем старое значение ДО применения изменений
        const oldShowEmptyFolders = editorSettings.showEmptyFolders;
        
        // Применяем настройки к редактору сразу (без сохранения)
        applySettingsToEditor();
        
        // Обновляем глобальные настройки сразу для проверки изменений
        if (showEmptyCheckbox) {
            editorSettings.showEmptyFolders = showEmptyCheckbox.checked;
        }
        
        // Обновляем дерево файлов сразу, если изменилась настройка показа пустых папок
        if (showEmptyCheckbox && oldShowEmptyFolders !== showEmptyCheckbox.checked) {
            refreshFileTree();
        }
        
        // Отменяем предыдущий таймер
        if (saveSettingsTimeout) {
            clearTimeout(saveSettingsTimeout);
        }
        
        // Запускаем сохранение с задержкой (debounce 300ms)
        saveSettingsTimeout = setTimeout(function() {
            // Проверяем флаг только перед отправкой запроса
            if (isSaving) {
                console.log('Сохранение уже выполняется, пропускаем');
                return;
            }
            
            const url = window.location.href.split('?')[0];
            const formData = {
                action: 'save_editor_settings',
                show_empty_folders: showEmptyCheckbox?.checked ? '1' : '0',
                enable_syntax_highlighting: syntaxCheckbox?.checked ? '1' : '0',
                show_line_numbers: showLineNumbers?.checked ? '1' : '0',
                font_family: fontFamily?.value || "'Consolas', monospace",
                font_size: fontSize?.value || '14',
                editor_theme: editorTheme?.value || 'monokai',
                indent_size: indentSize?.value || '4',
                word_wrap: wordWrap?.checked ? '1' : '0',
                auto_save: autoSave?.checked ? '1' : '0',
                auto_save_interval: autoSaveInterval?.value || '60'
            };
            
            isSaving = true;
            if (typeof AjaxHelper !== 'undefined') {
                AjaxHelper.post(url, formData)
                    .then(data => {
                        isSaving = false;
                        if (data.success) {
                            // Обновляем глобальные настройки (если еще не обновлены)
                            if (syntaxCheckbox) editorSettings.enableSyntaxHighlighting = syntaxCheckbox.checked;
                            
                            // Не показываем уведомление при каждом изменении, только при ошибках
                            // showNotification('Налаштування збережено', 'success');
                        } else {
                            isSaving = false; // Сбрасываем флаг при ошибке
                            // Восстанавливаем старое значение при ошибке
                            if (showEmptyCheckbox) {
                                editorSettings.showEmptyFolders = oldShowEmptyFolders;
                                showEmptyCheckbox.checked = oldShowEmptyFolders;
                                // Обновляем дерево обратно
                                refreshFileTree();
                            }
                            showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
                        }
                    })
                    .catch(error => {
                        isSaving = false;
                        console.error('Error:', error);
                        // Восстанавливаем старое значение при ошибке
                        if (showEmptyCheckbox) {
                            editorSettings.showEmptyFolders = oldShowEmptyFolders;
                            showEmptyCheckbox.checked = oldShowEmptyFolders;
                            // Обновляем дерево обратно
                            refreshFileTree();
                        }
                        showNotification('Помилка збереження налаштувань', 'danger');
                    });
            } else {
                // Если AjaxHelper недоступен, сбрасываем флаг
                isSaving = false;
            }
        }, 300);
    };
    
    // Обработчик для autoSave с дополнительной логикой
    const autoSaveHandler = function() {
        if (autoSaveInterval) {
            autoSaveInterval.disabled = !this.checked;
        }
        saveAllSettings();
    };
    
    // Добавляем обработчики только один раз (проверка в начале функции)
    if (showEmptyCheckbox) {
        showEmptyCheckbox.addEventListener('change', saveAllSettings);
    }
    if (syntaxCheckbox) {
        syntaxCheckbox.addEventListener('change', saveAllSettings);
    }
    if (showLineNumbers) {
        showLineNumbers.addEventListener('change', saveAllSettings);
    }
    if (fontFamily) {
        fontFamily.addEventListener('change', saveAllSettings);
    }
    if (fontSize) {
        fontSize.addEventListener('input', applySettingsToEditor);
        fontSize.addEventListener('change', saveAllSettings);
    }
    if (editorTheme) {
        editorTheme.addEventListener('change', saveAllSettings);
    }
    if (indentSize) {
        indentSize.addEventListener('input', applySettingsToEditor);
        indentSize.addEventListener('change', saveAllSettings);
    }
    if (wordWrap) {
        wordWrap.addEventListener('change', saveAllSettings);
    }
    if (autoSave) {
        autoSave.addEventListener('change', autoSaveHandler);
    }
    if (autoSaveInterval) {
        autoSaveInterval.addEventListener('input', applySettingsToEditor);
        autoSaveInterval.addEventListener('change', saveAllSettings);
    }
    
    // Помечаем, что обработчики установлены
    settingsSetupDone = true;
}

