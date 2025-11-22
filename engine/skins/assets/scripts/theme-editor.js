/**
 * JavaScript для редактора темы
 */

let codeEditor = null;
let originalContent = '';
let isModified = false;

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация CodeMirror
    const textarea = document.getElementById('theme-file-editor');
    if (textarea) {
        const extension = textarea.getAttribute('data-extension');
        const mode = getCodeMirrorMode(extension);
        
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
        
        originalContent = codeEditor.getValue();
        
        codeEditor.on('change', function() {
            isModified = codeEditor.getValue() !== originalContent;
            updateEditorStatus();
        });
    }
    
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
    modal.show();
}

/**
 * Отправка формы создания файла
 */
function submitCreateFile() {
    const form = document.getElementById('createFileForm');
    const formData = new FormData(form);
    formData.append('action', 'create_file');
    
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
    modal.show();
}

/**
 * Отправка формы создания папки
 */
function submitCreateDirectory() {
    const form = document.getElementById('createDirectoryForm');
    const formData = new FormData(form);
    formData.append('action', 'create_directory');
    
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
 * Инициализация древовидной структуры файлов
 */
function initFileTree() {
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

