define(['jquery', 'core/str'], function($, Str) {

    const hideSlots = (slots, uniqueid) => {

        var responseForm = document.querySelector("form#responseform");
        var ques = document.querySelectorAll("#responseform .que");
        if (ques === null) {
            return;
        }

        var count = 0; // Count of completed questions in current page.
        slots.forEach(slot => {
            var selector = '#question-' + uniqueid + '-' + slot;
            var question = document.querySelector("#responseform " + selector);
            if (question !== null) {
                count++;
            }
        });

        // Display the info about all questions in this page are completed in previos attempt.
        if (ques.length == count) {
            var wrapper = document.createElement('div');
            wrapper.classList.add('wrongquestion-completed-wrapper');

            Str.get_string('pagequestioncompletes', 'quizaccess_hidecorrect').then((str) => {
                var info = document.createElement('p');
                info.classList.add('completed-previous-info');
                info.textContent = str;

                wrapper.appendChild(info);
                responseForm.parentNode.insertBefore(wrapper, responseForm);
                return;
            }).fail();
        }

    };

    return {

        init: (slots, uniqueid, completed) => {
            hideSlots(slots, uniqueid);

            if (completed != '') {
                var qnBlock = document.querySelector('#mod_quiz_navblock');
                completed.forEach((id) => {
                    var qnButton = qnBlock.querySelector('#quiznavbutton' + id);
                    if (qnButton !== null) {
                        qnButton.classList.remove('answersaved');
                        qnButton.classList.add('correct');
                    }
                });
            }
        }
    };
});
