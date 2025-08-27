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
                <label for="question_text_${questionIndex}">Question Text:</label>
                <input type="text" id="question_text_${questionIndex}" name="questions[${questionIndex}][text]" required class="question-text-input">
            </div>
            <br>
            <div>
                <label for="question_type_${questionIndex}">Question Type:</label>
                <select id="question_type_${questionIndex}" name="questions[${questionIndex}][type]" class="question-type-select" data-question-index="${questionIndex}">
                    <option value="text">Text (Single Line)</option>
                    <option value="textarea">Textarea (Multi-Line)</option>
                    <option value="radio">Multiple Choice (Single Answer)</option>
                    <option value="checkbox">Checkboxes (Multiple Answers)</option>
                    <option value="dropdown">Dropdown (Single Answer)</option>
                    <option value="rating_stars">Star Rating (1-5)</option>
                </select>
            </div>
            <br>
            <div class="options-container" id="options-container-${questionIndex}" style="display: none; padding-left: 20px;">
                <!-- Options will be added here -->
                <button type="button" class="add-option-btn" data-question-index="${questionIndex}">+ Add Option</button>
            </div>
            <br>
            <div>
                <label>
                    <input type="checkbox" name="questions[${questionIndex}][required]" value="1" checked> Required
                </label>
            </div>
            <br>
            <button type="button" class="remove-question-btn">Remove Question</button>
        `;

        questionsContainer.appendChild(questionBlock);
    });

    // Use event delegation for dynamically added elements
    questionsContainer.addEventListener('click', function (e) {
        // Handle removing a question
        if (e.target.classList.contains('remove-question-btn')) {
            e.target.closest('.question-block').remove();
        }

        // Handle adding an option
        if (e.target.classList.contains('add-option-btn')) {
            const questionIndex = e.target.dataset.questionIndex;
            const optionsContainer = document.getElementById(`options-container-${questionIndex}`);
            const optionIndex = optionsContainer.querySelectorAll('.option-block').length;

            const optionBlock = document.createElement('div');
            optionBlock.classList.add('option-block');
            optionBlock.innerHTML = `
                <input type="text" name="questions[${questionIndex}][options][]" placeholder="Option text" required>
                <button type="button" class="remove-option-btn" style="cursor: pointer; color: red; border: none; background: none;">&times;</button>
            `;
            // Insert before the "Add Option" button
            optionsContainer.insertBefore(optionBlock, e.target);
        }

        // Handle removing an option
        if (e.target.classList.contains('remove-option-btn')) {
            e.target.closest('.option-block').remove();
        }
    });

    questionsContainer.addEventListener('change', function(e) {
        // Handle showing/hiding the options container based on question type
        if (e.target.classList.contains('question-type-select')) {
            const questionIndex = e.target.dataset.questionIndex;
            const optionsContainer = document.getElementById(`options-container-${questionIndex}`);
            const selectedType = e.target.value;

            if (['radio', 'checkbox', 'dropdown'].includes(selectedType)) {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
    });
});
