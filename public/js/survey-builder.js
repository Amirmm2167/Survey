document.addEventListener('DOMContentLoaded', function () {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    let questionCounter = 0;

    addQuestionBtn.addEventListener('click', function () {
        questionCounter++;
        const questionIndex = questionCounter - 1;

        const questionBlock = document.createElement('div');
        questionBlock.classList.add('question-block');
        questionBlock.style.border = '1px solid #ccc';
        questionBlock.style.padding = '15px';
        questionBlock.style.marginBottom = '15px';
        questionBlock.style.borderRadius = '5px';

        questionBlock.innerHTML = `
            <h4>Question ${questionCounter}</h4>
            <div>
                <label>Question Text:</label>
                <input type="text" name="questions[${questionIndex}][text]" required>
            </div>
            <div>
                <label>Question Type:</label>
                <select name="questions[${questionIndex}][type]" class="question-type-select" data-question-index="${questionIndex}">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="radio">Radio</option>
                    <option value="checkbox">Checkbox</option>
                </select>
            </div>
            <div class="options-container" id="options-container-${questionIndex}" style="display: none;">
                <button type="button" class="add-option-btn" data-question-index="${questionIndex}">+ Add Option</button>
            </div>
            <button type="button" class="remove-question-btn">Remove</button>
        `;

        questionsContainer.appendChild(questionBlock);
    });

    questionsContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-question-btn')) {
            e.target.closest('.question-block').remove();
        }
        if (e.target.classList.contains('add-option-btn')) {
            const questionIndex = e.target.dataset.questionIndex;
            const optionsContainer = document.getElementById(`options-container-${questionIndex}`);
            const optionBlock = document.createElement('div');
            optionBlock.innerHTML = `<input type="text" name="questions[${questionIndex}][options][]" required> <button type="button" class="remove-option-btn">X</button>`;
            optionsContainer.insertBefore(optionBlock, e.target);
        }
        if (e.target.classList.contains('remove-option-btn')) {
            e.target.closest('div').remove();
        }
    });

    questionsContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('question-type-select')) {
            const questionIndex = e.target.dataset.questionIndex;
            const optionsContainer = document.getElementById(`options-container-${questionIndex}`);
            if (['radio', 'checkbox'].includes(e.target.value)) {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
    });
});
