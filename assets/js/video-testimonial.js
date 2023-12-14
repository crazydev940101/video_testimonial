jQuery(document).ready(function($) {
    var videoPreview = $('#video-preview')[0];
    var allowButton = $('#allow-button');
    var recordButton = $('#record-button');
    var submitButton = $('#submit-button');
    var videoReleaseCheckbox = $('#video-release-checkbox');
    var recordReleaseNote = $('#record-release-note');
    var timerBar = $('#timer-bar');
    
    var stream;
    var recorder;
    var blob;
    var videoData = new FormData();
    var isRecording = false; // Add a variable to track the recording state
    var recordingTimer; // Timer variable

    videoReleaseCheckbox.prop('disabled', true); // Enable the checkbox
    submitButton.prop('disabled', true);
    timerBar.css('display', 'none');
    recordReleaseNote.text('* Press the Allow Camera/Microphone button below and grant access to your device’s camera and microphone.');
    
    // Check if camera and microphone access is available
    function hasGetUserMedia() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    allowButton.on('click', function() {
        if (hasGetUserMedia()) {
             var constraints = {
                audio: true,
                video: {
                    width: { ideal: 1920 }, // Set the desired video width (1920 for 1080p)
                    height: { ideal: 1080 } // Set the desired video height (1080 for 1080p)
                }
            };
            
            navigator.mediaDevices.getUserMedia(constraints)
                .then(function(mediaStream) {
                    stream = mediaStream;
                    videoPreview.srcObject = mediaStream;
                    videoPreview.play();
                    recordButton.show();
                    allowButton.hide();
                    recordReleaseNote.text('* Press the Record button to record your testimonial. Click Stop to end the recording.');
                })
                .catch(function(error) {
                    alert('Error accessing camera and microphone: ' + error);
                    console.error('Error accessing camera and microphone:', error);
                });
        } else {
            alert('getUserMedia not supported')
            console.error('getUserMedia not supported');
        }
    });
    
    recordButton.on('click', function() {
        if (hasGetUserMedia()) {
            if (!isRecording) {
                startRecording();
            } else {
                stopRecording();
            }
        } else {
            alert('getUserMedia not supported');
        }
    });
    
    function startRecording() {
        recordButton.text('Stop');
        submitButton.prop('disabled', true);
        videoReleaseCheckbox.prop('disabled', true);
        timerBar.css('display', 'block');
        
        videoPreview.removeAttribute('controls');
        videoPreview.muted = true;
        videoPreview.volume = 0;
        
        // Create a new MediaStream by cloning the original stream
        var clonedStream = stream.clone();
        videoPreview.srcObject = clonedStream;
        recorder = new RecordRTC(clonedStream, {
            type: 'video',
            bitsPerSecond: 256 * 8 * 1024,   // 256 * 8 * 1024
        });           
        
        // Start recording the video
        recorder.startRecording();
        isRecording = true;

        // release camera on stopRecording
        recorder.camera = clonedStream;

        // Set the timer to stop recording after 5 minutes
        var startTime = Date.now();
        recordingTimer = setInterval(function() {
            var elapsedTime = Date.now() - startTime;
            var remainingTime = 5 * 60 * 1000 - elapsedTime; // 5 minutes = 5 * 60 * 1000 milliseconds

            if (remainingTime <= 0) {
                console.log("Time limit is done!!!");
              stopRecording();
            } else {
              updateTimer(remainingTime);
            }
        }, 1000); // Update the timer every second
    }
    
    function stopRecording(){
        recordButton.text('Record');
        if(videoReleaseCheckbox.prop('checked') && !isRecording){
            submitButton.prop('disabled', false);    
        } else {
            submitButton.prop('disabled', true); // Disable the submit button
        }
        videoReleaseCheckbox.prop('disabled', false); // Enable the checkbox
        recorder.stopRecording(stopRecordingCallback);
    }
    
    function stopRecordingCallback() {
        isRecording = false;
        timerBar.css('display', 'none');
        
        blob = recorder.getBlob();
        videoPreview.currentTime = 0;
        videoPreview.setAttribute('controls', 'controls');
        videoPreview.muted = false;
        videoPreview.volume = 1;
        videoPreview.src = videoPreview.srcObject = null;
        videoPreview.src = URL.createObjectURL(blob);
        recordReleaseNote.text('* You can review your video by pressing the play icon on the video controller above or re-record your video by pressing the Record button again.');
        
        // Generate a unique file name using a timestamp or a random string
        let fileName = 'video_' + Date.now() + '.webm';
        videoData.append('action', 'video_testimonial_submit');
        videoData.append('video_data', blob, fileName);

        // Destroy recorder
        recorder.camera.stop();
        recorder.destroy();
        recorder = null;

        // clear timer
        clearInterval(recordingTimer);
    }
    
    function updateTimer(remainingTime) {
        var minutes = Math.floor(remainingTime / 60000);
        var seconds = Math.floor((remainingTime % 60000) / 1000);
        var timerText = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
        $('#timer').text(timerText);
    }
      
    videoReleaseCheckbox.on('change', function() {
        if (videoReleaseCheckbox.prop('checked') && !isRecording) {
            submitButton.prop('disabled', false); // Enable the submit button
        } else {
            submitButton.prop('disabled', true); // Disable the submit button
        }
    });
    
    submitButton.on('click', function() {
        event.preventDefault();
    
        var name = $('#name-field').val();
        var email = $('#email-field').val();
        if (!name) {
            alert('You should put your name.');
            return;
        }
        videoData.append('name', name);
        if (email) {
            videoData.append('email', email);
        }
        
        // Disable the submit button to prevent multiple submissions
        submitButton.prop('disabled', true);
        
        // Append the video data to the form data
        videoData.append('video_release', videoReleaseCheckbox.prop('checked') ? '1' : '0');
        
        // Show the loading spinner
        var spinner = $('<div class="loading-spinner"></div>');
        submitButton.after(spinner);
        
        // Submit the form data via AJAX
        $.ajax({
            url: my_ajax_object.ajaxurl,
            type: 'POST',
            data: videoData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('response:', response);
                if (response.success) {
                    alert(response.data);
                    window.location.href = '/';
                } else {
                    alert(response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Something went wrong, ' + error);
                console.error('AJAX error: ', error);
                console.log('AJAX Log error: ', error);
            },
            complete: function() {
                // Hide the loading spinner
                spinner.remove();
                recordButton.prop('disabled', true);
                videoReleaseCheckbox.prop('disabled', true);
                submitButton.prop('disabled', true);
            }
        });
    });
});