const timer = document.getElementById('quiz-countdown');
const quizForm = document.getElementById('quiz-attempt-form');
if (timer && quizForm) {
    let remaining = Number(timer.dataset.seconds) || 0;
    const renderTimer = () => {
        if (remaining <= 0) {
            timer.textContent = 'Time is up — submitting your answers';
            quizForm.requestSubmit();
            return;
        }
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        timer.textContent = `Time remaining: ${minutes}:${String(seconds).padStart(2, '0')}`;
        remaining -= 1;
        window.setTimeout(renderTimer, 1000);
    };
    renderTimer();
}
