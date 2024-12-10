<div class="form-group">
    <label for="answer_id">Select Answer</label>
    <select class="form-control" id="answer_id" name="answer_id" data-request="onGetAnswerMessage" data-request-data="answer_id: $('#answer_id').val()">
        <option value="">Select an answer</option>
        {% for id, title in answerTitles %}
            <option value="{{ id }}">{{ title }}</option>
        {% endfor %}
    </select>
</div>

<div class="form-group">
    <label for="answer_message">Answer Message</label>
    <textarea class="form-control" id="answer_message" name="answer_message" rows="5"></textarea>
</div>

<script>
    $(document).on('ajaxSuccess', function(event, context, data) {
        if (context.handler === 'onGetAnswerMessage') {
            $('#answer_message').val(data.message);
        }
    });
</script>