<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Embedded Signer With Form Credit Loan Agreement</title>
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

<div id="form-container">
    <div>
        <h4>Credit Loan Agreement Form</h4>
        <form id="contact-form" action="#" method="post">
            <div class="sn-input-group mb-3">
                <label for="full_name">Name<span class="text-danger">*</span></label>
                <input type="text" placeholder="John Smith" id="full_name" name="full_name" required>
            </div>
            <button type="submit" class="button-primary" id="continueButton">Continue</button>
            <button class="btn btn-dark d-none" id="loadingButton" type="submit" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            </button>
        </form>
    </div>
</div>

<ul class="status-list" id="documentList"></ul>

<div class="copyright gray--700 mt-3">Copyright (c) 2025 airSlate, Inc., SignNow API Sample Application v3.0</div>

<script>
    /* ------------------------------------------------------------------
   handlePages() – minimal client-side router driven by ?page= query.
   It hides every container whose ID is present in pagesMap, then
   shows the requested one and invokes its async handler (if provided).
   ------------------------------------------------------------------ */
    async function handlePages(pagesMap, defaultKey) {
        const fallbackKey = defaultKey || Object.keys(pagesMap)[0];
        const pageParam   = new URLSearchParams(window.location.search).get('page') || fallbackKey;

        Object.keys(pagesMap).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        const handlerKey = pagesMap[pageParam] ? pageParam : fallbackKey;
        const handler    = pagesMap[handlerKey];
        const el         = document.getElementById(handlerKey);

        if (el) el.style.display = 'block';
        if (typeof handler === 'function') {
            await handler(el);
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await handlePages(
            {
                'form-container': async () => {
                    const form = document.getElementById('contact-form');
                    const loadingButton = document.getElementById('loadingButton');
                    const continueButton = document.getElementById('continueButton');

                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        loadingButton.classList.remove('d-none');
                        continueButton.classList.add('d-none');

                        const fullName = document.getElementById('full_name').value;

                        try {
                            const response = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    full_name: fullName,
                                    action: 'create-embedded-invite'
                                })
                            });

                            if (!response.ok) throw new Error('Server responded with an error.');

                            const data = await response.json();
                            if (data.link) {
                                window.location.href = data.link;
                            } else {
                                throw new Error('No “link” field in server response.');
                            }
                        } catch (err) {
                            alert(err.message || 'Failed to create embedded invite.');
                            loadingButton.classList.add('d-none');
                            continueButton.classList.remove('d-none');
                        }
                    });
                },

                'download-with-status': async () => {
                    parent.postMessage({ type: 'SAMPLE_APP_FINISHED' }, location.origin);

                    const urlParams = new URLSearchParams(window.location.search);
                    const noRecipientsContainer = document.querySelector('#download-container.no-recipients');
                    const documentList = document.getElementById('documentList');

                    /* ---------------------------------------------------------
                        Pull invite statuses from the server and render the list.
                        Shows “no recipients” block when the array is empty.
                    --------------------------------------------------------- */

                    async function updateStatuses() {
                        try {
                            const response = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
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
                            noRecipientsContainer.style.display = 'none';

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
                                            const res = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
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
                                            Object.assign(document.createElement('a'), { href: url, download: 'document.pdf' }).click();
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

                            noRecipientsContainer.style.display = 'block';
                        } catch (err) {
                            console.error(err);
                            alert('Something went wrong while updating statuses.');
                        }
                    }

                    await updateStatuses();

                    document.getElementById('goBackBtn').addEventListener('click', () => {
                        if (window.parent && window.parent !== window) {
                            window.parent.location.reload();
                        } else {
                            location.reload();
                        }
                    });
                }
            },
            'form-container'
        );
    });
</script>

</body>
</html>
