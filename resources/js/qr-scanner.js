import { BrowserQRCodeReader } from '@zxing/browser';

// Només acceptem tokens UUID i rutes QR del nostre sistema.
// No naveguem mai a l'adreça llegida directament (evita redireccions externes).
const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
const rawToken = new RegExp(`^${UUID}$`, 'i');
const tokenInPath = new RegExp(`(?:^|/)qr/m/(${UUID})/?$`, 'i');

function extreureToken(text) {
    const valor = String(text ?? '').trim();
    if (rawToken.test(valor)) return valor.toLowerCase();

    try {
        const parsed = new URL(valor, window.location.origin);
        const match = parsed.pathname.match(tokenInPath);
        return match?.[1]?.toLowerCase() ?? null;
    } catch {
        return null;
    }
}

function iniciarLector() {
    const root = document.querySelector('[data-qr-scanner]');
    if (!root || root.dataset.qrReady === '1') return;
    root.dataset.qrReady = '1';

    const video = root.querySelector('[data-qr-video]');
    const startBtn = root.querySelector('[data-qr-start]');
    const stopBtn = root.querySelector('[data-qr-stop]');
    const status = root.querySelector('[data-qr-status]');
    const form = root.querySelector('[data-qr-form]');
    const input = root.querySelector('[data-qr-input]');
    const error = root.querySelector('[data-qr-error]');

    let controls = null;
    let iniciant = false;
    let navegant = false;
    let tancat = false;

    const mostraEstat = (missatge) => { if (status) status.textContent = missatge; };
    const mostraError = (missatge) => {
        if (!error) return;
        error.textContent = missatge;
        error.classList.toggle('hidden', !missatge);
    };

    const parar = () => {
        tancat = true;
        controls?.stop();
        controls = null;
        const stream = video?.srcObject;
        if (stream instanceof MediaStream) stream.getTracks().forEach(track => track.stop());
        if (video) video.srcObject = null;
        if (startBtn) startBtn.disabled = false;
        if (stopBtn) stopBtn.disabled = true;
        mostraEstat('Càmera aturada.');
    };

    const obrir = (contingut) => {
        const token = extreureToken(contingut);
        if (!token) {
            mostraError('El codi no correspon a una mostra de Traçabilitat.');
            return;
        }
        if (navegant) return;
        navegant = true;
        parar();
        // Només s'obre una ruta local, encara que el QR contingui una URL externa.
        window.location.assign(`/qr/m/${token}`);
    };

    startBtn?.addEventListener('click', async () => {
        if (iniciant || controls) return;
        mostraError('');
        if (!navigator.mediaDevices?.getUserMedia) {
            mostraEstat('La càmera necessita HTTPS o localhost i permís del navegador.');
            return;
        }
        iniciant = true;
        tancat = false;
        startBtn.disabled = true;
        mostraEstat('Sol·licitant accés a la càmera…');

        try {
            const reader = new BrowserQRCodeReader();
            const nousControls = await reader.decodeFromConstraints(
                { audio: false, video: { facingMode: { ideal: 'environment' } } },
                video,
                (result) => { if (result) obrir(result.getText()); }
            );
            if (tancat) {
                nousControls.stop();
            } else {
                controls = nousControls;
                stopBtn.disabled = false;
                mostraEstat('Càmera activa. Enfoca el QR de la mostra.');
            }
        } catch (err) {
            startBtn.disabled = false;
            mostraEstat('No es pot iniciar la càmera. Comprova els permisos o utilitza el lector USB.');
        } finally {
            iniciant = false;
        }
    });

    stopBtn?.addEventListener('click', parar);
    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        mostraError('');
        obrir(input?.value);
    });
    input?.focus();

    root.qrCleanup = parar;
}

document.addEventListener('livewire:navigating', () => {
    document.querySelector('[data-qr-scanner]')?.qrCleanup?.();
});
document.addEventListener('livewire:navigated', iniciarLector);
window.addEventListener('pagehide', () => {
    document.querySelector('[data-qr-scanner]')?.qrCleanup?.();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarLector);
} else {
    iniciarLector();
}
