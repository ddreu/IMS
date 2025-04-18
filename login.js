// function generateQRCode() {
//     const fingerprint = getOrCreateFingerprint();

//     fetch('qr-code/generate-qr-token.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json'
//         },
//         body: JSON.stringify({ fingerprint })
//     })
//     .then(res => res.json())
//     .then(data => {
//         if (!data.success) {
//             Swal.fire({
//                 icon: 'error',
//                 title: 'QR Error',
//                 text: data.message || 'Failed to generate QR code.'
//             });
//             return;
//         }

//         // Clear previous QR (if any)
//         const qrContainer = document.getElementById('qrCodeDisplay');
//         qrContainer.innerHTML = '';

//         // Generate new QR
//         new QRCode(qrContainer, {
//             text: data.token,
//             width: 200,
//             height: 200
//         });

//         pollForLogin(data.token); // Start polling
//     })
//     .catch(err => {
//         console.error('QR generation failed', err);
//         Swal.fire({
//             icon: 'error',
//             title: 'Oops...',
//             text: 'Something went wrong while generating QR.'
//         });
//     });
// }

function generateQRCode() {
    const fingerprint = getOrCreateFingerprint();

    // Validate fingerprint before making request
    if (!fingerprint || typeof fingerprint !== 'string' || fingerprint.trim() === '') {
        console.error('Invalid fingerprint. QR generation aborted.');
        Swal.fire({
            icon: 'error',
            title: 'Invalid Device',
            text: 'Failed to identify device fingerprint.'
        });
        return;
    }

    fetch('qr-code/generate-qr-token.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fingerprint })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            Swal.fire({
                icon: 'error',
                title: 'QR Error',
                text: data.message || 'Failed to generate QR code.'
            });
            return;
        }

        // Clear previous QR (if any)
        const qrContainer = document.getElementById('qrCodeDisplay');
        qrContainer.innerHTML = '';

        // Generate new QR
        new QRCode(qrContainer, {
            text: data.token,
            width: 200,
            height: 200
        });

        pollForLogin(data.token); // Start polling
    })
    .catch(err => {
        console.error('QR generation failed', err);
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Something went wrong while generating QR.'
        });
    });
}



function pollForLogin(token) {
    const interval = setInterval(() => {
        fetch(`qr-code/check-qr-login.php?token=${token}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    clearInterval(interval);
                    window.location.href = data.redirect;
                }
            });
    }, 2000); // Poll every 2 seconds
}
function getOrCreateFingerprint() {
    const existing = localStorage.getItem('qr_fingerprint');
    if (existing) return existing;

    const newFingerprint = 'fp_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('qr_fingerprint', newFingerprint);
    return newFingerprint;
}
