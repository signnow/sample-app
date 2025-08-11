<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Onboarding System - SignNow Sample</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="header">
    <img src="/img/sign-now.png" alt="Logo">
</div>

    <!-- Form Page -->
    <div id="form-page" class="container-block">
        <h4>HR Onboarding System</h4>
        <form id="contact-form">
            <div class="sn-input-group mb-3">
                <label for="employee_name">Employee Name<span class="text-danger">*</span></label>
                <input type="text" placeholder="John Smith" id="employee_name" name="employee_name" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="employee_email">Employee Email<span class="text-danger">*</span></label>
                <input type="email" placeholder="Employee Email" id="employee_email" name="employee_email" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="hr_manager_email">HR Manager Email<span class="text-danger">*</span></label>
                <input type="email" placeholder="HR Manager" id="hr_manager_email" name="hr_manager_email" required>
            </div>
            <div class="sn-input-group mb-3">
                <label for="employer_email">Employer Email<span class="text-danger">*</span></label>
                <input type="email" placeholder="Employer Email" id="employer_email" name="employer_email" required>
            </div>
            <div class="divider sn-input-group mt-1 mb-3"></div>
            <button type="submit" class="button-primary" id="continueButton">Continue</button>
            <button class="btn btn-dark d-none" id="loadingButton" type="submit" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            </button>
        </form>
</div>

    <!-- Document Selector Page -->
<div id="document-selector" class="document-selector container-block" style="display: none;">
        <h4>Select Documents</h4>
    <ul class="document-list">
        <li class="document-item" data-id="940989288b8b4c62a950b908333b5b21efd6a174">
                <img src="/img/doc-preview.png" alt="I9 Form" class="document-image">
            <div class="document-details">
                    <h3 class="document-title">I9 Form</h3>
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

    <!-- Status Page -->
<div id="status-page" class="wide-container-block" style="display: none;">
    <div class="mb-3">
        <button id="refresh-status-btn" class="button-primary">
            Refresh Status
        </button>
    </div>
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

<script>
        function getQueryParam(key) {
            const params = new URLSearchParams(window.location.search);
            return params.get(key) || '';
        }

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

        async function initFormPage() {
                const form = document.getElementById('contact-form');
            const submitBtn = document.getElementById('continueButton');
            const loadingBtn = document.getElementById('loadingButton');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = {
                    employee_name: formData.get('employee_name'),
                    employee_email: formData.get('employee_email'),
                    hr_manager_email: formData.get('hr_manager_email'),
                    employer_email: formData.get('employer_email')
                };

                submitBtn.classList.add('d-none');
                loadingBtn.classList.remove('d-none');

                try {
                    // Redirect to document selector with form data
                    const params = new URLSearchParams(data);
                    window.location.href = `?page=document-selector&${params.toString()}`;
                } catch (error) {
                    alert('An error occurred while processing your request');
                    console.error(error);
                } finally {
                    loadingBtn.classList.add('d-none');
                    submitBtn.classList.remove('d-none');
                }
                });
        }

        async function initDocumentSelector() {
            const documentItems = document.querySelectorAll('.document-item');
                const continueButton = document.getElementById('continue-btn-documents-selected');
            const loadingButton = document.getElementById('loading-btn-documents-selected');
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
                continueButton.classList.add('d-none');
                loadingButton.classList.remove('d-none');

                try {
                    const params = new URLSearchParams(window.location.search);
                    const employee_name = params.get('employee_name');
                    const employee_email = params.get('employee_email');
                    const hr_manager_email = params.get('hr_manager_email');
                    const employer_email = params.get('employer_email');

                    const response = await fetch('/api/samples/HROnboardingSystem', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'create-invite',
                            employee_name,
                            employee_email,
                            hr_manager_email,
                            employer_email,
                            template_ids: Array.from(selectedDocumentIds),
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirect directly to status page
                        window.location.href = `?page=status-page&document_group_id=${result.document_group_id}`;
                    } else {
                        alert(result.message || 'An error occurred');
                    }
                } catch (error) {
                    alert('An error occurred while processing your request');
                    console.error(error);
                } finally {
                    loadingButton.classList.add('d-none');
                    continueButton.classList.remove('d-none');
                }
                });
        }

        async function initStatusPage() {
            parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin)
            const documentList = document.getElementById('documentList');
            const refreshButton = document.getElementById('refresh-status-btn');

            async function checkStatus() {
                try {
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
                        refreshButton.disabled = true;
                        // Update status badge instead of button text
                        const statusBadge = document.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = '✓ Completed';
                        }
                    }
                } catch (error) {
                    console.error('Error checking status:', error);
                }
            }

            // Initial status check
            await checkStatus();

            // Add click handler for refresh button
            refreshButton.addEventListener('click', checkStatus);

            documentList.addEventListener('click', async (e) => {
                try {
                    const response = await fetch('/api/samples/HROnboardingSystem', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            document_group_id: new URLSearchParams(window.location.search).get('document_group_id'),
                            action: 'download-doc-group'
                        })
                    });

                    if (!response.ok) throw new Error('Error downloading file');

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

        document.addEventListener('DOMContentLoaded', async () => {
            await handlePages({
                'form-page': initFormPage,
                'document-selector': initDocumentSelector,
                'status-page': initStatusPage
            }, 'form-page');
    });
</script>
</body>
</html>
