document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-face-auth]');

    if (!root) {
        return;
    }

    const mode = root.dataset.faceMode;
    const modelUrl = root.dataset.modelUrl;
    const threshold = parseFloat(root.dataset.faceThreshold || '0.5');
    const verifySignalUrl = root.dataset.verifySignalUrl || '';
    const csrfToken = root.dataset.csrfToken || '';
    const verificationNonce = root.dataset.verificationNonce || '';
    const video = document.getElementById('faceVideo');
    const canvas = document.getElementById('faceCanvas');
    const preview = document.getElementById('facePreview');
    const captureButton = document.getElementById('captureFaceButton');
    const statusBox = document.getElementById('faceStatus');
    const hiddenInput = document.getElementById('faceImageData');
    const verifyInput = document.getElementById('verificationResult');
    const submitButton = document.getElementById('faceSubmitButton');
    const verifyButton = document.getElementById('runFaceVerification');
    const referenceImage = document.getElementById('referenceFaceImage');
    const selectedMessage = document.getElementById('faceMatchMessage');

    let stream;
    let modelsReady = false;
    let referenceDescriptor = null;
    let liveLoopBusy = false;
    let lastReportedMatchState = null;

    const setStatus = (message, type = 'secondary') => {
        if (!statusBox) {
            return;
        }

        statusBox.className = `alert alert-${type}`;
        statusBox.textContent = message;
    };

    const detectFaceDescriptor = async (input) => {
        return faceapi
            .detectSingleFace(input, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();
    };

    const startCamera = async () => {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            },
            audio: false
        });

        video.srcObject = stream;
        await video.play();
    };

    const loadModels = async () => {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl)
        ]);

        modelsReady = true;
    };

    const loadReferenceDescriptor = async () => {
        if (mode !== 'verify' || !referenceImage) {
            return;
        }

        await new Promise((resolve, reject) => {
            if (referenceImage.complete) {
                resolve();
                return;
            }

            referenceImage.onload = resolve;
            referenceImage.onerror = reject;
        });

        const detection = await detectFaceDescriptor(referenceImage);
        if (!detection) {
            throw new Error('Reference face could not be detected.');
        }

        referenceDescriptor = detection.descriptor;
    };

    const reportVerifyState = async (matched) => {
        if (mode !== 'verify' || !verifySignalUrl || !csrfToken || !verificationNonce) {
            return;
        }

        if (lastReportedMatchState === matched) {
            return;
        }

        lastReportedMatchState = matched;

        const body = new URLSearchParams();
        body.set('csrf_token', csrfToken);
        body.set('verification_nonce', verificationNonce);
        body.set('face_match', matched ? '1' : '0');

        try {
            await fetch(verifySignalUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
                credentials: 'same-origin'
            });
        } catch (error) {
            // Keep UI responsive even if signalling fails.
        }
    };

    const captureFace = async () => {
        if (!modelsReady) {
            setStatus('Face models are still loading. Please wait a moment.', 'warning');
            return;
        }

        const detection = await detectFaceDescriptor(video);
        if (!detection) {
            setStatus('No face detected. Center your face in the frame and try again.', 'danger');
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

        if (hiddenInput) {
            hiddenInput.value = dataUrl;
        }

        if (preview) {
            preview.src = dataUrl;
            preview.classList.remove('d-none');
        }

        if (submitButton) {
            submitButton.disabled = false;
        }

        setStatus('Face captured successfully. You can continue now.', 'success');
    };

    const updateLiveStatus = async () => {
        if (mode !== 'verify' || !referenceDescriptor || liveLoopBusy) {
            return;
        }

        liveLoopBusy = true;

        try {
            const liveDetection = await detectFaceDescriptor(video);

            if (!liveDetection) {
                if (verifyInput) {
                    verifyInput.value = '';
                }
                if (submitButton) {
                    submitButton.disabled = true;
                }
                if (selectedMessage) {
                    selectedMessage.textContent = 'No live face detected yet.';
                }
                reportVerifyState(false);
                setStatus('Camera is on. Align your face with the frame.', 'warning');
                return;
            }

            const matcher = new faceapi.FaceMatcher([
                new faceapi.LabeledFaceDescriptors('verified-user', [referenceDescriptor])
            ], threshold);

            const match = matcher.findBestMatch(liveDetection.descriptor);
            const matched = match.label === 'verified-user' && match.distance < threshold;

            if (verifyInput) {
                verifyInput.value = matched ? 'matched' : '';
            }
            if (submitButton) {
                submitButton.disabled = !matched;
            }
            if (verifyButton) {
                verifyButton.classList.toggle('btn-success', matched);
                verifyButton.classList.toggle('btn-outline-primary', !matched);
            }
            if (selectedMessage) {
                selectedMessage.textContent = matched
                    ? `Match confirmed. Distance: ${match.distance.toFixed(3)}`
                    : `Face not matched yet. Distance: ${match.distance.toFixed(3)}`;
            }

            reportVerifyState(matched);

            setStatus(
                matched ? 'Face verified successfully. You can continue.' : 'Live face detected, but it does not match the enrolled face yet.',
                matched ? 'success' : 'warning'
            );
        } catch (error) {
            reportVerifyState(false);
            setStatus('Unable to complete live face verification right now.', 'danger');
        } finally {
            liveLoopBusy = false;
        }
    };

    const boot = async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('This browser does not support webcam access.', 'danger');
            return;
        }

        try {
            setStatus('Starting camera and loading face models...', 'info');
            await Promise.all([loadModels(), startCamera()]);
            await loadReferenceDescriptor();

            if (mode === 'verify') {
                setStatus('Camera ready. Hold still while we compare your face.', 'info');
                window.setInterval(updateLiveStatus, 1400);
                updateLiveStatus();
            } else {
                setStatus('Camera ready. Capture a clear face photo to continue.', 'info');
            }
        } catch (error) {
            setStatus('Face setup could not start. Please allow camera access and refresh the page.', 'danger');
        }
    };

    if (captureButton) {
        captureButton.addEventListener('click', captureFace);
    }

    if (verifyButton) {
        verifyButton.addEventListener('click', updateLiveStatus);
    }

    window.addEventListener('beforeunload', () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
        }
    });

    boot();
});
