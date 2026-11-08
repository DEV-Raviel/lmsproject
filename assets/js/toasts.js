const notices = document.querySelectorAll('.notice[role="status"], .notice[role="alert"]');
if (notices.length) {
    const region = document.createElement('div');
    region.className = 'toast-region';
    region.setAttribute('aria-label', 'Notifications');
    document.body.append(region);

    notices.forEach((notice) => {
        const toast = document.createElement('div');
        toast.className = `toast ${notice.classList.contains('error') ? 'toast-error' : 'toast-success'}`;
        toast.setAttribute('role', notice.classList.contains('error') ? 'alert' : 'status');
        toast.setAttribute('aria-live', notice.classList.contains('error') ? 'assertive' : 'polite');
        const message = document.createElement('span');
        message.textContent = notice.textContent.trim();
        const dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'toast-dismiss';
        dismiss.setAttribute('aria-label', 'Dismiss notification');
        dismiss.textContent = '×';
        dismiss.addEventListener('click', () => toast.remove());
        toast.append(message, dismiss);
        region.append(toast);
        notice.remove();

        if (!notice.classList.contains('error')) {
            window.setTimeout(() => toast.remove(), 6000);
        }
    });
}
