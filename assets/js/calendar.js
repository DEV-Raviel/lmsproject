document.querySelectorAll('[data-calendar]').forEach((calendar) => {
    const monthHeading = calendar.querySelector('[data-calendar-month]');
    const daysContainer = calendar.querySelector('[data-calendar-days]');
    const dialog = document.querySelector('[data-calendar-dialog]');
    const form = dialog.querySelector('[data-calendar-form]');
    const dialogTitle = dialog.querySelector('[data-calendar-dialog-title]');
    const deleteButton = dialog.querySelector('[data-calendar-delete]');
    const eventIdInput = form.elements.event_id;
    const titleInput = form.elements.title;
    const descriptionInput = form.elements.description;
    const dateInput = form.elements.event_date;
    const events = JSON.parse(calendar.dataset.events);
    let visibleMonth = new Date();
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);

    const dateKey = (date) => [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');

    const openEditor = (date, event = null) => {
        form.reset();
        eventIdInput.value = event ? event.id : '';
        titleInput.value = event ? event.title : '';
        descriptionInput.value = event ? event.description : '';
        dateInput.value = event ? event.date : date;
        dialogTitle.textContent = event ? 'Edit event' : 'Add event';
        deleteButton.hidden = !event;
        dialog.showModal();
        titleInput.focus();
    };

    const render = () => {
        const year = visibleMonth.getFullYear();
        const month = visibleMonth.getMonth();
        monthHeading.textContent = visibleMonth.toLocaleDateString(undefined, {
            month: 'long',
            year: 'numeric',
        });
        daysContainer.replaceChildren();

        const firstWeekday = new Date(year, month, 1).getDay();
        const firstVisibleDay = new Date(year, month, 1 - firstWeekday);
        const today = dateKey(new Date());

        for (let index = 0; index < 42; index += 1) {
            const date = new Date(firstVisibleDay.getFullYear(), firstVisibleDay.getMonth(), firstVisibleDay.getDate() + index);
            const key = dateKey(date);
            const cell = document.createElement('div');
            cell.className = 'calendar-day';
            cell.setAttribute('role', 'gridcell');
            if (date.getMonth() !== month) {
                cell.classList.add('is-other-month');
            }
            if (key === today) {
                cell.classList.add('is-today');
            }

            const addDateButton = document.createElement('button');
            addDateButton.type = 'button';
            addDateButton.className = 'calendar-date';
            addDateButton.textContent = String(date.getDate());
            addDateButton.setAttribute('aria-label', `Add event on ${date.toLocaleDateString()}`);
            if (key === today) {
                addDateButton.setAttribute('aria-current', 'date');
            }
            addDateButton.addEventListener('click', () => openEditor(key));
            cell.append(addDateButton);

            const dayEvents = events.filter((event) => event.date === key);
            const visibleEventLimit = window.matchMedia('(max-width: 640px)').matches ? 1 : 3;
            dayEvents.forEach((event, eventIndex) => {
                if (event.editable) {
                    const eventButton = document.createElement('button');
                    eventButton.type = 'button';
                    eventButton.className = 'calendar-event is-custom-event';
                    if (eventIndex >= visibleEventLimit) {
                        eventButton.classList.add('is-overflow-event');
                    }
                    eventButton.textContent = event.title;
                    eventButton.title = event.description || event.title;
                    eventButton.addEventListener('click', () => openEditor(key, event));
                    cell.append(eventButton);
                } else {
                    const eventLabel = document.createElement('span');
                    eventLabel.className = `calendar-event is-deadline-event event-${event.source}`;
                    if (eventIndex >= visibleEventLimit) {
                        eventLabel.classList.add('is-overflow-event');
                    }
                    eventLabel.textContent = event.title;
                    eventLabel.title = event.detail ? `${event.source}: ${event.detail}` : event.title;
                    cell.append(eventLabel);
                }
            });

            const hiddenEventCount = dayEvents.length - visibleEventLimit;
            if (hiddenEventCount > 0) {
                const moreButton = document.createElement('button');
                moreButton.type = 'button';
                moreButton.className = 'calendar-more';
                moreButton.textContent = `+${hiddenEventCount} more`;
                moreButton.setAttribute('aria-expanded', 'false');
                moreButton.addEventListener('click', () => {
                    const isExpanded = cell.classList.toggle('is-expanded');
                    moreButton.textContent = isExpanded ? 'Show less' : `+${hiddenEventCount} more`;
                    moreButton.setAttribute('aria-expanded', String(isExpanded));
                });
                cell.append(moreButton);
            }

            daysContainer.append(cell);
        }
    };

    calendar.querySelector('[data-calendar-previous]').addEventListener('click', () => {
        visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
        render();
    });
    calendar.querySelector('[data-calendar-next]').addEventListener('click', () => {
        visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
        render();
    });
    calendar.querySelector('[data-calendar-today]').addEventListener('click', () => {
        visibleMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        render();
    });
    calendar.querySelector('[data-calendar-add]').addEventListener('click', () => openEditor(dateKey(new Date())));

    dialog.querySelectorAll('[data-calendar-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
    form.addEventListener('submit', (event) => {
        if (event.submitter?.value === 'delete'
            && !window.confirm('Delete this calendar event?')) {
            event.preventDefault();
        }
    });

    render();
});
