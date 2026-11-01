(function () {
    const modelUrl = 'https://justadudewhohacks.github.io/face-api.js/models';
    let modelsReady;

    function loadModels() {
        if (!modelsReady) {
            modelsReady = Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl),
            ]);
        }

        return modelsReady;
    }

    async function captureDescriptor(video, status) {
        status.textContent = 'Placez votre visage face à la caméra…';
        await video.play();

        for (let attempt = 0; attempt < 10; attempt += 1) {
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
                inputSize: 320,
                scoreThreshold: 0.5,
            })).withFaceLandmarks().withFaceDescriptor();

            if (detection) {
                status.textContent = 'Visage détecté.';
                return Array.from(detection.descriptor);
            }

            await new Promise((resolve) => setTimeout(resolve, 300));
        }

        throw new Error('Aucun visage détecté. Placez votre visage face à la caméra et réessayez.');
    }

    async function withCamera(button, status, callback) {
        let stream;
        const video = document.createElement('video');
        video.autoplay = true;
        video.muted = true;
        video.playsInline = true;
        video.className = 'face-camera-preview';
        const previewContainer = status?.parentElement || button.parentElement;
        previewContainer.insertBefore(video, status?.parentElement === previewContainer ? status : null);

        try {
            button.disabled = true;
            status.textContent = 'Chargement de la reconnaissance faciale…';
            await loadModels();
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            video.srcObject = stream;
            await new Promise((resolve) => { video.onloadedmetadata = resolve; });
            await new Promise((resolve) => setTimeout(resolve, 800));
            await callback(video);
        } catch (error) {
            status.textContent = error.message || 'Impossible d’utiliser la caméra.';
        } finally {
            if (stream) stream.getTracks().forEach((track) => track.stop());
            video.remove();
            button.disabled = false;
        }
    }

    document.querySelectorAll('[data-face-login]').forEach((button) => {
        button.addEventListener('click', () => withCamera(button, button.parentElement.querySelector('[data-face-status]'), async (video) => {
            const descriptor = await captureDescriptor(video, button.parentElement.querySelector('[data-face-status]'));
            const response = await fetch(button.dataset.faceLogin, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                body: JSON.stringify({ descriptor }),
            });
            const payload = await response.json();
            if (! response.ok) throw new Error(payload.message || payload.errors?.email?.[0] || 'Visage non reconnu.');
            window.location.href = payload.redirect;
        }));
    });

    document.querySelectorAll('[data-face-enrollment]').forEach((form) => {
        const button = form.querySelector('[data-face-capture]');
        const status = form.querySelector('[data-face-status]');
        button.addEventListener('click', () => withCamera(button, status, async (video) => {
            const descriptor = await captureDescriptor(video, status);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value, Accept: 'application/json' },
                body: JSON.stringify({ current_password: form.querySelector('[name="current_password"]').value, descriptor }),
            });
            const payload = await response.json();
            if (! response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Impossible d’activer la connexion par visage.');
            status.textContent = payload.message;
            form.reset();
        }));
    });
})();
