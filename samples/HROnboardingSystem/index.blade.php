<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sample App</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
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

<div id="form-container" style="display: none;">
    <div>
        <h4>HR Onboarding System</h4>
        <form id="contact-form" action="#" method="post">
            <div class="sn-input-group mb-3">
                <label for="employee_name">Employee Name<span class="text-danger">*</span></label>
                <input type="text" placeholder="John Smith" id="employee_name" name="employee_name" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="employee_email">Employee Email<span class="text-danger">*</span></label>
                <input type="email" placeholder="Employee Email" id="employee_email" name="employee_email">
            </div>
            <div class="sn-input-group mb-3">
                <label for="hr_manager_email">HR Manager Email<span class="text-danger">*</span></label>
                <input type="email" placeholder="HR Manager" id="hr_manager_email" name="hr_manager_email">
            </div>
            <div class="divider sn-input-group mt-1 mb-3"></div>
            <button type="submit" class="button-primary" id="continueButton">Continue</button>
            <button class="btn btn-dark d-none" id="loadingButton" type="submit" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            </button>
        </form>
    </div>
</div>

<div id="download-with-status" class="wide-container-block" style="display: none;">
    <ul class="status-list" id="documentList">
        <li class="status-item">
            <div class="status-wrapper">
                <img src="/img/doc-status.png" alt="Document" />
                <div class="status-info">
                    <strong>Document 1</strong>
                    <span class="status-date">Nov 08, 2023 at 9:00pm</span>
                </div>
            </div>
            <div class="status-container">
                <div class="status-badge">&#9679; Waiting for Others</div>
            </div>
            <div>
                <button class="button-outlined download-document">
                    Download Document
                </button>
            </div>
        </li>
    </ul>
</div>

<div id="document-selector" class="document-selector container-block" style="display: none;">
    <h1>Select a Document</h1>
    <ul class="document-list">
        <li class="document-item" data-id="940989288b8b4c62a950b908333b5b21efd6a174">
            <img src="/img/doc-preview.png" alt="I9 From" class="document-image">
            <div class="document-details">
                <h3 class="document-title">I9 From</h3>
                <p class="document-date gray--700">Nov 07, 2017 at 9:00pm</p>
            </div>
        </li>
        <li class="document-item" data-id="a4f523d0cb234ffc99b0badc9e6f59111f76abc2">
            <img src="/img/doc-preview.png" alt="NDA" class="document-image">
            <div class="document-details">
                <h3 class="document-title">NDA</h3>
                <p class="document-date gray--700">Nov 07, 2017 at 9:00pm</p>
            </div>
        </li>
        <li class="document-item" data-id="1a12d3e00a54457ca1bf7bde5fa37d38ede866ed">
            <img src="/img/doc-preview.png" alt="Employee Contract" class="document-image">
            <div class="document-details">
                <h3 class="document-title">Employee Contract</h3>
                <p class="document-date gray--700">Nov 07, 2017 at 9:00pm</p>
            </div>
        </li>
    </ul>
    <button type="submit" class="button-primary" id="continue-btn-documents-selected">Continue</button>
    <button class="btn btn-dark d-none" id="loading-btn-documents-selected" type="submit" disabled>
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Loading...
    </button>
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
     *
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

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    document.getElementById('loadingButton').classList.remove('d-none');
                    document.getElementById('continueButton').classList.add('d-none');

                    const employee_name = encodeURIComponent(document.getElementById('employee_name').value);
                    const employee_email = encodeURIComponent(document.getElementById('employee_email').value);
                    const hr_manager_email = encodeURIComponent(document.getElementById('hr_manager_email').value);

                    window.location.href = `/samples/HROnboardingSystem?page=document-selector&employee_name=${employee_name}&employee_email=${employee_email}&hr_manager_email=${hr_manager_email}`;
                });
            },
            'document-selector': async (el) => {
                const documentItems = el.querySelectorAll('.document-item');
                const continueButton = document.getElementById('continue-btn-documents-selected');
                let selectedDocumentIds = new Set();

                documentItems.forEach(item => {
                    item.addEventListener('click', () => {
                        const id = item.dataset.id;
                        item.classList.toggle('selected');
                        selectedDocumentIds.has(id) ? selectedDocumentIds.delete(id) : selectedDocumentIds.add(id);
                        continueButton.disabled = selectedDocumentIds.size === 0;
                    });
                });

                continueButton.addEventListener('click', async () => {

                    document.getElementById('loading-btn-documents-selected').classList.remove('d-none');
                    document.getElementById('continue-btn-documents-selected').classList.add('d-none');

                    const params = new URLSearchParams(window.location.search);
                    const employee_name = params.get('employee_name');
                    const employee_email = params.get('employee_email');
                    const hr_manager_email = params.get('hr_manager_email');

                    const response = await fetch('/api/samples/HROnboardingSystem', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            employee_name,
                            employee_email,
                            hr_manager_email,
                            template_ids: Array.from(selectedDocumentIds),
                            action: 'create-embedded-invite',
                        })
                    });

                    if (!response.ok) throw new Error('Error repone');

                    const data = await response.json();

                    window.location.href = data.link;
                });
            },

            'download-with-status': async () => {
                parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin)
                const documentList = document.getElementById('documentList');

                let interval = setInterval(async () => {
                    const response = await fetch('/api/samples/HROnboardingSystem', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            document_group_id: new URLSearchParams(window.location.search).get('document_group_id'),
                            action: 'invite-status'
                        })
                    });

                    const data = await response.json();

                    if(data.status === "fulfilled") {
                        clearInterval(interval);
                    }
                }, 1500)

                documentList.addEventListener('click', async (e) => {
                    try {
                        const response = await fetch('/api/samples/HROnboardingSystem', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                document_group_id: new URLSearchParams(window.location.search).get('document_group_id'),
                            })
                        });

                        if (!response.ok) throw new Error('Ошибка при получении файла');

                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'document.pdf';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                    } catch (error) {
                        console.error('Error downloading document:', error);
                        alert('Error downloading document');
                    }
                });

            }
        }, 'form-container');
    });
</script>

</body>

</html>
