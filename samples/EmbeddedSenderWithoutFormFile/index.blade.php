<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EmbeddedSenderWithoutFormFile2</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="/css/styles.css">
    <meta name="description" content="">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#fafafa">
</head>

<body>

<ul class="status-list" id="documentList"></ul>

<div id="download-container" class="no-recipients" style="display:none">
    <div class="thank-you-message">
        <img src="/img/no-recipients.svg" alt="signNow" class="mb-3" />
        <h4>Add signers to continue</h4>
        <p class="mb-4">
            Your document needs at least one person to sign it. Go back to the editor
            to add signers and assign signature fields.
        </p>
        <button type="button" class="button-secondary" id="goBackBtn">
            Go Back &amp; Add Signers
        </button>
    </div>
</div>

<div id="finish-page" class="container-block" style="display: none;">
    <h1>Process Complete</h1>
    <p>The document sending process is complete. You can exit the app or start a new session.</p>
    <button type="button" class="button-primary" id="startNewSession">Start New Session</button>
</div>

<script>
    /* -------------------------------------------------------------
   Generic page-switching helper: hides all page blocks, then
   shows the requested block and calls its handler (if any).
   ------------------------------------------------------------- */
    async function handlePages(pagesMap, defaultKey) {
        const fallbackKey = defaultKey || Object.keys(pagesMap)[0];
        const pageParam = new URLSearchParams(window.location.search).get('page') || fallbackKey;

        Object.keys(pagesMap).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        const handlerKey = pagesMap[pageParam] ? pageParam : fallbackKey;
        const handler = pagesMap[handlerKey];
        const el = document.getElementById(handlerKey);

        if (el) el.style.display = 'block';
        if (typeof handler === 'function') {
            await handler(el);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);

        handlePages({
            'download-with-status': async () => {
                parent.postMessage({ type: 'SAMPLE_APP_FINISHED' }, location.origin);

                const downloadContainer = document.getElementById('download-container');
                const documentList = document.getElementById('documentList');
                /* -------------------------------------------------
                   Fetch invite statuses and render them to the UI
                   ------------------------------------------------- */
                async function updateStatuses() {
                    try {
                        const response = await fetch('/api/samples/EmbeddedSenderWithoutFormFile', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                document_id: urlParams.get('document_id'),
                                action: 'invite-status'
                            })
                        });

                        if (!response.ok) throw new Error('Failed to fetch invite statuses.');

                        const data = await response.json();
                        documentList.innerHTML = '';
                        downloadContainer.style.display = 'none';

                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(doc => {
                                const li = document.createElement('li');
                                li.className = 'status-item';
                                li.innerHTML = `
                                    <div class="status-wrapper">
                                        <div class="status-info">
                                            <strong>${doc.name}</strong>
                                            <span class="status-date">${doc.timestamp || ''}</span>
                                        </div>
                                    </div>
                                    <div class="status-container">
                                        <div class="status-badge">&#9679; <span>${doc.status}</span></div>
                                    </div>
                                    <div>
                                        <button class="button-outlined download-document">Download Document</button>
                                        <button class="button-outlined refresh-status">Refresh</button>
                                        <button class="btn btn-dark d-none loading-button" type="button" disabled>
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            Refresh
                                        </button>
                                    </div>
                                `;

                                li.querySelector('.download-document').addEventListener('click', async () => {
                                    try {
                                        const res = await fetch('/api/samples/EmbeddedSenderWithoutFormFile', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({
                                                document_id: urlParams.get('document_id'),
                                                action: 'download'
                                            })
                                        });

                                        if (!res.ok) throw new Error();

                                        const blob = await res.blob();
                                        const url = window.URL.createObjectURL(blob);
                                        Object.assign(document.createElement('a'), {
                                            href: url,
                                            download: 'document.pdf'
                                        }).click();
                                        window.URL.revokeObjectURL(url);
                                    } catch {
                                        alert('Error downloading the document.');
                                    }
                                });

                                li.querySelector('.refresh-status').addEventListener('click', async () => {
                                    const refreshButton = li.querySelector('.refresh-status');
                                    const loadingButton = li.querySelector('.loading-button');

                                    refreshButton.classList.add('d-none');
                                    loadingButton.classList.remove('d-none');

                                    try {
                                        await updateStatuses();
                                    } catch (err) {
                                        alert('Failed to refresh status.');
                                    } finally {
                                        refreshButton.classList.remove('d-none');
                                        loadingButton.classList.add('d-none');
                                    }
                                });

                                documentList.appendChild(li);
                            });
                            return;
                        }

                        downloadContainer.style.display = 'block';

                    } catch (err) {
                        console.error(err);
                        alert('There was an error updating statuses.');
                    }
                }

                updateStatuses();

                document.getElementById('goBackBtn').addEventListener('click', () => {
                    if (window.parent && window.parent !== window) {
                        window.parent.location.reload();
                    } else {
                        location.reload();
                    }
                });
            }
        });
    });
</script>

</body>
</html>
