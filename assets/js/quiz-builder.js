(() => {
    const form = document.getElementById('quiz-form');
    if (!form) return;

    const list = document.getElementById('question-list');
    const template = document.getElementById('question-template');
    const countLabel = document.getElementById('question-count');
    const pointsLabel = document.getElementById('quiz-total-points');
    const maxQuestions = 30;

    function updateSummary() {
        const cards = [...list.querySelectorAll('[data-question]')];
        const total = cards.reduce((sum, card, index) => {
            card.querySelector('.question-number').textContent = `Question ${index + 1}`;
            const points = Number(card.querySelector('.question-points').value) || 0;
            card.querySelector('.question-points-label').textContent = `${points} ${points === 1 ? 'point' : 'points'}`;
            return sum + points;
        }, 0);
        countLabel.textContent = cards.length;
        pointsLabel.textContent = `Total points: ${total}`;
        list.querySelectorAll('.remove-question').forEach(button => {
            button.disabled = cards.length === 1;
        });
        document.getElementById('add-question').disabled = cards.length >= maxQuestions;
    }

    function addQuestion() {
        const index = list.querySelectorAll('[data-question]').length;
        if (index >= maxQuestions) return;
        const content = template.innerHTML.replaceAll('__INDEX__', String(index));
        list.insertAdjacentHTML('beforeend', content);
        updateSummary();
    }

    list.addEventListener('input', event => {
        if (event.target.matches('.question-points')) updateSummary();
    });
    list.addEventListener('change', event => {
        if (!event.target.matches('.question-type')) return;
        const card = event.target.closest('[data-question]');
        const isMultipleChoice = event.target.value === 'multiple_choice';
        card.querySelector('.answer-fields').hidden = !isMultipleChoice;
        card.querySelector('.short-answer-field').hidden = isMultipleChoice;
        card.querySelectorAll('.option-inputs input').forEach(input => {
            input.disabled = !isMultipleChoice;
            input.required = isMultipleChoice;
        });
        card.querySelector('[name$="[correct]"]').disabled = !isMultipleChoice;
        const answer = card.querySelector('[name$="[answer]"]');
        answer.disabled = isMultipleChoice;
        answer.required = !isMultipleChoice;
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('.remove-question');
        if (!button || list.querySelectorAll('[data-question]').length <= 1) return;
        button.closest('[data-question]').remove();
        const cards = [...list.querySelectorAll('[data-question]')];
        cards.forEach((card, index) => {
            card.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/questions\[\d+\]/, `questions[${index}]`);
            });
        });
        updateSummary();
    });
    document.getElementById('add-question').addEventListener('click', addQuestion);
    addQuestion();
})();
