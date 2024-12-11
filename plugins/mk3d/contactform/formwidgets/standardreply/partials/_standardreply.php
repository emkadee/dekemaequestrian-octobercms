
<div class="form-group">
            <label for="replyDropdown">Select a reply</label>
            <select id="replyDropdown" name="reply_id" class="form-control">\
                <option value="">Select a reply</option>
                <?php foreach ($replyTitles as $id => $title): ?>
                    <option value="<?= $id ?>"><?= $title ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="example"></div>

        <!-- Add a button to send the email -->
<button id="sendEmailButton" class="btn btn-primary">Send Email</button>

<!-- Hidden input field to store the message ID -->
<input type="hidden" id="message_id" value="<?= $messageId ?>">

<script src="http://dev.dekemaequestrian.nl/modules/backend/formwidgets/richeditor/assets/js/build-min.js"></script>


<!-- Add custom CSS to style the Froala editor -->
<style>
    .fr-box {
        border: 1px solid #ccc; /* Add border similar to normal form fields */
        border-radius: 4px; /* Optional: Add border radius */
        padding: 10px; /* Optional: Add padding */
    }
</style>

<script>
    $(document).ready(function() {
        console.log('select_and_show_answers.php loaded');
        
        // Initialize Froala Editor with specific buttons
        var editorElement = $('#Form-field-Message-reply_message');
        if (editorElement.length) {
            editorElement.froalaEditor({
                toolbarButtons: ['bold', 'italic', 'align', 'outdent', 'indent', 'emoticons', 'specialCharacters', 'undo', 'redo', 'clearFormatting', 'selectAll', 'html']
            });
        } else {
            console.error('Froala editor element not found');
        }

        // JavaScript to handle the dropdown change event and fetch the reply message
        $('#replyDropdown').on('change', function() {
            var replyId = this.value;
            var messageId = $('#message_id').val(); // Get the message ID from the hidden input

            $.request('onGetReplyMessage', {
                data: { reply_id: replyId, message_id: messageId },
                success: function(response) {
                    $('#Form-field-Message-reply_message').froalaEditor('html.set', response.message);

                }
            });
        });
        // JavaScript to handle the send email button click event
        $('#sendEmailButton').on('click', function() {
            var messageId = $('#message_id').val(); // Get the message ID from the hidden input
            var emailContent = editorElement.froalaEditor('html.get'); // Get the content from the Froala editor

            alert(emailContent);

            $.request('onSendEmail', {
                data: { message_id: messageId, email_content: emailContent },
                success: function(response) {
                    alert('Email sent successfully!');
                },
                error: function(response) {
                    alert('Failed to send email.');
                }
            });
        });
    });

    
</script>



