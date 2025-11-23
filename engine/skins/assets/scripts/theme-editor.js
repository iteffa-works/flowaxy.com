/**
 * JavaScript для редактора теми
 * Оптимізована версія з утилітними функціями та українською локалізацією
 * 
 * @file theme-editor.js
 * @description Редактор тем з підтримкою підсвітки синтаксису, завантаження файлів та налаштувань редактора
 * @author iTeffa
 * @organization Flowaxy Studio - розробка програмного забезпечення
 * @copyright © 2025 Flowaxy. Всі права захищені.
 * @license Proprietary
 * @version 1.0.0 Alpha
 * @since 2025-11-23
 * @see https://flowaxy.com
 * 
 * @requires CodeMirror 5.65.2
 * @requires Bootstrap 5.x
 * @requires Font Awesome
 */

// Глобальні змінні
let codeEditor = null;
let originalContent = '';
let isModified = false;
let editorSettings = {
    enableSyntaxHighlighting: true,
    showEmptyFolders: true
};
let settingsSetupDone = false; // Прапорець, що обробники налаштувань вже встановлені

// ============================================================================
// УТИЛІТНІ ФУНКЦІЇ
// ============================================================================

/**
 * Універсальна функція для виконання AJAX запитів
 * @param {string} action - Дія для виконання
 * @param {Object} formData - Дані для відправки
 * @returns {Promise} Promise з відповіддю сервера
 */
async function makeAjaxRequest(action, formData = {}) {
    const url = window.location.href.split('?')[0];
    // Спочатку шукаємо в input, потім в meta тезі
    let csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    if (!csrfToken) {
        csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    const requestData = {
        action: action,
        ...formData,
        csrf_token: csrfToken
    };
    
    if (typeof AjaxHelper !== 'undefined') {
        return await AjaxHelper.post(url, requestData);
    } else {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(requestData)
        });
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        }
        const text = await response.text();
        throw new Error('Сервер повернув не-JSON відповідь: ' + text.substring(0, 100));
    }
}

/**
 * Пошук елемента файлу або папки в дереві файлів
 * @param {string} path - Шлях до файлу або папки
 * @param {boolean} isFolder - Чи це папка
 * @returns {HTMLElement|null} Знайдений елемент або null
 */
function findTreeElement(path, isFolder = false) {
    const fileTree = document.querySelector('.file-tree');
    if (!fileTree) return null;
    
    if (isFolder) {
        let element = fileTree.querySelector(`[data-folder-path="${escapeHtml(path)}"]`);
        if (!element) {
            const folders = fileTree.querySelectorAll('.file-tree-folder');
            for (let folder of folders) {
                if (folder.getAttribute('data-folder-path') === path) {
                    element = folder;
                    break;
                }
            }
        }
        return element;
    } else {
        let element = fileTree.querySelector(`[data-file-path="${escapeHtml(path)}"]`);
        if (!element) {
            const fileWrappers = fileTree.querySelectorAll('.file-tree-item-wrapper');
            for (let wrapper of fileWrappers) {
                if (wrapper.getAttribute('data-file-path') === path) {
                    element = wrapper;
                    break;
                }
            }
        }
        if (!element) {
            const fileLinks = fileTree.querySelectorAll('.file-tree-item[data-file]');
            for (let link of fileLinks) {
                if (link.getAttribute('data-file') === path) {
                    element = link.closest('.file-tree-item-wrapper');
                    break;
                }
            }
        }
        return element;
    }
}

/**
 * Отримання теми з DOM або URL
 * @returns {string} Slug теми
 */
function getThemeFromPage() {
    const textarea = document.getElementById('theme-file-editor');
    if (textarea) {
        const theme = textarea.getAttribute('data-theme');
        if (theme) return theme;
    }
    return new URLSearchParams(window.location.search).get('theme') || '';
}

/**
 * Показ елемента в дереві файлів
 * @param {string} path - Шлях до елемента
 * @param {boolean} isFolder - Чи це папка
 */
function showTreeElement(path, isFolder = false) {
    const element = findTreeElement(path, isFolder);
    if (element) {
        element.style.display = '';
    }
}

/**
 * Ініціалізація CodeMirror редактора
 */
function initCodeMirror() {
    // Перевіряємо наявність CodeMirror
    if (typeof CodeMirror === 'undefined') {
        console.error('CodeMirror не завантажено! Перевірте підключення скриптів.');
        // Повторна спроба через невелику затримку
        setTimeout(initCodeMirror, 100);
        return;
    }
    
    // Ініціалізація CodeMirror
    const textarea = document.getElementById('theme-file-editor');
    if (!textarea) {
        return;
    }
    
    // Перевіряємо, чи увімкнена підсвітка синтаксису
    const enableSyntaxHighlighting = textarea.getAttribute('data-syntax-highlighting') !== '0';
    // Оновлюємо глобальну настройку
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
        
        // Приховуємо textarea після успішної ініціалізації
        textarea.style.display = 'none';
        
        originalContent = codeEditor.getValue();
        isModified = false;
        
        // Ініціалізуємо статус при завантаженні
        updateEditorStatus();
        
        codeEditor.on('change', function() {
            isModified = codeEditor.getValue() !== originalContent;
            updateEditorStatus();
        });
        
        // Оновлюємо розмір редактора при зміні розміру вікна
        let resizeTimeout;
        window.addEventListener('resize', function() {
            if (codeEditor) {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    codeEditor.refresh();
                }, 100);
            }
        });
        
        // Застосовуємо налаштування після ініціалізації
        setTimeout(function() {
            applyEditorSettingsToCodeMirror();
        }, 100);
    } catch (error) {
        console.error('Помилка ініціалізації CodeMirror:', error);
        // Показуємо textarea якщо CodeMirror не ініціалізувався
        textarea.style.display = 'block';
    }
}

// Ініціалізація при завантаженні сторінки
document.addEventListener('DOMContentLoaded', function() {
    // Завантажуємо налаштування редактора
    loadEditorSettings();
    
    // Ініціалізація CodeMirror
    initCodeMirror();
    
    // Ініціалізація древовидної структури файлів
    initFileTree();
    
    // Перевіряємо, чи є відкритий файл при завантаженні сторінки
    const urlParams = new URLSearchParams(window.location.search);
    const file = urlParams.get('file');
    const folder = urlParams.get('folder');
    const mode = urlParams.get('mode');
    
    // Якщо файл відкритий, показуємо футер
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
    
    // Перевіряємо наявність параметрів в URL для автоматичного показу режимів
    if (folder) {
        // Невелика затримка щоб дерево файлів встигло ініціалізуватися
        setTimeout(function() {
            showUploadFiles(folder);
        }, 300);
    } else if (mode === 'settings') {
        // Невелика затримка щоб дерево файлів встигло ініціалізуватися
        setTimeout(function() {
            showEditorSettings();
        }, 300);
    }
});

/**
 * Завантаження налаштувань редактора
 */
function loadEditorSettings() {
    const textarea = document.getElementById('theme-file-editor');
    if (textarea) {
        editorSettings.enableSyntaxHighlighting = textarea.getAttribute('data-syntax-highlighting') !== '0';
    }
    
    // Також завантажуємо з налаштувань при відкритті модального вікна
    // налаштування будуть оновлені в openEditorSettings()
}

/**
 * Отримання режиму CodeMirror за розширенням файлу
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
 * Оновлення статусу редактора
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
        // Змінюємо точку на попередження (жовта)
        if (statusIcon) {
            statusIcon.className = 'editor-status-dot text-warning me-2';
        }
        // Показуємо кнопку "Скасувати"
        if (cancelBtn) {
            cancelBtn.style.display = '';
        }
    } else {
        statusEl.textContent = 'Готово до редагування';
        statusEl.className = 'text-muted small';
        // Змінюємо точку на успіх (зелена)
        if (statusIcon) {
            statusIcon.className = 'editor-status-dot text-success me-2';
        }
        // Приховуємо кнопку "Скасувати"
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
    }
}

/**
 * Збереження файлу
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
    
    makeAjaxRequest('save_file', { theme: theme, file: file, content: content })
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
            console.error('Помилка:', error);
            showNotification('Помилка збереження файлу', 'danger');
        });
}

/**
 * Скидання змін в редакторі
 */
function resetEditor() {
    if (!codeEditor) {
        return;
    }
    
    // Якщо змін немає, нічого не робимо
    if (!isModified) {
        return;
    }
    
    // Якщо є зміни, питаємо підтвердження
    showConfirmDialog(
        'Скасувати зміни',
        'Ви впевнені, що хочете скасувати всі зміни? Внесені зміни будуть втрачені.',
        function() {
            // Відновлюємо оригінальний вміст
            codeEditor.setValue(originalContent);
            // Оновлюємо прапорець змін
            isModified = false;
            // Оновлюємо статус редактора (приховає кнопку "Скасувати" та змінить іконку)
            updateEditorStatus();
            // Показуємо повідомлення
            showNotification('Зміни скасовано', 'info');
        }
    );
}

/**
 * Створення нового файлу
 */
function createNewFile() {
    createNewFileInFolder(null, '');
}

/**
 * Створення нового файлу в конкретній папці
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
    
    // Видаляємо існуючі інлайн-форми
    document.querySelectorAll('.file-tree-inline-form').forEach(form => form.remove());
    
    // Створюємо інлайн-форму
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
    
    // Додаємо обробник Enter
    const input = inlineForm.querySelector('.file-tree-inline-input');
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitInlineCreateFile(this.nextElementSibling, folderPath || '');
        } else if (e.key === 'Escape') {
            cancelInlineForm(this.nextElementSibling.nextElementSibling);
        }
    });
    
    // Вставляємо форму на початок папки
    targetFolder.insertBefore(inlineForm, targetFolder.firstChild);
    input.focus();
}

/**
 * Відправка інлайн-форми створення файлу
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
    
    // Блокуємо кнопку
    button.disabled = true;
    input.disabled = true;
    
    // Використовуємо утилітну функцію для AJAX запиту
    const theme = new URLSearchParams(window.location.search).get('theme') || '';
    makeAjaxRequest('create_file', {
        theme: theme,
        file: fullPath,
        content: ''
    })
        .then(data => {
            if (data.success) {
                showNotification('Файл успішно створено', 'success');
                form.remove();
                
                // Оновлюємо дерево файлів через AJAX
                refreshFileTree();
                
                // Відкриваємо створений файл в редакторі
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
            console.error('Помилка:', error);
            showNotification('Помилка створення файлу', 'danger');
            button.disabled = false;
            input.disabled = false;
            input.focus();
        });
}

/**
 * Створення нової папки
 */
function createNewDirectory() {
    createNewDirectoryInFolder(null, '');
}

/**
 * Створення нової папки в конкретній папці
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
 * Відправка інлайн-форми створення папки
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
    
    // Формуємо повний шлях до папки
    let fullPath = directoryName;
    if (folderPath) {
        fullPath = folderPath + '/' + directoryName;
    }
    
    // Блокуємо кнопку
    button.disabled = true;
    input.disabled = true;
    
    // Використовуємо утилітну функцію для AJAX запиту
    const theme = new URLSearchParams(window.location.search).get('theme') || '';
    makeAjaxRequest('create_directory', {
        theme: theme,
        directory: fullPath
    })
        .then(data => {
            if (data.success) {
                showNotification('Папку успішно створено', 'success');
                form.remove();
                // Оновлюємо дерево файлів через AJAX
                refreshFileTree();
            } else {
                showNotification(data.error || 'Помилка створення', 'danger');
                button.disabled = false;
                input.disabled = false;
                input.focus();
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            showNotification('Помилка створення папки', 'danger');
            button.disabled = false;
            input.disabled = false;
            input.focus();
        });
}

/**
 * Скасування інлайн-форми
 */
function cancelInlineForm(button) {
    const form = button.closest('.file-tree-inline-form');
    if (form) {
        form.remove();
    }
}

/**
 * Видалення папки
 * Функція повинна бути доступна глобально для використання в onclick
 */
function deleteCurrentFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (!folderPath || folderPath === '') {
        showNotification('Неможливо видалити кореневу папку теми', 'danger');
        return;
    }
    
    if (!confirm('Ви впевнені, що хочете видалити цю папку?\n\nВсі файли та підпапки всередині також будуть видалені.\n\nЦю дію неможливо скасувати!')) {
        return;
    }
    
    const theme = getThemeFromPage();
    
    if (!theme) {
        showNotification('Тему не вказано', 'danger');
        return;
    }
    
    // Використовуємо утилітну функцію для AJAX запиту
    makeAjaxRequest('delete_directory', {
        theme: theme,
        folder: folderPath
    })
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Папку успішно видалено', 'success');
                refreshFileTree();
            } else {
                showNotification(data.error || 'Помилка видалення папки', 'danger');
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            showNotification('Помилка видалення папки', 'danger');
        });
}

/**
 * Перейменування файлу або папки
 */
function renameFileOrFolder(event, path, isFolder = false) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Перевіряємо, чи не намагаємося перейменувати кореневу папку
    if (isFolder && (!path || path === '')) {
        showNotification('Неможливо перейменувати кореневу папку теми', 'danger');
        return;
    }
    
    // Отримуємо поточну назву
    const currentName = path.split('/').pop() || path;
    
    // Знаходимо елемент в дереві файлів для заміни на inline форму
    const fileTree = document.querySelector('.file-tree');
    if (!fileTree) {
        showNotification('Дерево файлів не знайдено', 'danger');
        return;
    }
    
    // Використовуємо утилітну функцію для пошуку елемента
    let targetElement = findTreeElement(path, isFolder);
    
    if (!targetElement) {
        // Пробуємо ще раз через невелику затримку (на випадок, якщо дерево ще оновлюється)
        setTimeout(() => {
            targetElement = findTreeElement(path, isFolder);
            if (!targetElement) {
                showNotification('Елемент не знайдено в дереві. Спробуйте оновити дерево файлів.', 'warning');
            } else {
                createInlineRenameForm(targetElement, path, currentName, isFolder);
            }
        }, 300);
        return;
    }
    
    // Створюємо inline форму редагування
    createInlineRenameForm(targetElement, path, currentName, isFolder);
}

/**
 * Створення inline форми для перейменування файлу/папки
 */
function createInlineRenameForm(targetElement, path, currentName, isFolder) {
    // Приховуємо оригінальний елемент
    targetElement.style.display = 'none';
    
    // Створюємо форму редагування
    const inlineForm = document.createElement('div');
    inlineForm.className = 'file-tree-inline-form';
    inlineForm.innerHTML = `
        <input type="text" class="file-tree-inline-input" value="${escapeHtml(currentName)}" />
        <button type="button" class="file-tree-inline-btn file-tree-inline-btn-success" onclick="submitInlineRename(this, '${escapeHtml(path)}', ${isFolder ? 'true' : 'false'})">
            <i class="fas fa-check"></i>
        </button>
        <button type="button" class="file-tree-inline-btn file-tree-inline-btn-cancel" onclick="cancelInlineRename(this, '${escapeHtml(path)}')">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Додаємо обробник Enter
    const input = inlineForm.querySelector('.file-tree-inline-input');
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitInlineRename(this.nextElementSibling, path, isFolder);
        } else if (e.key === 'Escape') {
            cancelInlineRename(this.nextElementSibling.nextElementSibling, path);
        }
    });
    
    // Вставляємо форму після оригінального елемента
    targetElement.parentNode.insertBefore(inlineForm, targetElement.nextSibling);
    input.focus();
    input.select(); // Виділяємо текст для зручного редагування
}

/**
 * Скасування перейменування
 */
function cancelInlineRename(button, path) {
    const form = button.closest('.file-tree-inline-form');
    if (!form) return;
    
    // Використовуємо утилітну функцію для пошуку та показу елемента
    // Пробуємо знайти як папку, потім як файл
    let targetElement = findTreeElement(path, true);
    if (!targetElement) {
        targetElement = findTreeElement(path, false);
    }
    if (targetElement) {
        targetElement.style.display = '';
    }
    
    form.remove();
}

/**
 * Відправка inline форми перейменування
 */
function submitInlineRename(button, oldPath, isFolder) {
    const form = button.closest('.file-tree-inline-form');
    const input = form.querySelector('.file-tree-inline-input');
    const newName = input.value.trim();
    
    if (!newName) {
        showNotification(isFolder ? 'Введіть назву папки' : 'Введіть назву файлу', 'warning');
        input.focus();
        return;
    }
    
    const currentName = oldPath.split('/').pop() || oldPath;
    if (newName === currentName) {
        cancelInlineRename(button, oldPath);
        return;
    }
    
    const theme = getThemeFromPage();
    
    if (!theme) {
        showNotification('Тему не вказано', 'danger');
        cancelInlineRename(button, oldPath);
        return;
    }
    
    const action = isFolder ? 'rename_directory' : 'rename_file';
    
    // Блокуємо кнопку та input
    button.disabled = true;
    input.disabled = true;
    
    // Використовуємо утилітну функцію для AJAX запиту
    makeAjaxRequest(action, {
        theme: theme,
        old_path: oldPath,
        new_name: newName
    })
        .then(data => {
            form.remove(); // Видаляємо форму в будь-якому випадку
            
            if (data.success) {
                showNotification(data.message || (isFolder ? 'Папку успішно перейменовано' : 'Файл успішно перейменовано'), 'success');
                
                // Оновлюємо дерево файлів - це покаже перейменований файл
                refreshFileTree();
                
                // Якщо перейменовано відкритий файл, оновлюємо його в редакторі
                if (!isFolder && data.new_path) {
                    const textarea = document.getElementById('theme-file-editor');
                    const currentFile = textarea ? textarea.getAttribute('data-file') : '';
                    if (currentFile === oldPath) {
                        setTimeout(() => {
                            loadFileInEditor(data.new_path);
                        }, 500);
                    }
                }
            } else {
                showNotification(data.error || (isFolder ? 'Помилка перейменування папки' : 'Помилка перейменування файлу'), 'danger');
                // Показуємо оригінальний елемент знову, використовуючи утилітну функцію
                showTreeElement(oldPath, isFolder);
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            form.remove();
            showNotification(isFolder ? 'Помилка перейменування папки' : 'Помилка перейменування файлу', 'danger');
            // Показуємо оригінальний елемент знову, використовуючи утилітну функцію
            showTreeElement(oldPath, isFolder);
        });
}

/**
 * Видалення поточного файлу
 */
function deleteCurrentFile(event, filePath) {
    event.preventDefault();
    event.stopPropagation();
    
    // Перевіряємо, чи не намагаємося видалити критичний файл
    const criticalFiles = ['index.php', 'theme.json'];
    const fileName = filePath.split('/').pop() || filePath;
    if (criticalFiles.includes(fileName)) {
        showNotification('Неможливо видалити критичний файл: ' + fileName, 'danger');
        return;
    }
    
    const theme = getThemeFromPage();
    
    showConfirmDialog(
        'Видалити файл',
        'Ви впевнені, що хочете видалити цей файл? Цю дію неможливо скасувати.',
        function() {
            // Використовуємо утилітну функцію для AJAX запиту
            makeAjaxRequest('delete_file', {
                theme: theme,
                file: filePath
            })
                .then(data => {
                    if (data.success) {
                        showNotification('Файл успішно видалено', 'success');
                        
                        // Закриваємо редактор, якщо видалено відкритий файл
                        const textarea = document.getElementById('theme-file-editor');
                        const currentFile = textarea ? textarea.getAttribute('data-file') : '';
                        if (currentFile === filePath) {
                            // Приховуємо редактор, показуємо placeholder
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
                            
                            // Оновлюємо URL без перезавантаження
                            const url = new URL(window.location.href);
                            url.searchParams.delete('file');
                            window.history.pushState({ path: url.href }, '', url.href);
                        }
                        
                        // Оновлюємо дерево файлів через AJAX
                        refreshFileTree();
                    } else {
                        showNotification(data.error || 'Помилка видалення', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Помилка:', error);
                    showNotification('Помилка видалення файлу', 'danger');
                });
        }
    );
}

/**
 * Показати повідомлення
 */
// showNotification тепер глобальна функція з notifications.php

/**
 * Кастомне модальне вікно підтвердження
 */
function showConfirmDialog(title, message, onConfirm, confirmText = 'Підтвердити', cancelText = 'Скасувати') {
    const modal = document.getElementById('confirmDialogModal');
    if (!modal) {
        // Якщо модальне вікно не знайдено, використовуємо стандартний confirm
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
        
        // Видаляємо старі обробники
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Додаємо новий обробник
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
    
    // Показуємо модальне вікно
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Автоматичне розкриття папок до вибраного файлу
 */
function expandPathToFile(filePath) {
    if (!filePath) return;
    
    // Отримуємо всі папки
    const folders = document.querySelectorAll('.file-tree-folder');
    
    folders.forEach(folder => {
        const folderPath = folder.getAttribute('data-folder-path');
        if (!folderPath) return;
        
        // Нормалізуємо шляхи для порівняння
        const folderPathNormalized = folderPath.endsWith('/') ? folderPath : folderPath + '/';
        const filePathNormalized = filePath;
        
        // Перевіряємо, чи починається шлях до файлу зі шляху папки
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
 * Ініціалізація древовидної структури файлів
 */
function initFileTree() {
    // Автоматично розкриваємо шлях до вибраного файлу
    const activeFile = document.querySelector('.file-tree-item-wrapper.active');
    if (activeFile) {
        const filePath = activeFile.getAttribute('data-file-path') || 
                        activeFile.querySelector('.file-tree-item')?.getAttribute('data-file');
        if (filePath) {
            expandPathToFile(filePath);
        }
    }
    
    // Обробка кліків по файлам та папкам - використовуємо делегування подій
    // Це дозволяє працювати з динамічно оновлюваними елементами
    const fileTree = document.querySelector('.file-tree');
    if (fileTree && !fileTree.dataset.delegateHandler) {
        fileTree.addEventListener('click', function(e) {
            // Проверяем, не кликнули ли на кнопку контекстного меню (включая кнопку удаления)
            const contextMenuBtn = e.target.closest('.context-menu-btn');
            if (contextMenuBtn) {
                // Если есть onclick атрибут, позволяем ему обработать событие
                const onclickAttr = contextMenuBtn.getAttribute('onclick');
                if (onclickAttr) {
                    // Не обрабатываем событие дальше, позволяем onclick выполниться
                    return;
                }
            }
            
            // Сначала проверяем клик по папке
            const folderHeader = e.target.closest('.file-tree-folder-header');
            if (folderHeader) {
                // Не раскрываем папку, если кликнули на кнопку контекстного меню
                if (e.target.closest('.file-tree-context-menu')) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                const folder = folderHeader.closest('.file-tree-folder');
                if (!folder) return;
                
                const content = folder.querySelector('.file-tree-folder-content');
                if (!content) return; // Если папка не имеет содержимого, ничего не делаем
                
                const isExpanded = folderHeader.classList.contains('expanded');
                
                if (isExpanded) {
                    folderHeader.classList.remove('expanded');
                    content.classList.remove('expanded');
                    const icon = folderHeader.querySelector('.folder-icon');
                    if (icon) {
                        icon.className = 'fas fa-chevron-right folder-icon';
                    }
                } else {
                    folderHeader.classList.add('expanded');
                    content.classList.add('expanded');
                    const icon = folderHeader.querySelector('.folder-icon');
                    if (icon) {
                        icon.className = 'fas fa-chevron-down folder-icon';
                    }
                }
                return; // Не обрабатываем дальше, если клик был по папке
            }
            
            // Затем проверяем клик по файлу
            const fileLink = e.target.closest('.file-tree-item');
            if (fileLink) {
                e.preventDefault();
                e.stopPropagation();
                const filePath = fileLink.getAttribute('data-file');
                if (filePath) {
                    loadFile(e, filePath);
                }
                return;
            }
        });
        fileTree.dataset.delegateHandler = 'true';
    }
}

// Попередження при виході зі сторінки з незбереженими змінами
window.addEventListener('beforeunload', function(e) {
    if (isModified) {
        // Сучасні браузери ігнорують кастомний текст і показують стандартне повідомлення
        // Просто повертаємо рядок - браузер покаже стандартне діалогове вікно
        e.preventDefault();
        return 'У вас є незбережені зміни. Ви впевнені, що хочете покинути сторінку?';
    }
});

/**
 * Завантаження файлу в папку (відкриває вбудований режим завантаження)
 */
function uploadFileToFolder(event, folderPath) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Відкриваємо вбудований режим завантаження замість модального вікна
    showUploadFiles(folderPath || '');
}

/**
 * Скачування файлу
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
 * Скачування папки (ZIP)
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
 * Завантаження файлу в редактор через AJAX
 */
function loadFileInEditor(filePath) {
    // Перевіряємо, чи є файл log.txt (системний файл)
    const fileName = filePath.split('/').pop() || filePath;
    if (fileName.toLowerCase() === 'log.txt' || fileName.toLowerCase().endsWith('.log.txt')) {
        showNotification('⚠️ Увага: Це системний файл логів. Редагування може призвести до втрати даних про помилки системи.', 'warning');
    }
    
    const theme = getThemeFromPage();
    
    if (!theme) {
        showNotification('Тему не вказано', 'danger');
        return Promise.reject(new Error('Тему не вказано'));
    }
    
    // Використовуємо утилітну функцію для завантаження файлу
    return makeAjaxRequest('get_file', { theme: theme, file: filePath })
        .then(data => {
            if (data.success && data.content !== undefined) {
                // Оновлюємо активний файл в дереві
                document.querySelectorAll('.file-tree-item-wrapper').forEach(item => {
                    item.classList.remove('active');
                });
                
                let fileWrapper = document.querySelector(`[data-file-path="${filePath}"]`);
                if (fileWrapper) {
                    fileWrapper.classList.add('active');
                }
                
                // Приховуємо вбудовані режими (завантаження файлів, налаштування)
                hideEmbeddedModes();
                
                // Показуємо редактор, приховуємо placeholder
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
                    // Відновлюємо кнопки в футері
                    const footerButtons = editorFooter.querySelector('.d-flex.gap-2');
                    if (footerButtons) footerButtons.style.display = 'flex';
                    const statusText = editorFooter.querySelector('#editor-status');
                    if (statusText) statusText.style.display = 'block';
                    const statusIcon = editorFooter.querySelector('#editor-status-icon');
                    if (statusIcon) statusIcon.style.display = 'inline-block';
                    // Відновлюємо заголовок файлу в хедері
                    const fileTitle = editorHeader?.querySelector('.editor-file-title');
                    if (fileTitle && filePath) {
                        const extension = filePath.split('.').pop() || '';
                        fileTitle.innerHTML = '<i class="fas fa-edit me-2"></i>' + escapeHtml(filePath);
                    }
                    // Відновлюємо інформацію про файл в хедері
                    const fileInfo = editorHeader?.querySelector('.d-flex.justify-content-between > div:last-child');
                    if (fileInfo) fileInfo.style.display = 'block';
                    // Відновлюємо оригінальну кнопку "Зберегти"
                    const saveBtn = editorFooter.querySelector('button.btn-primary.btn-sm');
                    if (saveBtn) {
                        // Відновлюємо оригінальні значення якщо були змінені
                        if (saveBtn.getAttribute('data-original-onclick')) {
                            saveBtn.setAttribute('onclick', saveBtn.getAttribute('data-original-onclick'));
                            saveBtn.innerHTML = saveBtn.getAttribute('data-original-html');
                            saveBtn.removeAttribute('data-original-onclick');
                            saveBtn.removeAttribute('data-original-html');
                        } else if (saveBtn.getAttribute('onclick') === 'startFilesUpload()') {
                            // Якщо немає збережених значень, відновлюємо вручну
                            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Зберегти';
                            saveBtn.setAttribute('onclick', 'saveFile()');
                        }
                        if (saveBtn.style.display === 'none') {
                            saveBtn.style.display = '';
                        }
                    }
                }
                
                // Переконуємося, що textarea існує та оновлений
                let textareaEl = document.getElementById('theme-file-editor');
                if (!textareaEl) {
                    // Створюємо textarea якщо його немає
                    if (editorBody) {
                        textareaEl = document.createElement('textarea');
                        textareaEl.id = 'theme-file-editor';
                        editorBody.appendChild(textareaEl);
                    }
                }
                
                if (textareaEl) {
                    // Оновлюємо атрибути textarea
                    const extension = filePath.split('.').pop() || '';
                    textareaEl.setAttribute('data-theme', theme);
                    textareaEl.setAttribute('data-file', filePath);
                    textareaEl.setAttribute('data-extension', extension);
                    textareaEl.setAttribute('data-syntax-highlighting', editorSettings.enableSyntaxHighlighting ? '1' : '0');
                    textareaEl.value = data.content;
                }
                
                // Завантажуємо вміст в редактор
                if (codeEditor) {
                    // Якщо редактор вже ініціалізовано, оновлюємо його
                    codeEditor.setValue(data.content);
                    originalContent = data.content;
                    isModified = false;
                    
                    // Переконуємося, що статус та кнопки оновлені
                    updateEditorStatus();
                    
                    // Застосовуємо всі налаштування до редактора
                    applyEditorSettingsToCodeMirror();
                } else {
                    // Якщо редактор ще не ініціалізовано, ініціалізуємо його
                    if (typeof CodeMirror !== 'undefined' && textareaEl) {
                        initCodeMirror();
                    }
                }
                
                // Оновлюємо заголовок файлу
                const fileTitle = document.querySelector('.editor-file-title');
                if (fileTitle) {
                    fileTitle.innerHTML = '';
                    const newIcon = document.createElement('i');
                    newIcon.className = 'fas fa-edit me-2';
                    fileTitle.appendChild(newIcon);
                    fileTitle.appendChild(document.createTextNode(filePath));
                }
                
                // Оновлюємо розширення та розмір
                const extensionEl = document.getElementById('editor-extension');
                if (extensionEl) {
                    extensionEl.textContent = filePath.split('.').pop()?.toUpperCase() || '';
                }
                
                // Оновлюємо розмір файлу якщо доступний
                if (data.size !== undefined) {
                    const sizeEl = document.getElementById('editor-size');
                    if (sizeEl) {
                        sizeEl.textContent = formatBytes(data.size);
                    }
                }
                
                // Прокручуємо до активного файлу
                if (fileWrapper) {
                    fileWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                
                // Розкриваємо шлях до файлу
                expandPathToFile(filePath);
                
                // Оновлюємо URL без перезавантаження
                const url = new URL(window.location.href);
                url.searchParams.set('file', filePath);
                // Видаляємо параметри folder та mode, оскільки ми відкриваємо файл
                url.searchParams.delete('folder');
                url.searchParams.delete('mode');
                window.history.pushState({ path: url.href }, '', url.href);
            } else {
                showNotification(data.error || 'Помилка завантаження файлу', 'danger');
                throw new Error(data.error || 'Помилка завантаження файлу');
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
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
    
    // Отримуємо поточне значення налаштування показу пустих папок з чекбокса
    const showEmptyCheckbox = document.getElementById('showEmptyFoldersInline') || document.getElementById('showEmptyFolders');
    const showEmptyFolders = showEmptyCheckbox ? (showEmptyCheckbox.checked ? '1' : '0') : (editorSettings.showEmptyFolders ? '1' : '0');
    
    // Використовуємо утилітну функцію для AJAX запиту
    makeAjaxRequest('get_file_tree', { theme: theme, show_empty_folders: showEmptyFolders })
        .then(data => {
            if (data.success && data.tree) {
                // Оновлюємо дерево файлів (передаємо об'єкт теми)
                updateFileTree(data.tree, data.theme || theme);
            } else {
                showNotification(data.error || 'Помилка оновлення дерева', 'danger');
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            showNotification('Помилка оновлення дерева файлів', 'danger');
        });
}

/**
 * Оновлення дерева файлів в DOM
 */
function updateFileTree(treeArray, theme) {
    const treeContainer = document.querySelector('.file-tree');
    if (!treeContainer) {
        return;
    }
    
    // Рендеримо нове дерево
    const treeHtml = renderFileTreeFromArray(treeArray, theme);
    
    // Зберігаємо стан розкриття папок
    const expandedFolders = [];
    document.querySelectorAll('.file-tree-folder-header.expanded').forEach(header => {
        const path = header.getAttribute('data-folder-path');
        if (path) {
            expandedFolders.push(path);
        }
    });
    
    // Замінюємо вміст
    const rootFolder = treeContainer.querySelector('.file-tree-root');
    if (rootFolder) {
        const rootContent = rootFolder.querySelector('.file-tree-folder-content');
        if (rootContent) {
            rootContent.innerHTML = treeHtml;
            
            // Відновлюємо розкриття папок
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
            // Обробники вже встановлені через делегування подій, не потрібно викликати initFileTree() знову
        }
    }
}

/**
 * Рендеринг дерева файлів з масиву
 */
function renderFileTreeFromArray(treeArray, theme, level = 1) {
    let html = '';
    
    // Отримуємо slug теми
    const themeSlug = theme && typeof theme === 'object' ? theme.slug : theme || '';
    
    treeArray.forEach(item => {
        if (item.type === 'folder') {
            // Перевіряємо, чи є папка кореневою (порожній шлях)
            const isRootFolder = !item.path || item.path === '';
            
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
                        </button>${!isRootFolder ? `
                        <button type="button" class="context-menu-btn" onclick="renameFileOrFolder(event, '${escapeHtml(item.path)}', true)" title="Перейменувати папку">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="context-menu-btn context-menu-btn-danger" onclick="deleteCurrentFolder(event, '${escapeHtml(item.path)}')" title="Видалити папку">
                            <i class="fas fa-trash"></i>
                        </button>` : ''}
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
                    <button type="button" class="context-menu-btn" onclick="renameFileOrFolder(event, '${escapeHtml(item.path)}', false)" title="Перейменувати файл">
                        <i class="fas fa-edit"></i>
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
 * Екранування HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Форматування розміру в байтах
 */
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Відкриття налаштувань редактора (перемикання на вбудований режим налаштувань)
 */
function openEditorSettings() {
    showEditorSettings();
}

/**
 * Налаштування автоматичного збереження налаштувань редактора
 */
function setupAutoSaveEditorSettings() {
    const showEmptyFolders = document.getElementById('showEmptyFolders');
    const enableSyntaxHighlighting = document.getElementById('enableSyntaxHighlighting');
    
    if (!showEmptyFolders || !enableSyntaxHighlighting) {
        return;
    }
    
    // Перевіряємо, чи не налаштовані вже обробники
    if (showEmptyFolders.dataset.listener === 'true' && enableSyntaxHighlighting.dataset.listener === 'true') {
        return; // Обробники вже встановлені
    }
    
    // Видаляємо старі обробники, якщо вони є
    showEmptyFolders.removeEventListener('change', autoSaveEditorSettings);
    enableSyntaxHighlighting.removeEventListener('change', autoSaveEditorSettings);
    
    // Додаємо обробники зміни
    showEmptyFolders.addEventListener('change', autoSaveEditorSettings);
    showEmptyFolders.dataset.listener = 'true';
    
    enableSyntaxHighlighting.addEventListener('change', autoSaveEditorSettings);
    enableSyntaxHighlighting.dataset.listener = 'true';
}

/**
 * Автоматичне збереження налаштувань редактора
 */
let autoSaveTimeout = null; // Глобальна змінна для debounce таймера
let isSaving = false; // Глобальна змінна для запобігання множинних збережень

function autoSaveEditorSettings() {
    // Скасовуємо попередній запит, якщо він ще не виконано (debounce)
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    
    // Якщо вже виконується збереження, не запускаємо новий запит
    if (isSaving) {
        return;
    }
    
    // Запускаємо збереження з затримкою (debounce 300ms)
    autoSaveTimeout = setTimeout(() => {
        // Перевіряємо inline версію налаштувань, якщо є, інакше модальну
        const highlightingEl = document.getElementById('enableSyntaxHighlightingInline') || document.getElementById('enableSyntaxHighlighting');
        const showEmptyEl = document.getElementById('showEmptyFoldersInline') || document.getElementById('showEmptyFolders');
        
        if (!highlightingEl || !showEmptyEl) {
            return;
        }
        
        const newHighlighting = highlightingEl.checked;
        const newShowEmptyFolders = showEmptyEl.checked;
        
        // Зберігаємо старі значення для перевірки змін
        const oldHighlighting = editorSettings.enableSyntaxHighlighting;
        const oldShowEmptyFolders = editorSettings.showEmptyFolders;
        
        // Отримуємо URL без параметрів
        const url = window.location.href.split('?')[0];
        
        // Встановлюємо прапорець, що йде збереження
        isSaving = true;
        
        // Збираємо всі налаштування
        const formData = {
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
        
        // Використовуємо утилітну функцію для AJAX запиту
        makeAjaxRequest('save_editor_settings', formData)
            .then(data => {
                isSaving = false;
                
                if (data.success) {
                    // Оновлюємо глобальні налаштування
                    editorSettings.showEmptyFolders = newShowEmptyFolders;
                    editorSettings.enableSyntaxHighlighting = newHighlighting;
                    
                    // Застосовуємо всі налаштування до редактора
                    applyEditorSettingsToCodeMirror();
                    
                    // Оновлюємо дерево файлів, якщо змінилася налаштування показу пустих папок
                    if (oldShowEmptyFolders !== newShowEmptyFolders) {
                        refreshFileTree();
                    }
                    
                    showNotification('Налаштування збережено', 'success');
                } else {
                    // Відновлюємо значення чекбоксів при помилці
                    const highlightingEl = document.getElementById('enableSyntaxHighlightingInline') || document.getElementById('enableSyntaxHighlighting');
                    const showEmptyEl = document.getElementById('showEmptyFoldersInline') || document.getElementById('showEmptyFolders');
                    if (highlightingEl) highlightingEl.checked = oldHighlighting;
                    if (showEmptyEl) showEmptyEl.checked = oldShowEmptyFolders;
                    showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
                }
            })
            .catch(error => {
                isSaving = false;
                console.error('Помилка:', error);
                // Відновлюємо значення чекбоксів при помилці
                const highlightingEl = document.getElementById('enableSyntaxHighlightingInline') || document.getElementById('enableSyntaxHighlighting');
                const showEmptyEl = document.getElementById('showEmptyFoldersInline') || document.getElementById('showEmptyFolders');
                if (highlightingEl) highlightingEl.checked = oldHighlighting;
                if (showEmptyEl) showEmptyEl.checked = oldShowEmptyFolders;
                showNotification('Помилка збереження налаштувань', 'danger');
            });
    }, 300); // debounce 300ms
}

/**
 * Динамічне оновлення підсвітки синтаксису в CodeMirror
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
        // Увімкнюємо підсвітку
        const mode = getCodeMirrorMode(extension);
        codeEditor.setOption('mode', mode);
        codeEditor.setOption('theme', 'monokai');
        codeEditor.setOption('matchBrackets', true);
        codeEditor.setOption('autoCloseBrackets', true);
        codeEditor.setOption('foldGutter', true);
        codeEditor.setOption('gutters', ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']);
    } else {
        // Вимкнюємо підсвітку
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
 * Застосування всіх налаштувань редактора до CodeMirror
 */
function applyEditorSettingsToCodeMirror() {
    if (!codeEditor) {
        return;
    }
    
    // Отримуємо поточні налаштування з форми
    const showLineNumbers = document.getElementById('showLineNumbersInline') || document.getElementById('showLineNumbers');
    const fontFamily = document.getElementById('editorFontFamilyInline') || document.getElementById('editorFontFamily');
    const fontSize = document.getElementById('editorFontSizeInline') || document.getElementById('editorFontSize');
    const editorTheme = document.getElementById('editorThemeInline') || document.getElementById('editorTheme');
    const indentSize = document.getElementById('editorIndentSizeInline') || document.getElementById('editorIndentSize');
    const wordWrap = document.getElementById('wordWrapInline') || document.getElementById('wordWrap');
    const syntaxHighlighting = document.getElementById('enableSyntaxHighlightingInline') || document.getElementById('enableSyntaxHighlighting');
    
    // Застосовуємо налаштування
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
    
    // Оновлюємо підсвітку синтаксису
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
 * Збереження налаштувань редактора
 */
function saveEditorSettings() {
    const form = document.getElementById('editorSettingsForm');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.append('show_empty_folders', document.getElementById('showEmptyFolders')?.checked ? '1' : '0');
    formData.append('enable_syntax_highlighting', document.getElementById('enableSyntaxHighlighting')?.checked ? '1' : '0');
    
    // Використовуємо утилітну функцію для AJAX запиту
    const url = window.location.href.split('?')[0];
    makeAjaxRequest('save_editor_settings', {
        show_empty_folders: formData.get('show_empty_folders'),
        enable_syntax_highlighting: formData.get('enable_syntax_highlighting')
    })
        .then(data => {
            if (data.success) {
                showNotification('Налаштування успішно збережено', 'success');
                const modal = document.getElementById('editorSettingsModal');
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                }
                // Налаштування вже застосовані через autoSaveEditorSettings, нічого не робимо
            } else {
                showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
            }
        })
        .catch(error => {
            console.error('Помилка:', error);
            showNotification('Помилка збереження налаштувань', 'danger');
        });
}

/**
 * Збереження налаштувань редактора з футера (для режиму налаштувань)
 */
function saveEditorSettingsFromFooter() {
    // Отримуємо всі елементи налаштувань
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
    
    // Зберігаємо старе значення для перевірки змін
    const oldShowEmptyFolders = editorSettings.showEmptyFolders;
    
    // Формуємо дані для відправки
    const formData = {
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
    // Використовуємо утилітну функцію для AJAX запиту
    makeAjaxRequest('save_editor_settings', formData)
        .then(data => {
            isSaving = false;
            if (data.success) {
                // Оновлюємо глобальні налаштування
                if (showEmptyCheckbox) editorSettings.showEmptyFolders = showEmptyCheckbox.checked;
                if (syntaxCheckbox) editorSettings.enableSyntaxHighlighting = syntaxCheckbox.checked;
                
                // Застосовуємо налаштування до редактора
                applyEditorSettingsToCodeMirror();
                
                // Оновлюємо дерево файлів, якщо змінилася налаштування показу пустих папок
                if (showEmptyCheckbox && oldShowEmptyFolders !== showEmptyCheckbox.checked) {
                    refreshFileTree();
                }
                
                // Оновлюємо статус в футері
                const statusText = document.getElementById('editor-status');
                if (statusText) {
                    statusText.textContent = 'Налаштування збережено';
                    statusText.className = 'text-success small';
                    // Через 2 секунди повертаємо звичайний статус
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
            console.error('Помилка:', error);
            showNotification('Помилка збереження налаштувань', 'danger');
        });
}

/**
 * Приховати вбудовані режими (завантаження файлів, налаштування) та показати редактор
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
 * Показати режим завантаження файлів (вбудовується замість редактора)
 * Викликається з контекстного меню папок
 */
function showUploadFiles(targetFolder = '') {
    // Замінюємо ТІЛЬКИ тіло редактора (чорне вікно з кодом) на режим завантаження
    // Хедер та футер залишаються як в редакторі, без змін
    
    const editorBody = document.getElementById('editor-body');
    const editorPlaceholder = document.querySelector('.editor-placeholder-wrapper');
    const uploadContent = document.getElementById('upload-mode-content');
    const settingsContent = document.getElementById('settings-mode-content');
    
    // Приховуємо тіло редактора та placeholder
    if (editorBody) editorBody.style.display = 'none';
    if (editorPlaceholder) editorPlaceholder.style.display = 'none';
    if (settingsContent) settingsContent.style.display = 'none';
    
    // Показуємо хедер та футер (якщо вони були приховані), але НЕ змінюємо їх вміст
    const editorHeader = document.getElementById('editor-header');
    const editorFooter = document.getElementById('editor-footer');
    
    // Показуємо хедер, але НЕ змінюємо його вміст - залишаємо як для редагування файлу
    if (editorHeader) {
        editorHeader.style.display = 'block';
        // НЕ змінюємо заголовок - залишаємо порожнім або з поточним файлом
        // Інформація про файл залишається як є
    }
    
    // Показуємо футер та оновлюємо для режиму завантаження
    if (editorFooter) {
        // Явно показуємо футер, перевизначаючи inline стиль display: none
        editorFooter.style.setProperty('display', 'block', 'important');
        editorFooter.style.setProperty('visibility', 'visible', 'important');
        // Показуємо всі елементи футера
        const footerButtons = editorFooter.querySelector('.d-flex.gap-2');
        if (footerButtons) {
            footerButtons.style.display = 'flex';
        }
        // Оновлюємо статус на "FTP сервер: підключено"
        const statusText = editorFooter.querySelector('#editor-status');
        if (statusText) {
            statusText.style.display = 'block';
            statusText.style.visibility = 'visible';
            statusText.textContent = 'FTP сервер: підключено';
        }
        const statusIcon = editorFooter.querySelector('#editor-status-icon');
        if (statusIcon) {
            statusIcon.style.display = 'inline-block';
            statusIcon.style.visibility = 'visible';
            statusIcon.className = 'editor-status-dot text-success me-2';
        }
        // В режимі завантаження приховуємо кнопки редагування
        const cancelBtn = document.getElementById('cancel-btn');
        const editorSaveBtn = document.getElementById('editor-save-btn');
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        if (editorSaveBtn) {
            editorSaveBtn.style.display = 'none';
        }
        
        // Кнопки завантаження будуть показані автоматично при додаванні файлів в updateUploadFilesList()
        const uploadClearBtn = document.getElementById('upload-clear-btn');
        const uploadSubmitBtn = document.getElementById('upload-submit-btn');
        if (uploadClearBtn) {
            uploadClearBtn.style.display = 'none'; // Приховуємо поки немає файлів
        }
        if (uploadSubmitBtn) {
            uploadSubmitBtn.style.display = 'none'; // Приховуємо поки немає файлів
        }
        // Приховуємо прогрес бар в футері (поки що)
        const footerProgressBar = editorFooter.querySelector('#footer-upload-progress');
        if (footerProgressBar) {
            footerProgressBar.style.display = 'none';
        }
    }
    
    // Показуємо режим завантаження (замінює тіло редактора - чорне вікно з кодом)
    if (uploadContent) {
        uploadContent.style.display = 'block';
        // Ініціалізуємо dropzone при першому показі
        if (!uploadContent.dataset.initialized) {
            initUploadDropzone();
            loadFoldersForUpload();
            uploadContent.dataset.initialized = 'true';
        }
        // Встановлюємо цільову папку якщо вказана
        const select = document.getElementById('upload-target-folder');
        
        if (select) {
            select.value = targetFolder || '';
            
            // Оновлюємо заголовок зі шляхом
            const fileTitle = editorHeader?.querySelector('.editor-file-title');
            if (fileTitle) {
                const uploadPath = targetFolder || 'Коренева папка теми';
                fileTitle.innerHTML = '<i class="fas fa-upload me-2"></i>Завантажити файли <span class="text-muted mx-2">|</span> <span class="text-muted">' + uploadPath + '</span>';
            }
        }
        
        // Оновлюємо URL з параметром folder
        const theme = getThemeFromPage();
        const url = new URL(window.location.href);
        url.searchParams.set('theme', theme);
        if (targetFolder) {
            url.searchParams.set('folder', targetFolder);
        } else {
            url.searchParams.delete('folder');
        }
        // Видаляємо параметри file та mode, оскільки ми в режимі завантаження
        url.searchParams.delete('file');
        url.searchParams.delete('mode');
        window.history.pushState({ path: url.href }, '', url.href);
        
        // Видаляємо обробник зміни цільової папки, оскільки папка фіксована
        // (вона визначається при кліку на іконку завантаження в папці)
    }
}

/**
 * Показати режим налаштувань (вбудовується замість редактора)
 */
function showEditorSettings() {
    // Приховуємо тіло редактора та показуємо режим налаштувань
    const editorBody = document.getElementById('editor-body');
    const editorPlaceholder = document.querySelector('.editor-placeholder-wrapper');
    const uploadContent = document.getElementById('upload-mode-content');
    const settingsContent = document.getElementById('settings-mode-content');
    
    // Приховуємо тіло редактора та placeholder
    if (editorBody) editorBody.style.display = 'none';
    if (editorPlaceholder) editorPlaceholder.style.display = 'none';
    if (uploadContent) uploadContent.style.display = 'none';
    
    // Показуємо хедер та футер (вони повинні бути видимі, як в редакторі)
    const editorHeader = document.getElementById('editor-header');
    const editorFooter = document.getElementById('editor-footer');
    
    // Оновлюємо URL - додаємо параметр mode=settings
    const theme = getThemeFromPage();
    const url = new URL(window.location.href);
    url.searchParams.set('theme', theme);
    url.searchParams.set('mode', 'settings');
    url.searchParams.delete('file');
    url.searchParams.delete('folder');
    window.history.pushState({ path: url.href }, '', url.href);
    
    // Показуємо хедер та футер (вони залишаються як в редакторі, без змін)
    // Хедер та футер залишаються видимими з їх оригінальним вмістом (поточний файл, статус, кнопки)
    
    // Показуємо хедер та змінюємо заголовок на "Налаштування редактора"
    if (editorHeader) {
        editorHeader.style.display = 'block';
        // Змінюємо заголовок на "Налаштування редактора"
        const fileTitle = editorHeader.querySelector('.editor-file-title');
        if (fileTitle) {
            fileTitle.innerHTML = '<i class="fas fa-cog me-2"></i>Налаштування редактора';
        }
        // Приховуємо інформацію про файл (PHP 481 B)
        const fileInfo = editorHeader.querySelector('.d-flex.justify-content-between > div:last-child');
        if (fileInfo) {
            fileInfo.style.display = 'none';
        }
    }
    
    // Показуємо футер - він повинен бути завжди видимий
    if (editorFooter) {
        // Явно показуємо футер, перевизначаючи inline стиль display: none
        editorFooter.style.setProperty('display', 'block', 'important');
        editorFooter.style.setProperty('visibility', 'visible', 'important');
        // Показуємо всі елементи футера, НЕ змінюємо їх вміст
        const footerButtons = editorFooter.querySelector('.d-flex.gap-2');
        if (footerButtons) {
            footerButtons.style.display = 'flex';
        }
        // Оновлюємо статус для режиму налаштувань
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
        // Приховуємо кнопку "Скасувати"
        const cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        // Змінюємо кнопку "Зберегти" для збереження налаштувань
        const saveBtn = editorFooter.querySelector('button.btn-primary.btn-sm');
        if (saveBtn) {
            // Зберігаємо оригінальні значення якщо вони ще не збережені
            if (!saveBtn.getAttribute('data-original-onclick')) {
                saveBtn.setAttribute('data-original-onclick', saveBtn.getAttribute('onclick') || 'saveFile()');
                saveBtn.setAttribute('data-original-html', saveBtn.innerHTML);
            }
            // Встановлюємо обробник для збереження налаштувань
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Зберегти';
            saveBtn.setAttribute('onclick', 'saveEditorSettingsFromFooter()');
            // Залишаємо кнопку видимою
            saveBtn.style.display = '';
        }
    }
    
    // Показуємо режим налаштувань (замінює тіло редактора)
    if (settingsContent) {
        settingsContent.style.display = 'block';
        // Завантажуємо налаштування
        loadEditorSettingsInline();
    }
    
    // Переконуємося, що футер видимий після невеликої затримки
    setTimeout(function() {
        const footer = document.getElementById('editor-footer');
        if (footer) {
            footer.style.setProperty('display', 'block', 'important');
            footer.style.setProperty('visibility', 'visible', 'important');
        }
    }, 50);
}

/**
 * Ініціалізація drag & drop для завантаження файлів
 */
function initUploadDropzone() {
    const dropzone = document.getElementById('upload-dropzone');
    if (!dropzone) return;
    
    // Видаляємо старі обробники
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
 * Обробка вибраних файлів
 */
let selectedFiles = []; // Глобальна змінна для списку файлів до завантаження

function handleFileSelection(files) {
    const filesArray = Array.from(files);
    
    // Додаємо файли в список (уникаємо дублікатів)
    filesArray.forEach(file => {
        const existingIndex = selectedFiles.findIndex(f => f.name === file.name && f.size === file.size);
        if (existingIndex === -1) {
            selectedFiles.push(file);
        }
    });
    
    updateUploadFilesList();
}

/**
 * Оновлення списку файлів для завантаження
 */
function updateUploadFilesList() {
    const filesList = document.getElementById('upload-files-list');
    const filesItems = document.getElementById('upload-files-items');
    const dropzone = document.getElementById('upload-dropzone');
    
    if (!filesList || !filesItems) return;
    
    if (selectedFiles.length === 0) {
        filesList.style.display = 'none';
        // Показуємо dropzone коли немає файлів
        if (dropzone) {
            dropzone.style.display = 'flex';
        }
        // Приховуємо кнопки завантаження коли немає файлів
        const uploadClearBtn = document.getElementById('upload-clear-btn');
        const uploadSubmitBtn = document.getElementById('upload-submit-btn');
        if (uploadClearBtn) uploadClearBtn.style.display = 'none';
        if (uploadSubmitBtn) uploadSubmitBtn.style.display = 'none';
        return;
    }
    
    // Приховуємо dropzone та показуємо список файлів
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
    
    // Показуємо/приховуємо кнопки в футері залежно від наявності файлів
    const uploadClearBtn = document.getElementById('upload-clear-btn');
    const uploadSubmitBtn = document.getElementById('upload-submit-btn');
    const editorSaveBtn = document.getElementById('editor-save-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    
    if (selectedFiles.length > 0) {
        // Є файли - показуємо кнопки завантаження, приховуємо кнопки редагування
        if (uploadClearBtn) uploadClearBtn.style.display = '';
        if (uploadSubmitBtn) uploadSubmitBtn.style.display = '';
        if (editorSaveBtn) editorSaveBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';
    } else {
        // Немає файлів - приховуємо кнопки завантаження, показуємо кнопки редагування (якщо потрібно)
        if (uploadClearBtn) uploadClearBtn.style.display = 'none';
        if (uploadSubmitBtn) uploadSubmitBtn.style.display = 'none';
        // Не змінюємо відображення кнопок редагування тут, вони керуються окремо
    }
}

/**
 * Видалення файлу зі списку завантаження
 */
function removeFileFromUploadList(index) {
    selectedFiles.splice(index, 1);
    updateUploadFilesList();
}

/**
 * Очищення списку завантаження
 */
function clearUploadList() {
    selectedFiles = [];
    updateUploadFilesList();
    const fileInput = document.getElementById('uploadFilesInput');
    if (fileInput) {
        fileInput.value = '';
    }
    
    // Після очищення списку приховуємо кнопки завантаження
    const uploadClearBtn = document.getElementById('upload-clear-btn');
    const uploadSubmitBtn = document.getElementById('upload-submit-btn');
    if (uploadClearBtn) uploadClearBtn.style.display = 'none';
    if (uploadSubmitBtn) uploadSubmitBtn.style.display = 'none';
}

/**
 * Завантаження списку папок для вибору цілі завантаження
 */
function loadFoldersForUpload() {
    const theme = getThemeFromPage();
    if (!theme) return;
    
    const select = document.getElementById('upload-target-folder');
    if (!select) return;
    
    // Очищаємо існуючі опції (крім першої)
    while (select.children.length > 1) {
        select.removeChild(select.lastChild);
    }
    
    // Збираємо всі папки з дерева файлів
    const folders = document.querySelectorAll('.file-tree-folder[data-folder-path]');
    folders.forEach(folder => {
        const path = folder.getAttribute('data-folder-path');
        if (path) {
            const header = folder.querySelector('.file-tree-folder-header');
            const name = header ? header.textContent.trim().replace(/^.*?\s/, '') : path;
            const option = document.createElement('option');
            option.value = path;
            option.textContent = name || 'Корінь';
            select.appendChild(option);
        }
    });
}

/**
 * Початок завантаження файлів
 */
function startFilesUpload() {
    if (selectedFiles.length === 0) {
        showNotification('Виберіть файли для завантаження', 'warning');
        return;
    }
    
    const theme = getThemeFromPage();
    if (!theme) {
        showNotification('Тему не вказано', 'danger');
        return;
    }
    
    const targetFolder = document.getElementById('upload-target-folder')?.value || '';
    
    // Елементи футера для прогресу
    const editorFooter = document.getElementById('editor-footer');
    const footerProgressBar = document.getElementById('footer-upload-progress');
    const footerProgressBarInner = document.getElementById('footer-upload-progress-bar');
    const statusText = document.getElementById('editor-status');
    const statusIcon = document.getElementById('editor-status-icon');
    
    // Показуємо прогрес в футері
    if (footerProgressBar && footerProgressBarInner && statusText) {
        footerProgressBar.style.display = 'block';
        footerProgressBarInner.style.width = '0%';
        statusText.textContent = 'Завантаження файлів...';
        if (statusIcon) {
            statusIcon.className = 'editor-status-dot text-warning me-2';
        }
    }
    
    let uploaded = 0;
    let errors = 0;
    
    // Завантажуємо файли по одному
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
            
            // Оновлюємо прогрес бар в футері
            if (footerProgressBarInner) {
                footerProgressBarInner.style.width = progress + '%';
            }
            if (statusText) {
                statusText.textContent = `Завантаження: ${uploaded} з ${selectedFiles.length} файлів`;
            }
            
            if (!data.success) {
                errors++;
            }
            
            // Коли всі файли завантажені
            if (uploaded === selectedFiles.length) {
                if (errors === 0) {
                    // Оновлюємо статус на "Успішно всі файли завантажені"
                    if (statusText) {
                        statusText.textContent = 'Успішно всі файли завантажені';
                    }
                    if (statusIcon) {
                        statusIcon.className = 'editor-status-dot text-success me-2';
                    }
                    // Приховуємо прогрес бар через 3 секунди та повертаємо статус FTP
                    setTimeout(() => {
                        if (footerProgressBar) {
                            footerProgressBar.style.display = 'none';
                        }
                        if (statusText) {
                            statusText.textContent = 'FTP сервер: підключено';
                        }
                    }, 3000);
                    
                    showNotification(`Успішно завантажено ${uploaded} файлів`, 'success');
                    clearUploadList();
                    refreshFileTree();
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
            
            if (uploaded === selectedFiles.length) {
                showNotification(`Помилка завантаження. Завантажено ${uploaded - errors} з ${selectedFiles.length}`, 'danger');
            }
        });
    });
}

/**
 * Завантаження налаштувань для inline режиму
 */
function loadEditorSettingsInline() {
    // Якщо обробники вже встановлені, налаштування вже завантажені - не завантажуємо повторно
    if (settingsSetupDone) {
        // Просто застосовуємо поточні значення до редактора
        if (codeEditor) {
            applyEditorSettingsToCodeMirror();
        }
        return;
    }
    
    // Налаштування вже встановлені в HTML при рендерингу сторінки з PHP
    // Не потрібно їх перезавантажувати з сервера - це може перезаписати користувацькі зміни
    // Просто синхронізуємо глобальні налаштування з HTML та налаштовуємо обробники
    
    const showEmptyCheckbox = document.getElementById('showEmptyFoldersInline');
    const syntaxCheckbox = document.getElementById('enableSyntaxHighlightingInline');
    
    // Синхронізуємо глобальні налаштування з HTML значеннями
    if (showEmptyCheckbox) {
        editorSettings.showEmptyFolders = showEmptyCheckbox.checked;
    }
    if (syntaxCheckbox) {
        editorSettings.enableSyntaxHighlighting = syntaxCheckbox.checked;
    }
    
    // Застосовуємо налаштування до редактора, якщо він вже ініціалізовано
    if (codeEditor) {
        applyEditorSettingsToCodeMirror();
    }
    
    // Налаштовуємо автоматичне збереження при зміні
    setupAutoSaveEditorSettingsInline();
}

/**
 * Автоматичне збереження налаштувань в inline режимі
 */
function setupAutoSaveEditorSettingsInline() {
    // Якщо обробники вже встановлені, не додаємо їх знову
    if (settingsSetupDone) {
        return;
    }
    
    // Отримуємо всі елементи налаштувань
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
    
    // Debounce для збереження налаштувань
    let saveSettingsTimeout = null;
    
    // Функція для застосування налаштувань до редактора (без збереження)
    const applySettingsToEditor = function() {
        if (codeEditor) {
            applyEditorSettingsToCodeMirror();
        }
    };
    
    // Функція для збереження всіх налаштувань
    const saveAllSettings = function() {
        // Зберігаємо старе значення ДО застосування змін
        const oldShowEmptyFolders = editorSettings.showEmptyFolders;
        
        // Застосовуємо налаштування до редактора одразу (без збереження)
        applySettingsToEditor();
        
        // Оновлюємо глобальні налаштування одразу для перевірки змін
        if (showEmptyCheckbox) {
            editorSettings.showEmptyFolders = showEmptyCheckbox.checked;
        }
        
        // Оновлюємо дерево файлів одразу, якщо змінилася налаштування показу пустих папок
        if (showEmptyCheckbox && oldShowEmptyFolders !== showEmptyCheckbox.checked) {
            refreshFileTree();
        }
        
        // Скасовуємо попередній таймер
        if (saveSettingsTimeout) {
            clearTimeout(saveSettingsTimeout);
        }
        
        // Запускаємо збереження з затримкою (debounce 300ms)
        saveSettingsTimeout = setTimeout(function() {
            // Перевіряємо прапорець тільки перед відправкою запиту
            if (isSaving) {
                console.log('Збереження вже виконується, пропускаємо');
                return;
            }
            
            const formData = {
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
            // Використовуємо утилітну функцію для AJAX запиту
            makeAjaxRequest('save_editor_settings', formData)
                .then(data => {
                    isSaving = false;
                    if (data.success) {
                        // Оновлюємо глобальні налаштування (якщо ще не оновлені)
                        if (syntaxCheckbox) editorSettings.enableSyntaxHighlighting = syntaxCheckbox.checked;
                        
                        // Не показуємо повідомлення при кожній зміні, тільки при помилках
                        // showNotification('Налаштування збережено', 'success');
                    } else {
                        isSaving = false; // Скидаємо прапорець при помилці
                        // Відновлюємо старе значення при помилці
                        if (showEmptyCheckbox) {
                            editorSettings.showEmptyFolders = oldShowEmptyFolders;
                            showEmptyCheckbox.checked = oldShowEmptyFolders;
                            // Оновлюємо дерево назад
                            refreshFileTree();
                        }
                        showNotification(data.error || 'Помилка збереження налаштувань', 'danger');
                    }
                })
                .catch(error => {
                    isSaving = false;
                    console.error('Помилка:', error);
                    // Відновлюємо старе значення при помилці
                    if (showEmptyCheckbox) {
                        editorSettings.showEmptyFolders = oldShowEmptyFolders;
                        showEmptyCheckbox.checked = oldShowEmptyFolders;
                        // Оновлюємо дерево назад
                        refreshFileTree();
                    }
                    showNotification('Помилка збереження налаштувань', 'danger');
                });
        }, 300);
    };
    
    // Обробник для autoSave з додатковою логікою
    const autoSaveHandler = function() {
        if (autoSaveInterval) {
            autoSaveInterval.disabled = !this.checked;
        }
        saveAllSettings();
    };
    
    // Додаємо обробники тільки один раз (перевірка на початку функції)
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
    
    // Позначаємо, що обробники встановлені
    settingsSetupDone = true;
}

