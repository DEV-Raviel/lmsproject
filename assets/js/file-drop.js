document.querySelectorAll('[data-file-drop]').forEach(dropzone => {
    const input = dropzone.querySelector('input[type="file"]');
    const label = dropzone.querySelector('[data-file-label]');
    if (!input || !label) return;

    const showFileName = () => {
        label.textContent = input.files?.[0]?.name || 'Drop a file here or browse files';
        dropzone.classList.toggle('has-file', Boolean(input.files?.length));
    };

    input.addEventListener('change', showFileName);
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.remove('is-dragging');
        });
    });
    dropzone.addEventListener('drop', event => {
        const [file] = event.dataTransfer?.files || [];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFileName();
    });
});
