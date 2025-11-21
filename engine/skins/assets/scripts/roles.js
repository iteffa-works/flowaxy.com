document.addEventListener('DOMContentLoaded', function() {
    // Вибір усіх чекбоксів
    const selectAll = document.getElementById('selectAll');
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    const bulkActionsDropdown = document.getElementById('bulkActionsDropdown');
    
    // Функція для перевірки вибраних елементів
    function updateBulkActionsVisibility() {
        const selected = document.querySelectorAll('.role-checkbox:checked');
        if (bulkActionsDropdown) {
            if (selected.length > 0) {
                bulkActionsDropdown.classList.remove('hidden');
            } else {
                bulkActionsDropdown.classList.add('hidden');
            }
        }
    }
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            roleCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkActionsVisibility();
        });
    }
    
    // Оновлюємо видимість при зміні стану чекбоксів
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActionsVisibility();
            
            // Оновлюємо стан "Вибрати все"
            if (selectAll) {
                const allChecked = Array.from(roleCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(roleCheckboxes).some(cb => cb.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            }
        });
    });
    
    // Масові дії - відкриття/закриття меню
    const bulkActionsBtn = document.getElementById('bulkActionsBtn');
    const bulkActionsMenu = document.getElementById('bulkActionsMenu');
    
    if (bulkActionsBtn && bulkActionsMenu) {
        bulkActionsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            bulkActionsMenu.classList.toggle('show');
        });
        
        // Закрити при кліку поза меню
        document.addEventListener('click', function(e) {
            if (!bulkActionsBtn.contains(e.target) && !bulkActionsMenu.contains(e.target)) {
                bulkActionsMenu.classList.remove('show');
            }
        });
        
        // Видалення вибраних ролей
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = Array.from(document.querySelectorAll('.role-checkbox:checked')).map(cb => cb.value);
                
                if (selected.length === 0) {
                    alert('Виберіть хоча б одну роль для видалення');
                    return;
                }
                
                if (!confirm('Ви впевнені, що хочете видалити ' + selected.length + ' вибраних ролей?')) {
                    return;
                }
                
                // Створюємо форму для відправки
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                // CSRF токен
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('input[name="csrf_token"]')?.value || '';
                form.appendChild(csrfInput);
                
                // Action
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'bulk_delete_roles';
                form.appendChild(actionInput);
                
                // IDs ролей
                selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'role_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            });
        }
    }
    
    // Фільтри - відкриття/закриття меню
    const filtersBtn = document.getElementById('filtersBtn');
    const filtersMenu = document.getElementById('filtersMenu');
    
    if (filtersBtn && filtersMenu) {
        filtersBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            filtersMenu.classList.toggle('show');
        });
        
        // Закрити при кліку поза меню
        document.addEventListener('click', function(e) {
            if (!filtersBtn.contains(e.target) && !filtersMenu.contains(e.target)) {
                filtersMenu.classList.remove('show');
            }
        });
        
        // Застосувати фільтри
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function() {
                const filterType = document.getElementById('filterType').value;
                const filterDateFrom = document.getElementById('filterDateFrom').value;
                const filterDateTo = document.getElementById('filterDateTo').value;
                
                const cards = document.querySelectorAll('.role-card');
                let visibleCount = 0;
                
                cards.forEach(card => {
                    let show = true;
                    
                    // Фільтр по типу
                    if (filterType) {
                        const isSystem = card.dataset.isSystem === '1';
                        
                        if (filterType === 'system' && !isSystem) {
                            show = false;
                        } else if (filterType === 'custom' && isSystem) {
                            show = false;
                        }
                    }
                    
                    // Фільтр по даті
                    if (show && (filterDateFrom || filterDateTo)) {
                        const dateElement = card.querySelector('.role-card-meta-item');
                        const dateText = dateElement?.textContent?.trim();
                        
                        if (dateText && dateText !== '-') {
                            // Извлекаем дату з тексту (формат: дд.мм.гггг)
                            const dateMatch = dateText.match(/(\d{2})\.(\d{2})\.(\d{4})/);
                            if (dateMatch) {
                                const day = parseInt(dateMatch[1]);
                                const month = parseInt(dateMatch[2]) - 1;
                                const year = parseInt(dateMatch[3]);
                                const rowDate = new Date(year, month, day);
                                
                                if (filterDateFrom) {
                                    const fromDate = new Date(filterDateFrom);
                                    if (rowDate < fromDate) {
                                        show = false;
                                    }
                                }
                                
                                if (show && filterDateTo) {
                                    const toDate = new Date(filterDateTo);
                                    toDate.setHours(23, 59, 59, 999);
                                    if (rowDate > toDate) {
                                        show = false;
                                    }
                                }
                            } else if (filterDateFrom || filterDateTo) {
                                show = false;
                            }
                        } else if (filterDateFrom || filterDateTo) {
                            show = false;
                        }
                    }
                    
                    card.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });
                
                filtersMenu.classList.remove('show');
            });
        }
        
        // Скинути фільтри
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', function() {
                document.getElementById('filterType').value = '';
                document.getElementById('filterDateFrom').value = '';
                document.getElementById('filterDateTo').value = '';
                
                document.querySelectorAll('.role-card').forEach(card => {
                    card.style.display = '';
                });
                
                filtersMenu.classList.remove('show');
            });
        }
    }
});

