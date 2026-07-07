<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prefill and Embedded Sending Agreement</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="/css/styles.css">
    <meta name="description" content="">
    <meta property="og:title" content="">
    <meta property="og:type" content="">
    <meta property="og:url" content="">
    <meta property="og:image" content="">
    <meta property="og:image:alt" content="">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#fafafa">
</head>

<body>

<div class="header">
    <img src="/img/sign-now.png" alt="SignNow Logo">
</div>

<div id="form-container">
    <div>
        <h4>Prefill and Embedded Sending Agreement Form</h4>
        <form id="contact-form" action="#" method="post">
            <div class="sn-input-group mb-3">
                <label for="name">Name<span class="text-danger">*</span></label>
                <input type="text" placeholder="John Doe" id="name" name="name" required>
            </div>
            <button type="submit" class="button-primary" id="continueButton">Continue</button>
            <button class="btn btn-dark d-none" id="loadingButton" type="submit" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            </button>
        </form>
    </div>
</div>

<div id="download-container">
    <ul class="status-list" id="documentList"></ul>
</div>

<div class="copyright gray--700 mt-3">Copyright (c) 2025 airSlate, Inc., SignNow API Sample Application v3.0</div>

<script>
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

    document.addEventListener('DOMContentLoaded', async () => {
        await handlePages({
            'form-container': async () => {
                const form = document.getElementById('contact-form');

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    document.getElementById('loadingButton').classList.remove('d-none');
                    document.getElementById('continueButton').classList.add('d-none');

                    const name = document.getElementById('name').value;

                    const response = await fetch('/api/samples/PrefillAndEmbeddedSendingAgreement', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'create-embedded-invite', name})
                    });

                    const data = await response.json();
                    if (data.link) {
                        window.location.href = data.link;
                    } else {
                        alert('Error creating embedded invite.');
                        document.getElementById('loadingButton').classList.add('d-none');
                        document.getElementById('continueButton').classList.remove('d-none');
                    }
                });
            },

            'download-with-status': async () => {
                const documentId = new URLSearchParams(window.location.search).get('document_id');

                const interval = setInterval(async () => {
                    const response = await fetch('/api/samples/PrefillAndEmbeddedSendingAgreement', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({document_id: documentId, action: 'invite-status'})
                    });

                    const data = await response.json();

                    if (Array.isArray(data) && data.length > 0) {
                        const documentList = document.getElementById('documentList');
                        documentList.innerHTML = '';

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
                                    <div class="status-badge">&#9679; ${doc.status}</div>
                                </div>
                                <div>
                                    <button class="button-outlined download-document">Download Document</button>
                                </div>
                            `;

                            li.querySelector('.download-document').addEventListener('click', async () => {
                                try {
                                    const res = await fetch('/api/samples/PrefillAndEmbeddedSendingAgreement', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({document_id: documentId, action: 'download'})
                                    });
                                    if (!res.ok) throw new Error('Failed to download.');
                                    const blob = await res.blob();
                                    const url = window.URL.createObjectURL(blob);
                                    Object.assign(document.createElement('a'), {
                                        href: url,
                                        download: res.headers.get('Content-Disposition')?.match(/filename="([^"]+)"/)?.[1] || 'agreement.pdf'
                                    }).click();
                                    window.URL.revokeObjectURL(url);
                                } catch (error) {
                                    alert('Error downloading the document.');
                                }
                            });

                            documentList.appendChild(li);
                        });

                        clearInterval(interval);
                    }
                }, 3000);
            }
        }, 'form-container');
    });
</script>

</body>
</html>
