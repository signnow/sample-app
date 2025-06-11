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

<div class="header">
    <img src="/img/sign-now.png" alt="Logo">
</div>

<div id="form-container">
    <div>
        <h4>Credit Loan Agreement Form</h4>
        <form id="contact-form" action="#" method="post">
            <div class="sn-input-group mb-3">
                <label for="full_name">Name<span class="text-danger">*</span></label>
                <input type="text" placeholder="John Smith" id="full_name" name="full_name" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="email">Email</label>
                <input type="email" placeholder="Enter Email" id="email" name="email">
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
    <ul class="status-list" id="documentList">
    </ul>
</div>

<div class="copyright gray--700 mt-3">Copyright (c) 2025 airSlate, Inc., SignNow API Sample Application v3.0</div>

<script>
    /**
     * Handles dynamic page rendering and initialization based on the `page` query parameter in the URL.
     *
     * @param {Object} pagesMap - A map where each key is the ID of a container element (and page name),
     *                            and the value is a corresponding async handler function.
     * @param {string} [defaultKey] - (Optional) The default page key to use if the `page` parameter is missing or invalid.
     *                                If not provided, the first key in `pagesMap` will be used as fallback.
     * This function:
     * - Hides all elements whose IDs match the keys in `pagesMap`.
     * - Displays only the one corresponding to the current `page` parameter or the default.
     * - Calls the handler function for that page.
     *
     * Example usage:
     * handlePages({
     *   'form-container': async (el) => { ... },
     *   'download-container': async (el) => { ... },
     * });
     */

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
        } else {
            console.warn(`Handler for "${handlerKey}" not found or not a function.`);
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await handlePages({
            'form-container': async (el) => {
                const form = document.getElementById('contact-form');

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    document.getElementById('loadingButton').classList.remove('d-none');
                    document.getElementById('continueButton').classList.add('d-none');

                    const employee_name = document.getElementById('full_name').value;
                    const employee_email = document.getElementById('email').value;

                    const response = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            full_name: employee_name,
                            action: 'create-embedded-invite',
                            email: employee_email,
                        })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.link) {
                            window.location.href = data.link;
                        } else {
                            throw new Error('No link received from server');
                        }
                    } else {
                        throw new Error('Failed to send data');
                    }
                });
            },

            'download-with-status': async () => {
                parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin);

                const urlParams = new URLSearchParams(window.location.search);

                //Function for getting and rendering statuses
                async function updateStatuses() {
                    try {
                        const response = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                document_id: urlParams.get('document_id'),
                                action: 'invite-status'
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Не удалось получить статусы.');
                        }

                        const data = await response.json();
                        const documentList = document.getElementById('documentList');
                        documentList.innerHTML = ''; // clear list

                        if (Array.isArray(data)) {
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
                                        <button class="button-outlined download-document">
                                            Download Document
                                        </button>
                                        <button class="button-outlined refresh-status">
                                            Refresh
                                        </button>
                                    </div>
                                `;

                                // Handler for the "Download Document" button
                                const downloadButton = li.querySelector('.download-document');
                                downloadButton.addEventListener('click', async () => {
                                    try {
                                        const response = await fetch('/api/samples/EmbeddedSenderWithFormCreditLoanAgreement', {
                                            method: 'POST',
                                            headers: {'Content-Type': 'application/json'},
                                            body: JSON.stringify({
                                                document_id: urlParams.get('document_id'),
                                                action: 'download',
                                            })
                                        });

                                        if (!response.ok) {
                                            throw new Error('Failed to download the document.');
                                        }

                                        const blob = await response.blob();
                                        const url = window.URL.createObjectURL(blob);
                                        const link = Object.assign(document.createElement('a'), {
                                            href: url,
                                            download: 'document.pdf'
                                        });
                                        link.click();
                                        window.URL.revokeObjectURL(url);
                                    } catch (error) {
                                        console.error('Error', error);
                                        alert('Error downloading the document.');
                                    }
                                });

                                // Handler for the "Refresh" button
                                const refreshButton = li.querySelector('.refresh-status');
                                refreshButton.addEventListener('click', async () => {
                                    await updateStatuses();
                                });

                                documentList.appendChild(li);
                            });
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Something went wrong');
                    }
                }

                 await updateStatuses();
            }
        }, 'form-container');
    });
</script>

</body>

</html>
